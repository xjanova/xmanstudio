<?php

namespace App\Services;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * สิ่งที่แอดมินทำกับเครือข่าย GPUxMINE ได้: ระงับ ยกเลิกระงับ แบน ส่งข้อมูลซ้ำ และตัดสินรายได้
 *
 * ก่อนหน้านี้ไม่มีอะไรให้แอดมินกดเลย เครื่องที่ส่งผลงานปลอมหรือพังค้างอยู่ในคิวต่อไป
 * และรายได้ที่ติด "รอตรวจสอบ" ไม่มีทางออก
 *
 * ทุกการเปลี่ยนสถานะเป็น UPDATE แบบมีเงื่อนไขที่ชนะได้ครั้งเดียว — กดซ้ำ หรือแอดมินสองคน
 * กดพร้อมกัน ได้ผลเดียว ส่วนรายได้ที่ gpuxmine:settle-earnings กำลังจ่ายอยู่จะไม่ถูกยกเลิกทับ:
 * UPDATE ของเราต้องรอล็อกของรอบจ่ายก่อน แล้วจะเห็นว่าแถวเป็น paid ไปแล้วจึงไม่แตะ
 *
 * ฐานข้อมูลเป็นความจริง ส่วน relay กับ aixman ถูกแจ้งต่อแบบพยายามเต็มที่ แจ้งไม่ถึงตอนนี้
 * gpuxmine:sync-nodes ตามให้ในรอบถัดไป: aixman ได้ suspended ใหม่ (อยู่ในลายนิ้วมือ),
 * relay ถูกเปิด/ปิด worker ให้ตรงกับการระงับ (reconcileRelayGate — เฉพาะ relay รุ่นที่
 * บอก `disabled` ในรายชื่อ ซึ่งเป็นรุ่นเดียวกับที่มีคำสั่งนี้) และการถอนที่ค้างถูกลองซ้ำ
 */
class GpuxMineModerationService
{
    public function __construct(
        private readonly GpuxMineRelayService $relay,
        private readonly GpuxMineDispatchService $dispatch,
        private readonly GpuxMineNodeStateService $state,
        private readonly GpuxMineEarningSettlementService $settlement,
    ) {}

    /**
     * หยุดส่งงานให้เครื่องนี้ชั่วคราว — ถอยกลับได้ด้วย resume()
     *
     * aixman ถอดเครื่องออกทันทีแม้กำลังทำงานอยู่ (งานนั้นไปลองที่อื่น) relay ตัดสายของ
     * เครื่อง และรายได้ที่ยังไม่เข้ากระเป๋าของเครื่องนี้ถูกพักไว้จนกว่าจะยกเลิกระงับ
     * เหตุผลเจ้าของเครื่องได้อ่าน (หน้าเครื่องของฉัน และในโปรแกรมผ่าน /status)
     *
     * @return array{changed:bool, relay:?bool, aixman:?string}
     */
    public function suspend(GpuNode $node, User $admin, string $reason): array
    {
        $changed = GpuNode::withTrashed()
            ->whereKey($node->id)
            ->whereNull('suspended_at')
            ->update([
                'suspended_at' => now(),
                'suspended_reason' => $reason,
                'suspended_by' => $admin->id,
            ]) === 1;

        $node->refresh();

        if (! $changed) {
            return ['changed' => false, 'relay' => null, 'aixman' => null];
        }

        Log::info('[GPUxMINE] node suspended by admin', [
            'node_id' => $node->id,
            'worker_id' => $node->worker_id,
            'by' => $admin->id,
        ]);

        // aixman ก่อน: สิ่งแรกที่ต้องหยุดคือการส่งงานใหม่ แล้วค่อยตัดสายที่ relay
        $aixman = $this->pushIfLive($node);
        $relay = $this->relayIfLive($node, fn (string $id) => $this->relay->disableWorker($id));

        return ['changed' => true, 'relay' => $relay, 'aixman' => $aixman];
    }

    /**
     * ยกเลิกการระงับ — เครื่องที่ถูกแบนต้องยกเลิกแบนแทน
     *
     * @return array{changed:bool, relay:?bool, aixman:?string}
     */
    public function resume(GpuNode $node, User $admin): array
    {
        $changed = GpuNode::withTrashed()
            ->whereKey($node->id)
            ->whereNotNull('suspended_at')
            ->whereNull('banned_at')
            ->update([
                'suspended_at' => null,
                'suspended_reason' => null,
                'suspended_by' => null,
            ]) === 1;

        $node->refresh();

        if (! $changed) {
            return ['changed' => false, 'relay' => null, 'aixman' => null];
        }

        Log::info('[GPUxMINE] node resumed by admin', [
            'node_id' => $node->id,
            'worker_id' => $node->worker_id,
            'by' => $admin->id,
        ]);

        // relay ก่อน: aixman จะเคาะ /aixman/ready ผ่านอุโมงค์ ซึ่งต้องเปิดอยู่แล้ว
        $relay = $this->relayIfLive($node, fn (string $id) => $this->relay->enableWorker($id));
        $aixman = $this->pushIfLive($node);

        return ['changed' => true, 'relay' => $relay, 'aixman' => $aixman];
    }

