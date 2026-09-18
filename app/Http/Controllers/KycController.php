<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ยืนยันตัวตน (KYC) ฝั่งผู้ใช้
 *
 * ด่านเดียวกันนี้เปิดสองอย่าง: การรับ/ถอนเงิน และการสร้างเนื้อหาผู้ใหญ่บน
 * ai.xman4289.com เอกสารทั้งหมดอยู่บนดิสก์ส่วนตัว ไม่มี URL สาธารณะ
 */
class KycController extends Controller
{
    /** ไบต์สูงสุดต่อไฟล์ — ใหญ่พอสำหรับรูปจากมือถือ เล็กพอที่คนอัปวิดีโอมาไม่ได้ */
    private const MAX_UPLOAD_KB = 5120;

    private const MIN_WIDTH = 300;

    private const MIN_HEIGHT = 200;

    // ไม่มี middleware ในคอนสตรักเตอร์ — Laravel 11 ถอด Controller::middleware() ออกแล้ว
    // การตรวจสิทธิ์อยู่ที่กลุ่ม route (`Route::middleware('auth')`) ตามแบบที่รีโปนี้ใช้

    public function index(Request $request)
    {
        $kyc = KycVerification::where('user_id', $request->user()->id)->first();

        return view('kyc.index', [
            'kyc' => $kyc,
            'banks' => $this->banks(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $existing = KycVerification::where('user_id', $user->id)->first();

        // ระหว่างรอตรวจห้ามส่งซ้ำ — ไม่งั้นคิวของผู้ตรวจเต็มไปด้วยใบเดิม
        // และผู้ใช้เข้าใจผิดว่าส่งใหม่แล้วจะถูกตรวจเร็วขึ้น
        if ($existing && ! $existing->canResubmit()) {
            return redirect()
                ->route('kyc.index')
                ->with('error', $existing->isApproved()
                    ? 'บัญชีนี้ยืนยันตัวตนเรียบร้อยแล้ว'
                    : 'คำขอของคุณอยู่ระหว่างการตรวจสอบ กรุณารอผลก่อนส่งใหม่');
        }

        $validated = $request->validate([
            'id_card_number' => ['required', 'string', 'max:20'],
            'full_name_th' => ['required', 'string', 'max:150'],
            'full_name_en' => ['nullable', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            'bank_code' => ['required', 'string', 'max:20'],
            'bank_account_number' => ['required', 'string', 'max:30'],
            'bank_account_name' => ['required', 'string', 'max:150'],
            'id_card_front' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:' . self::MAX_UPLOAD_KB],
            'id_card_back' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:' . self::MAX_UPLOAD_KB],
            'selfie' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:' . self::MAX_UPLOAD_KB],
            'bank_book' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:' . self::MAX_UPLOAD_KB],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'กรุณายอมรับเงื่อนไขการเก็บและใช้ข้อมูลยืนยันตัวตน',
        ]);

        if (! KycVerification::isValidThaiIdCardNumber($validated['id_card_number'])) {
            throw ValidationException::withMessages([
                'id_card_number' => 'เลขบัตรประชาชนไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง (13 หลัก)',
            ]);
        }

        $digits = preg_replace('/\D/', '', $validated['id_card_number']) ?? '';
        $hash = KycVerification::hashIdCardNumber($digits);

        // หนึ่งบัตร = หนึ่งบัญชี กันคนเดียวเปิดหลายบัญชีเพื่อรับสิทธิ์ซ้ำ
        $takenByAnother = KycVerification::where('id_card_number_hash', $hash)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($takenByAnother) {
            throw ValidationException::withMessages([
                'id_card_number' => 'เลขบัตรนี้ถูกใช้ยืนยันตัวตนกับบัญชีอื่นแล้ว หากคิดว่าผิดพลาดกรุณาติดต่อทีมงาน',
            ]);
        }

        $paths = [
            'id_card_front_path' => $this->storeDocument($request, 'id_card_front', $user->id),
            'id_card_back_path' => $this->storeDocument($request, 'id_card_back', $user->id),
            'selfie_path' => $this->storeDocument($request, 'selfie', $user->id),
            'bank_book_path' => $this->storeDocument($request, 'bank_book', $user->id),
        ];

        // เก็บเหตุผลที่เคยถูกปฏิเสธไว้ให้ผู้ตรวจคนถัดไปเห็นว่าพลาดอะไรมาแล้ว
        $history = $existing?->history ?? [];
        if ($existing && $existing->status === KycVerification::STATUS_REJECTED) {
            $history[] = [
                'rejected_at' => optional($existing->reviewed_at)->toIso8601String(),
                'reason' => $existing->rejection_reason,
            ];
        }

        // ไฟล์ที่ไม่ได้อัปมารอบนี้ ให้คงของเดิมไว้ ไม่ใช่ล้างเป็น null —
        // คนที่ถูกปฏิเสธเพราะรูปบัตรเบลอ ไม่ควรต้องถ่ายสมุดบัญชีใหม่ด้วย
        foreach ($paths as $column => $path) {
            if ($path === null && $existing) {
                $paths[$column] = $existing->{$column};
            }
        }
        $paths = array_filter($paths, fn ($path) => $path !== null);

        DB::transaction(function () use ($user, $existing, $validated, $paths, $hash, $digits, $history) {
            KycVerification::updateOrCreate(
                ['user_id' => $user->id],
                array_merge(
                    $paths,
                    [
                        'status' => KycVerification::STATUS_PENDING,
                        'id_card_number_hash' => $hash,
                        'id_card_number_last4' => substr($digits, -4),
                        'full_name_th' => $validated['full_name_th'],
                        'full_name_en' => $validated['full_name_en'] ?? null,
                        'birth_date' => $validated['birth_date'],
                        'bank_code' => $validated['bank_code'],
                        'bank_account_number' => $validated['bank_account_number'],
                        'bank_account_name' => $validated['bank_account_name'],
                        'submitted_at' => now(),
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                        'attempts' => ($existing?->attempts ?? 0) + 1,
                        'history' => $history,
                    ]
                )
            );

            $user->forceFill(['kyc_status' => KycVerification::STATUS_PENDING])->save();
        });

        return redirect()
            ->route('kyc.index')
            ->with('success', 'ส่งคำขอยืนยันตัวตนเรียบร้อย ทีมงานจะตรวจสอบภายใน 1–3 วันทำการ');
    }

    /**
     * เสิร์ฟเอกสารจากดิสก์ส่วนตัว
     *
     * เจ้าของบัญชีดูของตัวเองได้ ผู้ดูแลระบบดูได้ทุกใบ คนอื่น 404 ไม่ใช่ 403 —
     * 403 ยืนยันให้คนที่เดา path ว่ามีไฟล์นั้นอยู่จริง
     */
    public function document(Request $request, int $id, string $kind): StreamedResponse
    {
        $kyc = KycVerification::findOrFail($id);
        $user = $request->user();
        $isOwner = $kyc->user_id === $user->id;
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : (bool) ($user->is_admin ?? false);

        abort_unless($isOwner || $isAdmin, 404);

        $column = match ($kind) {
            'front' => 'id_card_front_path',
            'back' => 'id_card_back_path',
            'selfie' => 'selfie_path',
            'bank' => 'bank_book_path',
            default => abort(404),
        };

        $path = $kyc->{$column};
        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            // เอกสารยืนยันตัวตนไม่ควรถูกแคชไว้ในเครื่องหรือตัวกลางใด ๆ
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /**
     * รับไฟล์ → เข้ารหัสใหม่ด้วย GD → เก็บบนดิสก์ส่วนตัว
     *
     * การเข้ารหัสใหม่ไม่ได้ทำเพื่อบีบขนาด แต่เพื่อ:
     *   - ตัด EXIF ทิ้ง — รูปจากมือถือมักมีพิกัด GPS ของบ้านคนถ่ายติดมาด้วย
     *   - ทำลายไฟล์ลูกผสม (รูปที่แนบสคริปต์ไว้ท้ายไฟล์) เพราะสิ่งที่เขียนลงดิสก์
     *     คือพิกเซลที่ GD วาดใหม่ ไม่ใช่ไบต์ที่ผู้ใช้ส่งมา
     */
    private function storeDocument(Request $request, string $field, int $userId): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false) {
            throw ValidationException::withMessages([$field => 'อ่านไฟล์ไม่สำเร็จ กรุณาลองใหม่']);
        }

        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            throw ValidationException::withMessages([$field => 'ไฟล์นี้ไม่ใช่รูปภาพที่อ่านได้']);
        }

        try {
            if (imagesx($image) < self::MIN_WIDTH || imagesy($image) < self::MIN_HEIGHT) {
                throw ValidationException::withMessages([
                    $field => 'รูปเล็กเกินไป กรุณาถ่ายให้ชัดขึ้น (อย่างน้อย ' . self::MIN_WIDTH . '×' . self::MIN_HEIGHT . ' พิกเซล)',
                ]);
            }

            ob_start();
            imagejpeg($image, null, 88);
            $encoded = ob_get_clean();
        } finally {
            imagedestroy($image);
        }

        if ($encoded === false || $encoded === '') {
            throw ValidationException::withMessages([$field => 'แปลงไฟล์ไม่สำเร็จ กรุณาลองใหม่']);
        }

        $path = sprintf('kyc/%d/%s.jpg', $userId, Str::uuid());
        Storage::disk('local')->put($path, $encoded);

        return $path;
    }

    /** @return array<string, string> */
    private function banks(): array
    {
        return [
            'KBANK' => 'กสิกรไทย',
            'SCB' => 'ไทยพาณิชย์',
            'BBL' => 'กรุงเทพ',
            'KTB' => 'กรุงไทย',
            'BAY' => 'กรุงศรีอยุธยา',
            'TTB' => 'ทหารไทยธนชาต',
            'GSB' => 'ออมสิน',
            'BAAC' => 'ธ.ก.ส.',
            'CIMB' => 'ซีไอเอ็มบี ไทย',
            'UOB' => 'ยูโอบี',
            'LHB' => 'แลนด์ แอนด์ เฮ้าส์',
            'TISCO' => 'ทิสโก้',
            'KKP' => 'เกียรตินาคินภัทร',
        ];
    }
}
