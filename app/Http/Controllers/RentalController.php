<?php

namespace App\Http\Controllers;

use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\UserRental;
use App\Models\UserRentalAddon;
use App\Services\Rentals\RentalAddonPricing;
use App\Services\Rentals\RentalAddons;
use App\Services\RentalService;
use App\Services\StripeService;
use App\Services\ThaiPaymentService;
use App\Services\TpixPay\TpixCheckout;
use App\Support\PaymentSlips;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RentalController extends Controller
{
    public function __construct(
        protected RentalService $rentalService,
        protected ThaiPaymentService $paymentService,
        protected RentalAddons $addons,
    ) {}

    /** The opaque token mlm.xman4289.com issues when a subdomain is reserved. */
    private const RESERVATION_PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    /**
     * Display rental packages page
     * GET /rental
     */
    public function index()
    {
        $packages = $this->rentalService->getAvailablePackages();
        $user = Auth::user();
        $activeRental = $user ? $this->rentalService->getUserActiveRental($user) : null;
        $activeRentals = $user ? $this->rentalService->getUserActiveRentals($user) : collect();

        // Products (XMAN MLM, …) get a section of their own with their add-on price list.
        $productSections = $packages->whereNotNull('product_key')->groupBy('product_key')
            ->map(fn ($group, $productKey) => [
                'packages' => $group->values(),
                'addons' => $this->addons->catalogue($productKey),
            ]);
        $packages = $packages->whereNull('product_key')->values();

        return view('rental.packages', compact('packages', 'activeRental', 'activeRentals', 'productSections'));
    }

    /**
     * Show checkout page
     * GET /rental/checkout/{package}
     *
     * {package} is an id (every old link) or a product package's code; the outside world — the
     * MLM site — links with the code and its picks:
     *   /rental/checkout/mlm-core?cycle=yearly&addons[]=mlm-members-5k&mlm_reservation=<token>
     */
    public function checkout(Request $request, string $package)
    {
        $cycle = is_string($request->query('cycle')) ? $request->query('cycle') : null;
        $package = RentalPackage::findForCheckout($package, $cycle);

        if (! $package || ! $package->is_active) {
            return redirect()->route('rental.index')
                ->with('error', 'แพ็กเกจนี้ไม่พร้อมใช้งาน');
        }

        $user = Auth::user();
        $selection = RentalAddons::normalizeSelection($request->query('addons'));
        $activeRental = $this->rentalService->getUserActiveRental($user, $package->product_key);

        if ($activeRental) {
            // Already renting this product: add-ons asked for here are bought for that rental.
            if ($package->product_key && $selection !== []) {
                return redirect()->route('rental.addons', ['rental' => $activeRental->id, 'addons' => $selection])
                    ->with('warning', 'คุณเช่าแพ็กนี้อยู่แล้ว — เพิ่มตัวเลือกเสริมให้รายการเดิมได้ที่หน้านี้');
            }

            return redirect()->route('rental.status')
                ->with('warning', 'คุณมีแพ็กเกจที่ยังใช้งานอยู่');
        }

        $paymentMethods = $this->paymentMethods();

        // Product checkout: the package's add-on list, what the link preselected, the cycle switch.
        $addonCatalogue = collect();
        $selected = [];
        $addonNotices = [];
        $cycles = [];
        $reservation = null;
        $reservationInvalid = false;

        if ($package->product_key && $package->billingCycle()) {
            $addonCatalogue = $this->addons->catalogue($package->product_key);
            $resolved = $this->addons->resolve($package->product_key, $selection);
            foreach ($resolved['items'] as $item) {
                $selected[$item['addon']->code] = $item['quantity'];
            }
            $addonNotices = $resolved['errors'];

            foreach (['monthly', 'yearly'] as $c) {
                $row = $c === $package->billingCycle() ? $package : $package->sibling($c);
                if ($row && $row->is_active) {
                    $cycles[$c] = $row;
                }
            }

            $raw = $request->query('mlm_reservation');
            if (is_string($raw) && $raw !== '') {
                if (preg_match(self::RESERVATION_PATTERN, $raw)) {
                    $reservation = $raw;
                } else {
                    $reservationInvalid = true;
                }
            }
        }

        return view('rental.checkout', compact(
            'package', 'paymentMethods', 'addonCatalogue', 'selected', 'addonNotices', 'cycles', 'reservation', 'reservationInvalid'
        ));
    }

    /**
     * Process checkout
     * POST /rental/checkout
     */
    public function processCheckout(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:rental_packages,id',
            'payment_method' => 'required|in:promptpay,bank_transfer,credit_card,stripe',
            'promo_code' => 'nullable|string|max:50',
            // Product packages: addons[code] = quantity (0 = not taken), and the MLM reservation.
            'addons' => 'nullable|array|max:30',
            'addons.*' => 'nullable|integer|min:0|max:99',
            'mlm_reservation' => ['nullable', 'string', 'regex:' . self::RESERVATION_PATTERN],
        ]);

        $user = Auth::user();
        $package = RentalPackage::active()->findOrFail($request->package_id);

        $result = $this->rentalService->createRental(
            $user,
            $package,
            $request->promo_code,
            $request->payment_method,
            [
                'addons' => RentalAddons::normalizeSelection($request->input('addons')),
                'reservation' => $request->input('mlm_reservation'),
            ]
        );

        if (! $result['success']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return $this->toPayment($result, $request->payment_method, 'เปิดใช้งานแพ็กเกจสำเร็จ');
    }

    /**
     * Renewal page: the package (monthly or yearly) and every add-on in force, full price.
     * GET /rental/{rental}/renew
     */
    public function renew(Request $request, UserRental $rental)
    {
        [$rental, $current] = $this->ownProductRental($rental);

        if (! in_array($rental->status, [UserRental::STATUS_ACTIVE, UserRental::STATUS_EXPIRED], true)) {
            return redirect()->route('rental.status')
                ->with('error', 'การเช่านี้' . $rental->status_label . ' ต่ออายุไม่ได้ — ติดต่อทีมงาน');
        }

        $cycles = [];
        foreach (['monthly', 'yearly'] as $c) {
            $row = $c === $current->billingCycle() ? $current : $current->sibling($c);
            if ($row && ($row->is($current) || $row->is_active)) {
                $cycles[$c] = $row;
            }
        }
        $cycle = $request->query('cycle');
        $target = is_string($cycle) && isset($cycles[$cycle]) ? $cycles[$cycle] : $current;
        $targetCycle = $target->billingCycle() ?? 'monthly';

        $items = $rental->addons()->where('status', UserRentalAddon::STATUS_ACTIVE)->with('addon')->get()
            ->filter(fn ($row) => $row->addon !== null)
            ->map(fn ($row) => ['addon' => $row->addon, 'quantity' => (int) $row->quantity])
            ->values()->all();
        $lines = array_merge([RentalAddons::packageLine($target)], RentalAddons::fullCycleLines($items, $targetCycle));
        $total = RentalAddons::total($lines);

        $base = $rental->expires_at && $rental->expires_at->isFuture() ? $rental->expires_at->copy() : now();
        $newExpiry = $target->calculateExpiryDate($base);
        $paymentMethods = $this->paymentMethods();

        return view('rental.renew', compact('rental', 'current', 'target', 'targetCycle', 'cycles', 'lines', 'total', 'newExpiry', 'paymentMethods'));
    }

    /**
     * POST /rental/{rental}/renew
     */
    public function processRenew(Request $request, UserRental $rental)
    {
        $this->ownProductRental($rental);

        $request->validate([
            'payment_method' => 'required|in:promptpay,bank_transfer,credit_card,stripe',
            'cycle' => 'nullable|in:monthly,yearly',
        ]);

        $result = $this->rentalService->createRenewal(Auth::user(), $rental, $request->payment_method, $request->cycle);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        return $this->toPayment($result, $request->payment_method, 'ต่ออายุสำเร็จ');
    }

    /**
     * Add add-ons to a running rental, priced for the days the package has left.
     * GET /rental/{rental}/addons   (?addons[]=code preselects, as the MLM site links)
     */
    public function addons(Request $request, UserRental $rental)
    {
        [$rental, $package] = $this->ownProductRental($rental);

        if (! $rental->is_active || RentalAddonPricing::daysLeft($rental) <= 0) {
            return redirect()->route('rental.status')
                ->with('error', 'แพ็กหลักไม่ได้ใช้งานอยู่หรือหมดอายุวันนี้ — ต่ออายุก่อนแล้วค่อยเพิ่มตัวเลือกเสริม');
        }

        $cycle = $rental->billingCycle() ?? 'monthly';
        $daysLeft = RentalAddonPricing::daysLeft($rental);
        $cycleDays = RentalAddonPricing::cycleDays($rental);
        $catalogue = $this->addons->catalogue($package->product_key);
        $held = $this->addons->held($rental);

        $heldGroups = [];
        foreach ($catalogue as $addon) {
            if ($addon->exclusive_group && isset($held[$addon->code])) {
                $heldGroups[$addon->exclusive_group] = $addon->code;
            }
        }

        $selected = [];
        foreach (RentalAddons::normalizeSelection($request->query('addons')) as $code => $quantity) {
            $selected[$code] = $quantity;
        }

        $paymentMethods = $this->paymentMethods();

        return view('rental.addons', compact(
            'rental', 'package', 'cycle', 'daysLeft', 'cycleDays', 'catalogue', 'held', 'heldGroups', 'selected', 'paymentMethods'
        ));
    }

    /**
     * POST /rental/{rental}/addons
     */
    public function processAddons(Request $request, UserRental $rental)
    {
        $this->ownProductRental($rental);

        $request->validate([
            'payment_method' => 'required|in:promptpay,bank_transfer,credit_card,stripe',
            'addons' => 'required|array|max:30',
            'addons.*' => 'nullable|integer|min:0|max:99',
        ]);

        $result = $this->rentalService->createAddonPurchase(
            Auth::user(),
            $rental,
            RentalAddons::normalizeSelection($request->input('addons')),
            $request->payment_method
        );

        if (! $result['success']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return $this->toPayment($result, $request->payment_method, 'เพิ่มตัวเลือกเสริมสำเร็จ');
    }

    /**
     * Show payment page
     * GET /rental/payment/{uuid}
     */
    public function payment(string $uuid)
    {
        $payment = RentalPayment::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->where('status', RentalPayment::STATUS_PENDING)
            ->with(['userRental.rentalPackage'])
            ->firstOrFail();

        // An add-on quote holds for the day it was made: opened on a later day, it is priced again
        // for today's remaining days before any amount or QR is shown.
        $quote = $this->rentalService->refreshAddonQuote($payment);
        if ($quote === 'lapsed') {
            return redirect()->route('rental.status')
                ->with('error', 'แพ็กหลักหมดอายุก่อนชำระตัวเลือกเสริม รายการนี้จึงถูกยกเลิก — ต่ออายุแล้วเลือกใหม่ได้');
        }
        if ($quote === 'repriced') {
            $payment->refresh()->load('userRental.rentalPackage');
            session()->forget('stripe_client_secret');
            session()->now('info', 'คำนวณราคาตัวเลือกเสริมใหม่ตามวันที่เหลือของวันนี้แล้ว');
        }

        // Self-heal: Stripe payment succeeded but webhook hasn't arrived yet
        if ($payment->payment_method === 'stripe'
            && $payment->stripe_payment_intent_id
        ) {
            try {
                $stripeService = app(StripeService::class);
                $intent = $stripeService->retrievePaymentIntent($payment->stripe_payment_intent_id);
                if ($intent->status === 'succeeded') {
                    $payment->update([
                        'gateway_response' => [
                            'payment_intent_id' => $intent->id,
                            'amount_received' => $intent->amount_received,
                            'payment_method' => $intent->payment_method,
                        ],
                    ]);
                    $payment->markAsCompleted();
                    $payment->refresh();
                }
            } catch (\Exception $e) {
                // Ignore — webhook or next poll will handle it
            }
        }

        $paymentInfo = [];
        $stripeClientSecret = null;
        $stripePublishableKey = null;
        $stripeFeeInfo = null;

        if ($payment->payment_method === 'promptpay') {
            $paymentInfo = $this->paymentService->generatePromptPayQR(
                $payment->amount,
                $payment->payment_reference
            );
        } elseif ($payment->payment_method === 'bank_transfer') {
            $paymentInfo = [
                'bank_accounts' => $this->paymentService->getBankTransferInfo(),
            ];
        } elseif ($payment->payment_method === 'stripe') {
            $stripeService = app(StripeService::class);
            $stripePublishableKey = $stripeService->getPublishableKey();
            $stripeClientSecret = session('stripe_client_secret');
            $stripeFeeInfo = $stripeService->calculateStripeFee($payment->amount);

            if (! $stripeClientSecret && $payment->stripe_payment_intent_id) {
                try {
                    $intent = $stripeService->retrievePaymentIntent($payment->stripe_payment_intent_id);
                    if ($intent->status !== 'canceled' && $intent->status !== 'succeeded') {
                        $stripeClientSecret = $intent->client_secret;
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            if (! $stripeClientSecret) {
                try {
                    $intent = $stripeService->createPaymentIntentForRental($payment, Auth::user());
                    $stripeClientSecret = $intent->client_secret;
                } catch (\Exception $e) {
                    Log::error('Failed to create Stripe PaymentIntent for rental', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return view('rental.payment', compact(
            'payment', 'paymentInfo', 'stripeClientSecret', 'stripePublishableKey', 'stripeFeeInfo'
        ));
    }

    /**
     * Upload transfer slip
     * POST /rental/payment/{uuid}/upload-slip
     */
    public function uploadSlip(Request $request, string $uuid)
    {
        $request->validate([
            // Raster only: `image` alone admits SVG, which runs script when an admin opens the slip.
            'slip' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $payment = RentalPayment::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->where('status', RentalPayment::STATUS_PENDING)
            ->firstOrFail();

        // Kept off the web root; the admin rental list opens it through PaymentSlipController
        $path = PaymentSlips::store($request->file('slip'));
        if ($path === null) {
            return back()->with('error', 'บันทึกสลิปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        $payment->update([
            'transfer_slip_url' => $path,
            'status' => RentalPayment::STATUS_PROCESSING,
        ]);

        return redirect()->route('rental.payment.status', $uuid)
            ->with('success', 'อัพโหลดสลิปสำเร็จ รอตรวจสอบ');
    }

    /**
     * Show payment status
     * GET /rental/payment/{uuid}/status
     */
    public function paymentStatus(string $uuid)
    {
        $payment = RentalPayment::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->with(['userRental.rentalPackage'])
            ->firstOrFail();

        // Self-heal: Stripe payment succeeded but webhook hasn't arrived yet
        if ($payment->status === RentalPayment::STATUS_PENDING
            && $payment->payment_method === 'stripe'
            && $payment->stripe_payment_intent_id
        ) {
            try {
                $stripeService = app(StripeService::class);
                $intent = $stripeService->retrievePaymentIntent($payment->stripe_payment_intent_id);
                if ($intent->status === 'succeeded') {
                    $payment->update([
                        'gateway_response' => [
                            'payment_intent_id' => $intent->id,
                            'amount_received' => $intent->amount_received,
                            'payment_method' => $intent->payment_method,
                        ],
                    ]);
                    $payment->markAsCompleted();
                    $payment->refresh();
                }
            } catch (\Exception $e) {
                // Ignore — webhook or next poll will handle it
            }
        }

        return view('rental.payment-status', compact('payment'));
    }

    /**
     * Show current rental status
     * GET /rental/status
     */
    public function status()
    {
        $user = Auth::user();
        $activeRental = $this->rentalService->getUserActiveRental($user);
        $productRentals = $this->rentalService->getUserProductRentals($user);
        $rentals = $this->rentalService->getUserRentalHistory($user);
        $payments = $user->rentalPayments()
            ->with(['userRental.rentalPackage'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('rental.status', compact('activeRental', 'productRentals', 'rentals', 'payments'));
    }

    /**
     * Validate promo code (AJAX)
     * POST /rental/validate-promo
     */
    public function validatePromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'package_id' => 'nullable|exists:rental_packages,id',
        ]);

        $result = $this->rentalService->validatePromoCode(
            $request->code,
            Auth::user(),
            $request->package_id
        );

        return response()->json($result);
    }

    /**
     * Cancel rental
     * POST /rental/{rental}/cancel
     */
    public function cancel(Request $request, UserRental $rental)
    {
        if ($rental->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $this->rentalService->cancelRental($rental, $request->reason);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        return redirect()->route('rental.status')
            ->with('success', 'ยกเลิกการเช่าสำเร็จ');
    }

    /**
     * Show invoices
     * GET /rental/invoices
     */
    public function invoices()
    {
        $invoices = Auth::user()->rentalInvoices()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('rental.invoices', compact('invoices'));
    }

    /**
     * Payment methods a rental can be paid with. A rental is paid through a RentalPayment, which
     * TPIX does not settle (TPIX pays orders and wallet top-ups) — so the TPIX choice the shared
     * list carries is not offered here.
     */
    private function paymentMethods(): array
    {
        return array_values(array_filter(
            $this->paymentService->getSupportedMethods(),
            fn (array $method) => $method['id'] !== TpixCheckout::METHOD,
        ));
    }

    /** After a payment is created: nothing to pay, Stripe's intent, or the payment page. */
    private function toPayment(array $result, string $paymentMethod, string $freeMessage)
    {
        if (! $result['requires_payment']) {
            return redirect()->route('rental.status')->with('success', $freeMessage);
        }

        if ($paymentMethod === 'stripe') {
            $stripeService = app(StripeService::class);
            $intent = $stripeService->createPaymentIntentForRental($result['payment'], Auth::user());

            return redirect()->route('rental.payment', $result['payment']->uuid)
                ->with('stripe_client_secret', $intent->client_secret);
        }

        return redirect()->route('rental.payment', $result['payment']->uuid);
    }

    /**
     * The signed-in user's own rental of a product (renew / add-ons are for products only).
     *
     * @return array{0:UserRental,1:RentalPackage}
     */
    private function ownProductRental(UserRental $rental): array
    {
        if ($rental->user_id !== Auth::id()) {
            abort(403);
        }

        $package = $rental->rentalPackage()->withTrashed()->first();
        abort_unless($package && $package->product_key, 404);
        $rental->setRelation('rentalPackage', $package);

        return [$rental, $package];
    }
}