    /**
     * แบน: ถอนเครื่องออกทั้งที่ aixman และ relay และห้ามจับคู่ใหม่
     *
     * ห้ามทั้งเครื่องนี้ (machine_id ในบัญชีไหนก็ตาม) และบัญชีเจ้าของ (เครื่องใหม่ทุกเครื่อง)
     * จนกว่าจะยกเลิกแบน เครื่องอื่นที่เจ้าของคนนี้แชร์อยู่แล้วยังทำงานต่อ — แอดมินระงับหรือ
     * แบนแยกทีละเครื่อง machine_id เป็นค่าที่ตัวเครื่องรายงานเอง คนที่แก้เครื่องแล้วมาด้วย
     * บัญชีใหม่หลบได้ (ดู GpuNode::machineIsBlocked) — ไม่ใช่การแบนที่หลบไม่ได้
     *
     * ระงับไปด้วย (ถ้ายังไม่ได้ระงับ) เพื่อพักรายได้ที่ยังไม่เข้ากระเป๋าของเครื่องนี้ไว้ให้
     * แอดมินตัดสินทีละรายการ แถวถูก soft delete เหมือนเจ้าของถอนเอง ประวัติยังอยู่
     *
     * retire_status บอกว่าการถอนค้างตรงไหน — RETIRE_AWAITING_RELAY คือ aixman ถอนแล้ว
     * แต่ relay รุ่นนี้ยังลบ worker ไม่ได้
     *
     * @return array{changed:bool, retired:bool, retire_status:?string}
     */
    public function ban(GpuNode $node, User $admin, string $reason): array
    {
        $changed = DB::transaction(function () use ($node, $admin, $reason) {
            $banned = GpuNode::withTrashed()
                ->whereKey($node->id)
                ->whereNull('banned_at')
                ->update([
                    'banned_at' => now(),
                    'banned_reason' => $reason,
                    'banned_by' => $admin->id,
                ]) === 1;

            if ($banned) {
                GpuNode::withTrashed()
                    ->whereKey($node->id)
                    ->whereNull('suspended_at')
                    ->update([
                        'suspended_at' => now(),
                        'suspended_reason' => $reason,
                        'suspended_by' => $admin->id,
                    ]);
            }

            return $banned;
        });

        $node->refresh();

        if (! $changed) {
            return [
                'changed' => false,
                'retired' => $node->retire_status === GpuNode::RETIRE_DONE,
                'retire_status' => $node->retire_status,
            ];
        }

        Log::warning('[GPUxMINE] node banned by admin', [
            'node_id' => $node->id,
            'user_id' => $node->user_id,
            'worker_id' => $node->worker_id,
            'machine_id' => $node->machine_id,
            'by' => $admin->id,
        ]);

        $retired = match (true) {
            // ถอนแล้ว soft delete — ถอนไม่ครบตอนนี้ แถวค้าง pending ให้ตัวจับเวลาลองต่อ
            ! $node->trashed() => $this->state->forget($node),
            $node->retire_status !== GpuNode::RETIRE_DONE => $this->state->retire($node),
            default => true,
        };

        return ['changed' => true, 'retired' => $retired, 'retire_status' => $node->retire_status];
    }

    /**
     * ยกเลิกแบน — ปลดการระงับของแถวนั้นด้วย
     *
     * worker เดิมถูกถอนไปแล้วและไม่ฟื้น เจ้าของต้องจับคู่ใหม่ถ้าจะแชร์ต่อ รายได้ที่ถูกพักไว้
     * ของเครื่องนี้จะเดินต่อในรอบปล่อยเงินถัดไป — ยกเลิกรายการที่ไม่ควรจ่ายก่อนกดปุ่มนี้
     */
    public function unban(GpuNode $node, User $admin): bool
    {
        $changed = GpuNode::withTrashed()
            ->whereKey($node->id)
            ->whereNotNull('banned_at')
            ->update([
                'banned_at' => null,
                'banned_reason' => null,
                'banned_by' => null,
                'suspended_at' => null,
                'suspended_reason' => null,
                'suspended_by' => null,
            ]) === 1;

        $node->refresh();

        if ($changed) {
            Log::warning('[GPUxMINE] node unbanned by admin', [
                'node_id' => $node->id,
                'user_id' => $node->user_id,
                'by' => $admin->id,
            ]);
        }

        return $changed;
    }

