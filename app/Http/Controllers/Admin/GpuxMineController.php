<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineEarningSettlementService;
use App\Services\GpuxMineHealthService;
use App\Services\GpuxMineModerationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * หน้าแอดมินของ GPUxMINE — ทุกเครื่องในเครือข่าย และรายได้ที่ต้องมีคนตัดสิน
 *
 * เดิมไม่มีหน้านี้เลย: เครื่องที่ส่งผลงานปลอมหรือพังค้างอยู่ในคิวต่อไป ไม่มีใครหยุดได้
 * และรายได้ที่ติด "รอตรวจสอบ" ไม่มีทางออก เครื่องที่เจ้าของถอนไปแล้วก็ยังต้องเห็นที่นี่
 * เพราะการถอนอาจยังค้าง และรายได้เก่าของมันยังต้องตามได้
 *
 * การเปลี่ยนสถานะทั้งหมดอยู่ใน GpuxMineModerationService — ที่นี่แค่ตรวจข้อมูลที่กรอก
 * แล้วบอกผลเป็นคำที่แอดมินเข้าใจ
 */
class GpuxMineController extends Controller
{
    private const NODES_PER_PAGE = 25;

    private const EARNINGS_PER_PAGE = 50;

    /** ตัวกรองรายการเครื่อง → คำบนแท็บ */
    public const NODE_FILTERS = [
        'all' => 'ทั้งหมด',
        'active' => 'ใช้งานอยู่',
        'online' => 'ออนไลน์',
        'problem' => 'มีปัญหา',
        'suspended' => 'ถูกระงับ',
        'banned' => 'ถูกแบน',
        'removed' => 'ถอนแล้ว',
        'retiring' => 'ถอนยังไม่เสร็จ',
    ];

    public function __construct(
        private readonly GpuxMineModerationService $moderation,
        private readonly GpuxMineHealthService $health,
        private readonly GpuxMineEarningSettlementService $settlement,
    ) {}

    public function index(Request $request): View
    {
        $filter = $this->queryWord($request, 'state');
        $filter = array_key_exists($filter, self::NODE_FILTERS) ? $filter : 'all';
        $search = $this->queryWord($request, 'q');

        $nodes = $this->filteredNodes($filter, $search)
            ->with(['user:id,name,email', 'referrer:id,name,email'])
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('online')
            ->orderByDesc('paired_at')
            ->orderByDesc('id')
            ->paginate(self::NODES_PER_PAGE)
            ->withQueryString();

        $network = GpuNode::withTrashed()->whereNotNull('worker_id');

        return view('admin.gpuxmine.index', [
            'nodes' => $nodes,
            'filter' => $filter,
            'search' => $search,
            'nodeTotals' => $this->totalsFor($nodes->getCollection()),
            'counts' => [
                'active' => (clone $network)->whereNull('deleted_at')->count(),
                'online' => (clone $network)->whereNull('deleted_at')->where('online', true)->count(),
                'eligible' => (clone $network)->whereNull('deleted_at')->where('online', true)
                    ->where('dispatch_status', 'eligible')->whereNull('suspended_at')->count(),
                'suspended' => (clone $network)->whereNotNull('suspended_at')->whereNull('banned_at')->count(),
                'banned' => (clone $network)->whereNotNull('banned_at')->count(),
                'retiring' => (clone $network)->where('retire_status', GpuNode::RETIRE_PENDING)->count(),
            ],
            'money' => $this->moneyByStatus(GpuJobEarning::query()),
            'health' => $this->healthSummary(),
        ]);
    }

