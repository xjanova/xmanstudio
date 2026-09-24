<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\AiprayDonation;
use App\Models\Product;
use App\Services\AiprayDonationService;
use Illuminate\Http\Request;

class AiprayController extends Controller
{
    use ServesReleaseDownloads;

    /**
     * หน้าแอป Aipray — เวอร์ชัน/บันทึกการเปลี่ยนแปลงมาจากเวอร์ชันที่ sync ไว้ใน DB ปุ่มโหลดชี้มาที่เว็บเราเอง
     *
     * เดิมถาม GitHub API ทุกครั้งที่มีคนเปิดหน้า (ไม่มี token = 60 ครั้ง/ชม. ต่อทั้งเซิร์ฟเวอร์) แล้วยื่นลิงก์ไฟล์บน
     * GitHub ให้ลูกค้า = บอก repo · กฎเจ้าของ (2026-09-24) แอปโหลดจาก xman4289.com เท่านั้น
     * latestRelease() ยังตามทัน release ใหม่เอง (ถาม GitHub ได้อย่างมากทุก 5 นาที) ถ้าต่อไม่ติดก็ใช้ของใน DB
     */
    public function show()
    {
        $product = Product::where('slug', 'aipray')->with('githubSetting')->firstOrFail();

        $latest = $this->latestRelease($product);

        // ยังไม่มีเวอร์ชันใน DB = ไม่แสดงป้ายเวอร์ชัน (เดิมเดาเป็น 1.0.0)
        $version = $latest?->version;
        $changelog = $latest?->changelog ?? '';
        $downloadUrl = route('aipray.download');

        $donationService = app(AiprayDonationService::class);
        $donations = $donationService->getPublicDonations(20);
        $donationStats = $donationService->getStats();

        return view('aipray.show', compact(
            'product', 'version', 'changelog', 'downloadUrl',
            'donations', 'donationStats'
        ));
    }

    /**
     * ดาวน์โหลด APK (ฟรี ไม่ต้องล็อกอิน) — ไฟล์ส่งจาก xman4289.com เอง ไม่ redirect ไป GitHub
     *
     * ไฟล์ไหนคือตัวให้ลูกค้ากำหนดที่ asset_pattern ของ GitHub setting (ตัว universal — เจ้าของเลือก 2026-09-24)
     * Content-Type เป็น application/vnd.android.package-archive มือถือจึงเสนอติดตั้งได้ทันที
     */
    public function download(Request $request)
    {
        $product = Product::where('slug', 'aipray')->where('is_active', true)->first();

        if (! $product) {
            // หน้าแอปก็ 404 อยู่แล้ว ไม่มีที่ให้ส่งเบราว์เซอร์กลับไป
            abort_if($this->isBrowser($request), 404);

            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        return $this->serveRelease($request, $product, $this->latestRelease($product), route('aipray.show'));
    }

    public function donate()
    {
        $donationService = app(AiprayDonationService::class);
        $qr = $donationService->generateQr(0); // Default QR without amount
        $donations = $donationService->getPublicDonations(10);

        return view('aipray.donate', compact('qr', 'donations'));
    }

    public function storeDonation(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'donor_name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $donationService = app(AiprayDonationService::class);
        $donation = $donationService->createDonation($validated);

        // Generate QR with specific amount
        $qr = $donationService->generateQr((float) $validated['amount']);

        return view('aipray.donate', [
            'qr' => $qr,
            'donation' => $donation,
            'amount' => $validated['amount'],
            'donations' => $donationService->getPublicDonations(10),
            'success' => true,
        ]);
    }

    public function donationComplete(Request $request)
    {
        $request->validate(['donation_id' => 'required|integer']);

        $donation = AiprayDonation::findOrFail($request->donation_id);
        $donationService = app(AiprayDonationService::class);
        $donationService->completeDonation($donation, $request->input('reference'));

        return redirect()->route('aipray.show')
            ->with('success', 'ขอบคุณสำหรับการบริจาค! สาธุ');
    }
}
