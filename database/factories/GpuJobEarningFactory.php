<?php

namespace Database\Factories;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * งานหนึ่งชิ้นที่ aixman บันทึกให้เครื่อง GPUxMINE — สำหรับเทสต์
 *
 * ค่าเริ่มต้นคือแถวแบบที่ aixman เขียน: pending เพิ่งเสร็จ ได้ ฿1.50 ส่งเครื่อง
 * ผ่าน forNode() เพื่อให้เจ้าของ worker_id และ gpu_node_id ตรงกับเครื่องนั้น
 * ใช้ matured() เมื่ออยากได้แถวที่พ้นระยะพักแล้ว
 *
 * @extends Factory<GpuJobEarning>
 */
class GpuJobEarningFactory extends Factory
{
    protected $model = GpuJobEarning::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        $jobId = 1_000 + ++self::$sequence;

        return [
            'gpu_node_id' => null,
            'user_id' => fn () => GpuNode::factory()->paired()->create()->user_id,
            'worker_id' => 'gxm-test' . $jobId,
            'job_id' => GpuJobEarning::JOB_ID_PREFIX . $jobId,
            'ai_gpu_job_id' => $jobId,
            'prompt_id' => fake()->uuid(),
            'kind' => 'image',
            'model_key' => 'sdxl-community',
            'lane' => 'full',
            'seconds' => 12,
            'amount_satang' => 150,
            'credits_charged' => 4,
            'thb_per_credit' => '0.500000',
            'revenue_satang' => 200,
            'platform_fee_rate' => '0.2500',
            'platform_fee_satang' => 50,
            'status' => GpuJobEarning::STATUS_PENDING,
            'completed_at' => now()->subMinutes(5),
        ];
    }

    public function forNode(GpuNode $node): static
    {
        return $this->state(fn () => [
            'gpu_node_id' => $node->id,
            'user_id' => $node->user_id,
            'worker_id' => $node->worker_id,
        ]);
    }

    /** เสร็จมานานกว่าระยะพักเริ่มต้น (24 ชั่วโมง) แล้ว */
    public function matured(): static
    {
        return $this->state(fn () => ['completed_at' => now()->subHours(25)]);
    }
}