    public function show(int $id): View
    {
        $node = GpuNode::withTrashed()
            ->with(['user:id,name,email', 'referrer:id,name,email', 'suspendedBy:id,name', 'bannedBy:id,name'])
            ->findOrFail($id);

        $earnings = $this->earningsOf($node)
            ->with(['reviewer:id,name'])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(self::EARNINGS_PER_PAGE)
            ->withQueryString();

        // เครื่องอื่นของเจ้าของคนนี้ และแถวอื่นของเครื่องนี้ (machine_id เดียวกัน อาจอยู่คนละบัญชี)
        $siblings = GpuNode::withTrashed()
            ->whereKeyNot($node->id)
            ->whereNotNull('worker_id')
            ->where(function (Builder $q) use ($node) {
                $q->where('user_id', $node->user_id);
                if ($node->machine_id) {
                    $q->orWhere('machine_id', $node->machine_id);
                }
            })
            ->with('user:id,name,email')
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('paired_at')
            ->limit(20)
            ->get();

        return view('admin.gpuxmine.show', [
            'node' => $node,
            'earnings' => $earnings,
            'money' => $this->moneyByStatus($this->earningsOf($node)),
            'siblings' => $siblings,
            'holdHours' => $this->settlement->holdHours(),
            'ownerBanned' => $node->user_id ? GpuNode::ownerIsBanned((int) $node->user_id) : false,
        ]);
    }

