<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\Product;
use App\Services\GpuxMineNodeStateService;
use App\Services\GpuxMineReferrerResolver;
use App\Services\GpuxMineRelayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * หน้า "เครื่องของฉัน" ของ GPUxMINE
 *
 * ที่เดียวที่เจ้าของเครื่องเห็นภาพรวมของสิ่งที่ตัวเองแชร์อยู่ และที่เดียวที่
 * ออกรหัสจับคู่ได้ — โปรแกรมไคลเอนต์ไม่มีทางสร้างตัวตนของตัวเองได้เลย
 * นอกจากผ่านรหัสจากหน้านี้
 */
class GpuNodeController extends Controller
{
    use ServesReleaseDownloads;

    /** ประวัติการรับเงินต่อหน้า — เจ้าของที่ทำงานมาเป็นปีต้องย้อนดูได้ทุกรายการ */
    private const EARNINGS_PER_PAGE = 25;

    public function __construct(
        private readonly GpuxMineRelayService $relay,
        private readonly GpuxMineNodeStateService $state,
        private readonly GpuxMineReferrerResolver $referrers,
    ) {}

    public function index(Request $request): View
    {
        $nodes = GpuNode::where('user_id', Auth::id())
            ->orderByDesc('paired_at')
            ->orderByDesc('created_at')
            ->get();

        // ถามสถานะจาก relay ตอนเปิดหน้า (แคชไว้สิบห้าวินาที) — ตัวจับเวลา
        // เบื้องหลังวิ่งทุกนาที แต่คนที่เพิ่งกด START ในโปรแกรมแล้วสลับมาดู
        // หน้านี้ ควรเห็นผลเกือบทันที ไม่ใช่รอรอบถัดไป
        $this->refreshFromRelay($nodes);

        // ประวัติการรับเงิน — เจ้าของเครื่องยอมให้เราใช้การ์ดของเขา
        // อย่างน้อยที่สุดเขาต้องเห็นได้ว่ามันทำงานไปกี่ชิ้นและได้เท่าไร
        // เครื่องที่ถอนไปแล้วยังต้องขึ้นชื่อ ไม่ใช่กลายเป็นรหัส worker
        $earnings = GpuJobEarning::where('user_id', Auth::id())
            ->with(['node' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(self::EARNINGS_PER_PAGE)
            ->withQueryString();

        $totals = GpuJobEarning::where('user_id', Auth::id())
            ->selectRaw('status, COUNT(*) as jobs, COALESCE(SUM(amount_satang), 0) as satang')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('gpuxmine.index', [
            'nodes' => $nodes,
            'pending' => $nodes->firstWhere(fn (GpuNode $n) => $n->pairingIsUsable()),
            'relayReady' => $this->relay->isConfigured(),
            // ตัวติดตั้งส่งจาก xman4289.com เอง — ลิงก์ GitHub บอกลูกค้าว่า repo อยู่ไหน (กฎเจ้าของ 2026-09-24)
            'downloadUrl' => route('gpuxmine.download'),
            'earnings' => $earnings,
            'paidSatang' => (int) ($totals['paid']->satang ?? 0),
            'pendingSatang' => (int) ($totals['pending']->satang ?? 0),
            'jobsTotal' => (int) $totals->sum('jobs'),
        ]);
    }

    /**
     * ตัวติดตั้งโปรแกรมรุ่นล่าสุด (แจกฟรี — ที่ขายคือ Pro Miner ซึ่งเป็น license บนสินค้าตัวเดียวกัน)
     * ไฟล์ไหนคือตัวติดตั้งกำหนดที่ asset_pattern ของ GitHub setting (Setup.exe ของ Velopack)
     */
    public function download(Request $request): Response
    {
        $product = Product::where('slug', 'gpuxmine')->where('is_active', true)->first();

        if (! $product) {
            return $this->downloadUnavailable($request, route('gpuxmine.index'), 404, 'Product not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        return $this->serveRelease($request, $product, $this->latestRelease($product), route('gpuxmine.index'));
    }

    /**
     * ออกรหัสจับคู่ใหม่
     *
     * แถวถูกสร้างตั้งแต่ตอนนี้ ทั้งที่ยังไม่รู้ว่าเครื่องไหนจะมาอ้าง เพราะ
     * รหัสต้องผูกกับเจ้าของตั้งแต่วินาทีที่ออก — ถ้าไปผูกตอนแลก โปรแกรมจะ
     * ต้องบอกเองว่าเป็นของใคร ซึ่งเป็นสิ่งที่มันพิสูจน์ไม่ได้
     */
    public function pair(Request $request): RedirectResponse
    {
        if (! $this->relay->isConfigured()) {
            return back()->with('error', 'ระบบรับเครื่องยังไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแล');
        }

        $userId = (int) Auth::id();

        // เพดานเครื่องต่อบัญชี (D9) — ตรวจซ้ำอีกครั้งตอนโปรแกรมมาแลกรหัส
        // เพราะนั่นคือจุดที่ worker ใหม่เกิดขึ้นจริง
        $cap = (int) config('services.gpuxmine.max_nodes_per_user', 10);
        if ($cap > 0 && GpuNode::where('user_id', $userId)->whereNotNull('paired_at')->count() >= $cap) {
            return back()->with('error', "บัญชีนี้ลงทะเบียนเครื่องครบ {$cap} เครื่องแล้ว — ถอนเครื่องที่ไม่ได้ใช้ออกก่อน แล้วค่อยขอรหัสจับคู่ใหม่");
        }

        // รหัสที่ออกค้างไว้และยังไม่ได้ใช้ ถือว่าถูกแทนที่ — ไม่งั้นกดหลายครั้ง
        // แล้วมีรหัสใช้ได้ค้างอยู่หลายตัวพร้อมกัน
        GpuNode::where('user_id', $userId)
            ->whereNull('paired_at')
            ->delete();

        $node = GpuNode::create([
            'user_id' => $userId,
            // ผู้แนะนำจับตอนนี้ ตอนที่ยังมี session/cookie ของลิงก์แนะนำให้อ่าน —
            // ตอนโปรแกรมมาแลกรหัสไม่มีทั้งสองอย่าง (D8)
            'referrer_user_id' => $this->referrers->resolve($userId),
            'pairing_code' => GpuNode::newPairingCode(),
            'pairing_expires_at' => now()->addMinutes(GpuNode::PAIRING_TTL_MINUTES),
        ]);

        return back()->with('pairing_code', $node->pairing_code);
    }

    public function rename(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate(['label' => 'required|string|max:60']);

        $node = $this->ownedNode($id);
        $node->update(['label' => $validated['label']]);

        return back()->with('success', 'เปลี่ยนชื่อเครื่องแล้ว');
    }

    /**
     * ถอนเครื่องออกจากระบบ
     *
     * หยุดส่งงานมาที่เครื่องนี้ และเพิกถอนกุญแจที่ relay ไม่ได้ไปยุ่งอะไรกับ
     * คอมของเจ้าของ — โปรแกรมจะยังเปิดอยู่ได้ เพียงแต่ไม่มีงานเข้าอีก ประวัติ
     * เดิมยังอยู่ (soft delete) เพราะยอดที่ค้างจ่ายต้องยังตามได้
     *
     * ถ้า aixman หรือ relay ไม่ตอบตอนนี้ แถวจะค้างสถานะรอถอนไว้ และ
     * gpuxmine:sync-nodes ลองถอนให้ใหม่ทุกรอบจนสำเร็จ
     */
    public function forget(Request $request, int $id): RedirectResponse
    {
        $node = $this->ownedNode($id);

        $this->state->forget($node);

        return back()->with('success', 'ถอนเครื่องออกจากระบบแล้ว — ยอดที่ค้างจ่ายยังอยู่ตามเดิม');
    }

    private function ownedNode(int $id): GpuNode
    {
        return GpuNode::where('user_id', Auth::id())->findOrFail($id);
    }

    /**
     * รวมสถานะจาก relay ลงในแถวที่กำลังจะแสดง แล้วส่งต่อให้ aixman ถ้าจำเป็น
     *
     * ใช้ตัวรวมเดียวกับ gpuxmine:sync-nodes — หน้านี้เคยเขียนแถวเองโดยไม่
     * ส่งต่อ แล้วตัวจับเวลาก็ไม่เห็นอะไรเปลี่ยนอีก เครื่องที่เจ้าของเปิดหน้า
     * ดูจึงไม่เคยไปถึง aixman ตอนนี้การส่งต่อตัดสินจากสิ่งที่ aixman ตอบรับ
     * ไปล่าสุด หน้านี้จะเขียนแถวก่อนกี่รอบก็กลืนการส่งไม่ได้แล้ว
     *
     * relay ตอบไม่ได้ = แสดงสถานะล่าสุดที่รู้ ไม่เขียนอะไรทับ
     */
    private function refreshFromRelay($nodes): void
    {
        $paired = $nodes->filter(fn (GpuNode $n) => $n->worker_id !== null && $n->paired_at !== null);
        if ($paired->isEmpty()) {
            return;
        }

        $snapshot = $this->relay->workersSnapshot();
        if ($snapshot === null) {
            return;
        }

        foreach ($paired as $node) {
            // ภาพจากแคชเก่ากว่าแถวที่ตัวจับเวลาเพิ่งเขียน — อย่าเอาของเก่าไปทับ
            if ($node->updated_at === null || $node->updated_at->getTimestamp() <= $snapshot['fetchedAt']) {
                $this->state->apply($node, $snapshot['workers'][$node->worker_id] ?? null);
            }

            if ($this->state->needsPush($node)) {
                $this->state->pushAfterResponse($node);
            }
        }
    }
}
