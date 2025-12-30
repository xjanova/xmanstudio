<?php

namespace App\Http\Controllers;

use App\Models\RentalPackage;
use App\Models\UserRental;
use App\Models\RentalPayment;
use App\Services\RentalService;
use App\Services\ThaiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return view('rental.index', compact('packages', 'activeRental'));
    }

    /**
     * Show checkout page
     * GET /rental/checkout/{package}
     */
    public function checkout(RentalPackage $package)
    {
        if (!$package->is_active) {
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
            'payment_method' => 'required|in:promptpay,bank_transfer,credit_card',
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

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        // If free package, redirect to success
        if (!$result['requires_payment']) {
            return redirect()->route('rental.status')
                ->with('success', 'เปิดใช้งานแพ็กเกจสำเร็จ');
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

        $paymentInfo = [];

        if ($payment->payment_method === 'promptpay') {
            $paymentInfo = $this->paymentService->generatePromptPayQR(
                $payment->amount,
                $payment->payment_reference
            );
        } elseif ($payment->payment_method === 'bank_transfer') {
            $paymentInfo = [
                'bank_accounts' => $this->paymentService->getBankTransferInfo(),
            ];
        }

        return view('rental.payment', compact('payment', 'paymentInfo'));
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
        $history = $this->rentalService->getUserRentalHistory($user);

        return view('rental.status', compact('activeRental', 'history'));
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

        if (!$result['success']) {
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