    /**
     * อ่านสถานะจาก relay ใหม่ ทำให้ประตูที่ relay ตรงกับการระงับ แล้วส่งให้ aixman ทันที
     * ไม่รอลายนิ้วมือหรือรอบเวลา
     *
     * gate: null = ประตูที่ relay ตรงอยู่แล้ว (หรือ relay รุ่นนี้ไม่บอก) · true = เพิ่งเปิด/ปิด
     * ให้ตรง · false = relay ไม่รับคำสั่ง
     *
     * เครื่องที่ถอนไปแล้ว: ไม่มีอะไรให้ส่ง แต่ถ้าการถอนยังค้าง ลองถอนให้อีกครั้งแทน
     *
     * @return array{kind:string, relay:?bool, gate:?bool, aixman:?string, retired:?bool, retire_status:?string}
     */
    public function resync(GpuNode $node): array
    {
        $result = ['kind' => 'push', 'relay' => null, 'gate' => null, 'aixman' => null, 'retired' => null, 'retire_status' => null];

        if ($node->trashed()) {
            if ($node->worker_id === null || $node->retire_status === GpuNode::RETIRE_DONE) {
                return ['kind' => 'removed'] + $result;
            }

            $retired = $this->state->retire($node);

            return ['kind' => 'retire', 'retired' => $retired, 'retire_status' => $node->retire_status] + $result;
        }

        if ($node->worker_id === null || $node->paired_at === null) {
            return ['kind' => 'unpaired'] + $result;
        }

        $workers = $this->relay->workers();
        $gate = null;
        if ($workers !== null) {
            $row = $workers[$node->worker_id] ?? null;
            $this->state->apply($node, $row);
            // เปิดประตูก่อนส่งให้ aixman — aixman จะเคาะ /aixman/ready ผ่านอุโมงค์ต่อทันที
            $gate = $this->state->reconcileRelayGate($node, $row);
        }

        return [
            'relay' => $workers !== null,
            'gate' => $gate,
            'aixman' => $this->dispatch->push($node),
        ] + $result;
    }

    /**
     * อนุมัติรายได้ที่ติด "รอตรวจสอบ"
     *
     * งานที่พ้นระยะพักแล้ว → cleared (โอนในรอบถัดไป) ยังไม่พ้น → pending (พักต่อจนครบ
     * เหมือนงานปกติ ช่วงพักคือช่วงที่ยังจับของปลอมได้ อนุมัติไม่ได้แปลว่าข้ามมันไป)
     * ยอดติดลบอนุมัติไม่ได้ — ยกเลิกเท่านั้น
     *
     * @return string|null สถานะใหม่ หรือ null ถ้าแถวไม่ได้อยู่ในสถานะที่อนุมัติได้แล้ว
     */
    public function approveEarning(GpuJobEarning $earning, User $admin): ?string
    {
        if (! $earning->canBeApproved()) {
            return null;
        }

        $holdEndsAt = $earning->holdEndsAt($this->settlement->holdHours());
        $target = $holdEndsAt === null || $holdEndsAt->lte(now())
            ? GpuJobEarning::STATUS_CLEARED
            : GpuJobEarning::STATUS_PENDING;

        $updated = GpuJobEarning::whereKey($earning->id)
            ->where('status', GpuJobEarning::STATUS_REVIEW)
            ->where('amount_satang', '>=', 0)
            ->update([
                'status' => $target,
                'cleared_at' => $target === GpuJobEarning::STATUS_CLEARED ? now() : null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

        if ($updated !== 1) {
            return null;
        }

        Log::info('[GPUxMINE] earning approved by admin', [
            'earning_id' => $earning->id,
            'to' => $target,
            'by' => $admin->id,
        ]);

        return $target;
    }

    /**
     * ยกเลิกรายได้หนึ่งงาน — ได้เฉพาะเงินที่ยังไม่เข้ากระเป๋า
     *
     * แถวที่จ่ายแล้วมีรายการในกระเป๋าผูกอยู่ ยกเลิกตรงนี้ไม่ได้ (ต้องปรับยอดกระเป๋าแยก)
     * ค่าแนะนำของงานเกิดตอนจ่ายเท่านั้น งานที่ถูกยกเลิกก่อนจ่ายจึงไม่มีค่าแนะนำตามไปด้วย
     */
    public function voidEarning(GpuJobEarning $earning, User $admin, string $reason): bool
    {
        $updated = GpuJobEarning::whereKey($earning->id)
            ->whereIn('status', GpuJobEarning::UNPAID_STATUSES)
            ->update([
                'status' => GpuJobEarning::STATUS_VOID,
                'void_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

        if ($updated === 1) {
            Log::warning('[GPUxMINE] earning voided by admin', [
                'earning_id' => $earning->id,
                'was' => $earning->status,
                'amount_satang' => $earning->amount_satang,
                'by' => $admin->id,
            ]);
        }

        return $updated === 1;
    }

    /** ส่งสถานะปัจจุบันให้ aixman — เฉพาะเครื่องที่ยังอยู่ในระบบ */
    private function pushIfLive(GpuNode $node): ?string
    {
        if ($node->trashed() || $node->worker_id === null || $node->paired_at === null) {
            return null;
        }

        return $this->dispatch->push($node);
    }

    /** @param  callable(string): bool  $action */
    private function relayIfLive(GpuNode $node, callable $action): ?bool
    {
        if ($node->trashed() || $node->worker_id === null) {
            return null;
        }

        return $action($node->worker_id);
    }
}
