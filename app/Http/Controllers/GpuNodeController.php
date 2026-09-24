<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\Product;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineRelayService;
use Carbon\Carbon;
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

    public function __construct(
        private readonly GpuxMineRelayService $relay,
        private readonly GpuxMineDispatchService $dispatch,
    ) {}

    public function index(Request $request): View
    {
        $nodes = GpuNode::where('user_id', Auth::id())
            ->orderByDesc('paired_at')
            ->orderByDesc('created_at')
            ->get();

        // ถามสถานะสดจาก relay ทุกครั้งที่เปิดหน้า — ตัวจับเวลาเบื้องหลังวิ่ง
        // ทุกนาที แต่คนที่เพิ่งกด START ในโปรแกรมแล้วสลับมาดูหน้านี้ ควรเห็น
        // ผลทันที ไม่ใช่รอรอบถัดไป
        $this->refreshFromRelay($nodes);

        // ประวัติการรับเงิน — เจ้าของเครื่องยอมให้เราใช้การ์ดของเขา
        // อย่างน้อยที่สุดเขาต้องเห็นได้ว่ามันทำงานไปกี่ชิ้นและได้เท่าไร
        $earnings = GpuJobEarning::where('user_id', Auth::id())
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

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

        $userId = Auth::id();

        // รหัสที่ออกค้างไว้และยังไม่ได้ใช้ ถือว่าถูกแทนที่ — ไม่งั้นกดหลายครั้ง
        // แล้วมีรหัสใช้ได้ค้างอยู่หลายตัวพร้อมกัน
        GpuNode::where('user_id', $userId)
            ->whereNull('paired_at')
            ->delete();

        $node = GpuNode::create([
            'user_id' => $userId,
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
     * หยุดส่งงานมาที่เครื่องนี้ ไม่ได้ไปยุ่งอะไรกับคอมของเจ้าของ — โปรแกรมจะ
     * ยังเปิดอยู่ได้ เพียงแต่ไม่มีงานเข้าอีก ประวัติเดิมยังอยู่ (soft delete)
     * เพราะยอดที่ค้างจ่ายต้องยังตามได้
     */
    public function forget(Request $request, int $id): RedirectResponse
    {
        $node = $this->ownedNode($id);

        $this->dispatch->retire($node);
        $node->delete();

        return back()->with('success', 'ถอนเครื่องออกจากระบบแล้ว — ยอดที่ค้างจ่ายยังอยู่ตามเดิม');
    }

    private function ownedNode(int $id): GpuNode
    {
        return GpuNode::where('user_id', Auth::id())->findOrFail($id);
    }

    /**
     * รวมสถานะจาก relay ลงในแถวที่กำลังจะแสดง
     *
     * relay เป็นคนเดียวที่รู้ว่าเครื่องต่ออยู่จริงไหมและวัดตัวเองได้เท่าไร
     * ค่าพวกนี้เปลี่ยนทุกไม่กี่วินาที จึงเขียนกลับลงฐานข้อมูลเฉพาะเมื่อ
     * เปลี่ยนจริง — ไม่งั้นทุกการเปิดหน้าเว็บคือการเขียนดิสก์ครั้งหนึ่ง
     */
    private function refreshFromRelay($nodes): void
    {
        $paired = $nodes->filter(fn (GpuNode $n) => $n->worker_id !== null);
        if ($paired->isEmpty()) {
            return;
        }

        $live = $this->relay->workers();
        if ($live === []) {
            return;
        }

        foreach ($paired as $node) {
            $row = $live[$node->worker_id] ?? null;
            if ($row === null) {
                continue;
            }

            $telemetry = is_array($row['telemetry'] ?? null) ? $row['telemetry'] : [];

            $fresh = [
                'online' => (bool) ($row['online'] ?? false),
                'agent_version' => $row['agentVersion'] ?? $node->agent_version,
                'last_seen_at' => ! empty($row['lastSeenAt']) ? Carbon::parse($row['lastSeenAt']) : $node->last_seen_at,
                'assessed' => (bool) ($telemetry['assessed'] ?? false),
                'score' => (int) ($telemetry['score'] ?? 0),
                'tier' => (string) ($telemetry['tier'] ?? 'unrated'),
                'gpu_name' => $telemetry['gpuName'] ?? $node->gpu_name,
                'vram_total_mb' => (int) ($telemetry['vramTotalMb'] ?? $node->vram_total_mb),
                'can_run' => $telemetry['canRun'] ?? $node->can_run,
                'free_share_pct' => (int) ($telemetry['freeSharePct'] ?? 0),
            ];

            $node->forceFill($fresh);
            if ($node->isDirty()) {
                $node->save();
            }
        }
    }
}
