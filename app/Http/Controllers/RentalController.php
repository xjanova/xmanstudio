<?php

namespace App\Http\Controllers;

use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\UserRental;
use App\Services\RentalService;
use App\Services\StripeService;
use App\Services\ThaiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RentalController extends Controller
{
    public function __construct(
        protected RentalService $rentalService,
        protected ThaiPaymentService $paymentService
    ) {}

    /**
     * Display rental packages page
     * GET /rental
     */
    public function index()
    {
        $packages = $this->rentalService->getAvailablePackages();
        $user = Auth::user();
        $activeRental = $user ? $this->rentalService->getUserActiveRental($user) : null;

        return view('rental.packages', compact('packages', 'activeRental'));
    }

    /**
     * Show checkout page
     * GET /rental/checkout/{package}
     */
    public function checkout(RentalPackage $package)
    {
        if (! $package->is_active) {
            return redirect()->route('rental.index')
                ->with('error', 'แพ็กเกจนี้ไม่พร้อมใช้งาน');
        }

        $user = Auth::user();
        $activeRental = $this->rentalService->getUserActiveRental($user);

        if ($activeRental) {
            return redirect()->route('rental.status')
                ->with('warning', 'คุณมีแพ็กเกจที่ยังใช้งานอยู่');
        }

        $paymentMethods = $this->paymentService->getSupportedMethods();

        return view('rental.checkout', compact('package', 'paymentMethods'));
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
        ]);

        $user = Auth::user();
        $package = RentalPackage::active()->findOrFail($request->package_id);

        $result = $this->rentalService->createRental(
            $user,
            $package,
            $request->promo_code,
            $request->payment_method
        );

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        // If free package, redirect to success
        if (! $result['requires_payment']) {
            return redirect()->route('rental.status')
                ->with('success', 'เปิดใช้งานแพ็กเกจสำเร็จ');
        }

        // Handle Stripe payment - create PaymentIntent
        if ($request->payment_method === 'stripe') {
            $stripeService = app(StripeService::class);
            $intent = $stripeService->createPaymentIntentForRental($result['payment'], $user);

            return redirect()->route('rental.payment', $result['payment']->uuid)
                ->with('stripe_client_secret', $intent->client_secret);
        }

        // Redirect to payment page
        return redirect()->route('rental.payment', $result['payment']->uuid);
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
            'slip' => 'required|image|max:5120',
        ]);

        $payment = RentalPayment::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->where('status', RentalPayment::STATUS_PENDING)
            ->firstOrFail();

        $path = $request->file('slip')->store('payment-slips', 'public');

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
        $rentals = $this->rentalService->getUserRentalHistory($user);
        $payments = $user->rentalPayments()
            ->with(['userRental.rentalPackage'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('rental.status', compact('activeRental', 'rentals', 'payments'));
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
}
