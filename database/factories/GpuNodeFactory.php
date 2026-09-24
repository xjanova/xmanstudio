<?php

namespace Database\Factories;

use App\Models\GpuNode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * เครื่อง GPUxMINE สำหรับเทสต์
 *
 * ค่าเริ่มต้นคือแถวที่เพิ่งออกรหัสจับคู่ (ยังไม่มีตัวตนบน relay) — ใช้
 * paired() เมื่ออยากได้เครื่องที่จับคู่เสร็จแล้ว และ online()/assessed()
 * ต่อท้ายเพื่อได้เครื่องที่พร้อมรับงาน
 *
 * @extends Factory<GpuNode>
 */
class GpuNodeFactory extends Factory
{
    protected $model = GpuNode::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pairing_code' => GpuNode::newPairingCode(),
            'pairing_expires_at' => now()->addMinutes(GpuNode::PAIRING_TTL_MINUTES),
        ];
    }

    /** จับคู่แล้ว มี worker บน relay แต่ยังไม่ต่อเข้ามาและยังไม่ประเมิน */
    public function paired(): static
    {
        return $this->state(function () {
            $workerId = 'gxm-' . Str::lower(Str::random(12));

            return [
                'pairing_code' => null,
                'pairing_expires_at' => null,
                'paired_at' => now()->subDay(),
                'worker_id' => $workerId,
                'relay_token' => Str::random(43),
                'relay_url' => 'wss://relay.example.test:8443/agent',
                'tunnel_endpoint' => 'https://relay.example.test:8443/w/' . $workerId,
                'machine_id' => Str::lower(Str::random(40)),
                'label' => 'เครื่องทดสอบ',
            ];
        });
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'online' => true,
            'last_seen_at' => now(),
        ]);
    }

    /** ผ่านการประเมินแล้ว รับงานสร้างภาพได้ */
    public function assessed(): static
    {
        return $this->state(fn () => [
            'assessed' => true,
            'score' => 1200,
            'tier' => 'b',
            'gpu_name' => 'NVIDIA GeForce RTX 3060',
            'vram_total_mb' => 12288,
            'can_run' => ['image'],
            'lanes' => ['image' => 'full'],
        ]);
    }

    /** relay รุ่นใหม่: aixman ถือกุญแจอุโมงค์แยกจากกุญแจของเครื่อง */
    public function withTunnelToken(): static
    {
        return $this->state(fn () => ['tunnel_token' => Str::random(43)]);
    }
}