    public function earnings(Request $request): View
    {
        $status = $this->queryWord($request, 'status');
        $status = in_array($status, array_merge(['all'], GpuJobEarning::STATUSES), true) ? $status : GpuJobEarning::STATUS_REVIEW;
        $search = $this->queryWord($request, 'q');

        $earnings = GpuJobEarning::query()
            ->with(['node' => fn ($q) => $q->withTrashed(), 'user:id,name,email', 'reviewer:id,name'])
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(function (Builder $q) use ($like) {
                    $q->where('job_id', 'like', $like)
                        ->orWhere('prompt_id', 'like', $like)
                        ->orWhere('worker_id', 'like', $like)
                        ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', $like)->orWhere('name', 'like', $like));
                });
            })
            // คิวตรวจ: รอนานสุดขึ้นก่อน — ที่อื่นใหม่สุดก่อน
            ->when(
                $status === GpuJobEarning::STATUS_REVIEW,
                fn (Builder $q) => $q->orderBy('completed_at')->orderBy('id'),
                fn (Builder $q) => $q->orderByDesc('completed_at')->orderByDesc('id'),
            )
            ->paginate(self::EARNINGS_PER_PAGE)
            ->withQueryString();

        return view('admin.gpuxmine.earnings', [
            'earnings' => $earnings,
            'status' => $status,
            'search' => $search,
            'money' => $this->moneyByStatus(GpuJobEarning::query()),
            'holdHours' => $this->settlement->holdHours(),
        ]);
    }

    public function suspend(Request $request, int $id): RedirectResponse
    {
        $reason = $this->reason($request);
        $node = GpuNode::withTrashed()->findOrFail($id);

        if ($node->isBanned()) {
            return back()->with('error', 'เครื่องนี้ถูกแบนอยู่แล้ว — การแบนระงับเครื่องไปด้วย');
        }

        $result = $this->moderation->suspend($node, $request->user(), $reason);

        if (! $result['changed']) {
            return back()->with('error', 'เครื่องนี้ถูกระงับอยู่แล้ว');
        }

        return back()->with('success', 'ระงับ ' . $node->displayName() . ' แล้ว — ไม่มีงานส่งมาอีก และรายได้ที่ยังไม่เข้ากระเป๋าถูกพักไว้'
            . $this->aftermath($result));
    }

    public function resume(Request $request, int $id): RedirectResponse
    {
        $node = GpuNode::withTrashed()->findOrFail($id);

        if ($node->isBanned()) {
            return back()->with('error', 'เครื่องนี้ถูกแบน — ใช้ปุ่มยกเลิกแบนแทน');
        }

        $result = $this->moderation->resume($node, $request->user());

        if (! $result['changed']) {
            return back()->with('error', 'เครื่องนี้ไม่ได้ถูกระงับอยู่');
        }

        $message = $node->trashed()
            ? 'ยกเลิกระงับแล้ว — เครื่องนี้ถูกถอนไปแล้ว เจ้าของจับคู่เครื่องนี้ใหม่ได้ และรายได้ที่ค้างจะเดินต่อในรอบถัดไป'
            : 'ยกเลิกระงับ ' . $node->displayName() . ' แล้ว — เครื่องกลับเข้าคิวเมื่อโปรแกรมต่อ relay ใหม่ และรายได้ที่ค้างจะเดินต่อ';

        return back()->with('success', $message . $this->aftermath($result));
    }

    public function ban(Request $request, int $id): RedirectResponse
    {
        $reason = $this->reason($request);
        $node = GpuNode::withTrashed()->findOrFail($id);

        $result = $this->moderation->ban($node, $request->user(), $reason);

        if (! $result['changed']) {
            return back()->with('error', 'เครื่องนี้ถูกแบนอยู่แล้ว');
        }

        return back()->with('success', 'แบน ' . $node->displayName() . ' แล้ว — ถอนออกจากระบบ และห้ามจับคู่ใหม่ทั้งเครื่องนี้และบัญชีเจ้าของ'
            . ($result['retired'] ? '' : ' · ถอนที่ aixman/relay ยังไม่ครบ ระบบลองซ้ำเองทุกนาที'));
    }

    public function unban(Request $request, int $id): RedirectResponse
    {
        $node = GpuNode::withTrashed()->findOrFail($id);

        if (! $this->moderation->unban($node, $request->user())) {
            return back()->with('error', 'เครื่องนี้ไม่ได้ถูกแบนอยู่');
        }

        return back()->with('success', 'ยกเลิกแบนแล้ว — เจ้าของจับคู่ใหม่ได้ (worker เดิมไม่ฟื้น) และรายได้ที่ค้างของเครื่องนี้จะเดินต่อในรอบถัดไป');
    }

    public function resync(int $id): RedirectResponse
    {
        $node = GpuNode::withTrashed()->findOrFail($id);
        $result = $this->moderation->resync($node);

        return match ($result['kind']) {
            'removed' => back()->with('error', 'เครื่องนี้ถูกถอนเรียบร้อยแล้ว — ไม่มีอะไรให้ส่ง'),
            'unpaired' => back()->with('error', 'เครื่องนี้ยังไม่ได้จับคู่กับ relay'),
            'retire' => $result['retired']
                ? back()->with('success', 'ถอน worker ที่ค้างอยู่สำเร็จแล้วทั้งที่ aixman และ relay')
                : back()->with('error', 'ยังถอนไม่สำเร็จ — aixman หรือ relay ยังไม่ตอบรับ ระบบลองซ้ำเองทุกนาที'),
            default => $result['aixman'] === GpuxMineDispatchService::OUTCOME_OK
                ? back()->with('success', 'ส่งข้อมูลให้ aixman แล้ว · ผล: ' . ($node->fresh()?->dispatch_status ?? '—')
                    . ($result['relay'] ? '' : ' · อ่านสถานะจาก relay ไม่ได้ ใช้สถานะล่าสุดที่รู้'))
                : back()->with('error', 'ส่งให้ aixman ไม่สำเร็จ (' . ($node->fresh()?->dispatch_note ?? $result['aixman']) . ') — ระบบลองซ้ำเองทุกนาที'),
        };
    }

    public function approveEarning(Request $request, int $id): RedirectResponse
    {
        $earning = GpuJobEarning::findOrFail($id);

        if ($earning->status !== GpuJobEarning::STATUS_REVIEW) {
            return back()->with('error', 'งานนี้ไม่ได้อยู่ในสถานะรอตรวจสอบแล้ว (' . $earning->statusLabel() . ')');
        }
        if ($earning->amount_satang < 0) {
            return back()->with('error', 'ยอดติดลบอนุมัติไม่ได้ — ยกเลิกรายการนี้แทน');
        }

        $to = $this->moderation->approveEarning($earning, $request->user());

        return match ($to) {
            GpuJobEarning::STATUS_CLEARED => back()->with('success', 'อนุมัติ ' . $earning->job_id . ' แล้ว — พ้นระยะพักแล้ว จะเข้ากระเป๋าเจ้าของในรอบโอนถัดไป'),
            GpuJobEarning::STATUS_PENDING => back()->with('success', 'อนุมัติ ' . $earning->job_id . ' แล้ว — ยังอยู่ในระยะพัก จะเข้ากระเป๋าเมื่อพ้น ' . $this->settlement->holdHours() . ' ชม. นับจากงานเสร็จ'),
            default => back()->with('error', 'สถานะของงานนี้เพิ่งเปลี่ยน — โหลดหน้าใหม่แล้วดูอีกครั้ง'),
        };
    }

    public function voidEarning(Request $request, int $id): RedirectResponse
    {
        $reason = $this->reason($request);
        $earning = GpuJobEarning::findOrFail($id);

        if (! $earning->canBeVoided()) {
            return back()->with('error', $earning->status === GpuJobEarning::STATUS_PAID
                ? 'งานนี้เข้ากระเป๋าไปแล้ว ยกเลิกไม่ได้ — ถ้าต้องเรียกคืน ให้ปรับยอดกระเป๋าของเจ้าของแยกต่างหาก'
                : 'งานนี้ถูกยกเลิกไปแล้ว');
        }

        if (! $this->moderation->voidEarning($earning, $request->user(), $reason)) {
            return back()->with('error', 'สถานะของงานนี้เพิ่งเปลี่ยน (อาจเพิ่งเข้ากระเป๋า) — โหลดหน้าใหม่แล้วดูอีกครั้ง');
        }

        return back()->with('success', 'ยกเลิกรายได้ ' . $earning->job_id . ' (฿' . GpuxMineEarningSettlementService::baht((int) $earning->amount_satang) . ') แล้ว — ไม่จ่ายรายการนี้');
    }

    /** ค่าจาก query string ที่เป็นข้อความสั้น ๆ — ส่ง ?q[]= มาก็ได้แค่สตริงว่าง ไม่ใช่ 500 */
    private function queryWord(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
    }

    /** เหตุผลที่แอดมินกรอก — เจ้าของเครื่องได้อ่าน จึงบังคับให้มีความหมาย */
    private function reason(Request $request): string
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผล — เจ้าของเครื่องจะเห็นข้อความนี้',
            'reason.min' => 'เหตุผลสั้นเกินไป (อย่างน้อย 3 ตัวอักษร)',
            'reason.max' => 'เหตุผลยาวเกิน 255 ตัวอักษร',
        ]);

        return trim($validated['reason']);
    }

    /**
     * สิ่งที่ยังไม่ครบหลังเปลี่ยนสถานะ — ฐานข้อมูลเปลี่ยนแล้ว ส่วนที่ขาดตัวจับเวลาตามให้
     *
     * @param  array{relay:?bool, aixman:?string}  $result
     */
    private function aftermath(array $result): string
    {
        $notes = [];

        if ($result['aixman'] === GpuxMineDispatchService::OUTCOME_UNCONFIGURED) {
            $notes[] = 'ยังไม่ได้ตั้งค่า aixman';
        } elseif ($result['aixman'] !== null
            && ! in_array($result['aixman'], [GpuxMineDispatchService::OUTCOME_OK, GpuxMineDispatchService::OUTCOME_SKIPPED], true)) {
            $notes[] = 'แจ้ง aixman ไม่สำเร็จตอนนี้ ระบบส่งซ้ำเองทุกนาที';
        }

        if ($result['relay'] === false) {
            $notes[] = 'relay ไม่รับคำสั่ง (relay รุ่นเก่าอาจยังไม่มีคำสั่งนี้)';
        }

        return $notes === [] ? '' : ' · ' . implode(' · ', $notes);
    }

    private function filteredNodes(string $filter, string $search): Builder
    {
        $query = GpuNode::withTrashed()->whereNotNull('worker_id');

        match ($filter) {
            'active' => $query->whereNull('deleted_at'),
            'online' => $query->whereNull('deleted_at')->where('online', true),
            'problem' => $query->whereNull('deleted_at')->where(fn (Builder $q) => $q
                ->where('dispatch_status', 'error')
                ->orWhereNotNull('dispatch_last_error')
                ->orWhereIn('dispatch_worker_status', ['terminated', 'failed'])),
            'suspended' => $query->whereNotNull('suspended_at')->whereNull('banned_at'),
            'banned' => $query->whereNotNull('banned_at'),
            'removed' => $query->whereNotNull('deleted_at'),
            'retiring' => $query->where('retire_status', GpuNode::RETIRE_PENDING),
            default => null,
        };

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('worker_id', 'like', $like)
                    ->orWhere('machine_id', 'like', $like)
                    ->orWhere('label', 'like', $like)
                    ->orWhere('gpu_name', 'like', $like)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', $like)->orWhere('name', 'like', $like));
            });
        }

        return $query;
    }

    /**
     * รายได้ของเครื่องหนึ่งเครื่อง — จับทั้ง gpu_node_id และแถวที่ aixman เขียนโดยไม่มี
     * gpu_node_id แต่ worker ตรงกัน
     */
    private function earningsOf(GpuNode $node): Builder
    {
        return GpuJobEarning::query()->where(function (Builder $q) use ($node) {
            $q->where('gpu_node_id', $node->id);
            if ($node->worker_id) {
                $q->orWhere(fn (Builder $q) => $q->whereNull('gpu_node_id')->where('worker_id', $node->worker_id));
            }
        });
    }

    /**
     * ยอดรายได้ต่อเครื่องต่อสถานะ สำหรับเครื่องในหน้านี้เท่านั้น — สองคำสั่งรวม ไม่ใช่หนึ่งคำสั่งต่อแถว
     *
     * @param  Collection<int, GpuNode>  $nodes
     * @return array<int, array<string, array{jobs:int, satang:int}>>
     */
    private function totalsFor(Collection $nodes): array
    {
        if ($nodes->isEmpty()) {
            return [];
        }

        $totals = [];
        $nodeByWorker = $nodes->filter(fn (GpuNode $n) => $n->worker_id !== null)->keyBy('worker_id');

        $add = function (?int $nodeId, string $status, int $jobs, int $satang) use (&$totals) {
            if ($nodeId === null) {
                return;
            }
            $totals[$nodeId][$status]['jobs'] = ($totals[$nodeId][$status]['jobs'] ?? 0) + $jobs;
            $totals[$nodeId][$status]['satang'] = ($totals[$nodeId][$status]['satang'] ?? 0) + $satang;
        };

        GpuJobEarning::whereIn('gpu_node_id', $nodes->modelKeys())
            ->selectRaw('gpu_node_id, status, COUNT(*) as jobs, COALESCE(SUM(amount_satang), 0) as satang')
            ->groupBy('gpu_node_id', 'status')
            ->toBase()
            ->get()
            ->each(fn ($row) => $add((int) $row->gpu_node_id, (string) $row->status, (int) $row->jobs, (int) $row->satang));

        if ($nodeByWorker->isNotEmpty()) {
            GpuJobEarning::whereNull('gpu_node_id')
                ->whereIn('worker_id', $nodeByWorker->keys()->all())
                ->selectRaw('worker_id, status, COUNT(*) as jobs, COALESCE(SUM(amount_satang), 0) as satang')
                ->groupBy('worker_id', 'status')
                ->toBase()
                ->get()
                ->each(fn ($row) => $add($nodeByWorker->get($row->worker_id)?->id, (string) $row->status, (int) $row->jobs, (int) $row->satang));
        }

        return $totals;
    }

    /**
     * @return array<string, array{jobs:int, satang:int, donated:int, referral:int}>
     */
    private function moneyByStatus(Builder $query): array
    {
        $rows = (clone $query)
            ->selectRaw('status, COUNT(*) as jobs, COALESCE(SUM(amount_satang), 0) as satang, '
                . 'COALESCE(SUM(donated_value_satang), 0) as donated, COALESCE(SUM(referral_satang), 0) as referral')
            ->groupBy('status')
            ->toBase()
            ->get()
            ->keyBy('status');

        $money = [];
        foreach (GpuJobEarning::STATUSES as $status) {
            $row = $rows->get($status);
            $money[$status] = [
                'jobs' => (int) ($row->jobs ?? 0),
                'satang' => (int) ($row->satang ?? 0),
                'donated' => (int) ($row->donated ?? 0),
                'referral' => (int) ($row->referral ?? 0),
            ];
        }

        return $money;
    }

    /** ส่วนที่ตรวจได้ถูก ๆ จากฐานข้อมูลกับแคช — การถาม relay/aixman อยู่ใน gpuxmine:doctor */
    private function healthSummary(): array
    {
        return [
            'sync' => $this->health->lastBeat(GpuxMineHealthService::TASK_SYNC),
            'settle' => $this->health->lastBeat(GpuxMineHealthService::TASK_SETTLE),
            'stuck' => $this->health->stuckPending(),
            'review' => $this->health->awaitingReview(),
            'retiring' => $this->health->staleRetirements(),
            'holdHours' => $this->settlement->holdHours(),
        ];
    }
}
