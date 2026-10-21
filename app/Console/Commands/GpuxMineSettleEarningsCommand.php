<?php

namespace App\Console\Commands;

use App\Services\GpuxMineEarningSettlementService;
use App\Services\GpuxMineHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ปล่อยรายได้ GPUxMINE ที่พ้นระยะพักแล้ว และโอนเข้ากระเป๋า XMAN ของเจ้าของเครื่อง
 *
 * ก่อนหน้านี้ไม่มีอะไรพาเงินจาก gpu_job_earnings ไปไหนเลย ทุกแถวจะค้างเป็น
 * "รอเข้ากระเป๋า" ไปตลอด และการจ่ายด้วยมือเสี่ยงทั้งจ่ายซ้ำและยอดคงเหลือเพี้ยน
 *
 * รันซ้ำกี่รอบก็ได้ผลเดิม: แถวที่จ่ายแล้วเป็น paid และไม่ถูกหยิบอีก เจ้าของคนหนึ่ง
 * ล้ม (เช่น ฐานข้อมูลสะดุด) ไม่ลากคนอื่นล้มตาม — ข้ามไปคนถัดไป รอบหน้าลองใหม่
 * รายละเอียดการกันจ่ายซ้ำอยู่ที่ GpuxMineEarningSettlementService
 */
class GpuxMineSettleEarningsCommand extends Command
{
    protected $signature = 'gpuxmine:settle-earnings';

    protected $description = 'ปล่อยรายได้ GPUxMINE ที่พ้นระยะพักแล้วโอนเข้ากระเป๋า XMAN ของเจ้าของเครื่อง';

    public function handle(GpuxMineEarningSettlementService $settlement, GpuxMineHealthService $health): int
    {
        // หลักฐานว่า cron เรียกเราจริง — gpuxmine:doctor ดูตรงนี้
        $health->beat(GpuxMineHealthService::TASK_SETTLE);

        $clearing = $settlement->clearMatured();

        $owners = 0;
        $skipped = 0;
        $rows = 0;
        $satang = 0;
        $commissions = 0;
        $failures = 0;

        foreach ($settlement->usersToPay() as $userId) {
            try {
                $result = $settlement->payUser($userId);
            } catch (Throwable $e) {
                $failures++;
                Log::error('[GPUxMINE] paying earnings into the wallet failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($result['skipped']) {
                $skipped++;

                continue;
            }

            if ($result['rows'] > 0) {
                $owners++;
            }
            $rows += $result['rows'];
            $satang += $result['satang'];
            $commissions += $result['commissions'];
        }

        $this->info(sprintf(
            'พ้นระยะพัก %d งาน · ส่งตรวจ %d · โอนเข้ากระเป๋า %d คน %d งาน ฿%s · ค่าแนะนำ %d รายการ · ข้าม %d · ล้ม %d',
            $clearing['cleared'],
            $clearing['review'],
            $owners,
            $rows,
            GpuxMineEarningSettlementService::baht($satang),
            $commissions,
            $skipped,
            $failures,
        ));

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
