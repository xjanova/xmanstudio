<?php

namespace Tests\Concerns;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * relay กับ aixman ปลอมสำหรับเทสต์ของ GPUxMINE
 *
 * ทุกเทสต์ตั้งสถานะของสองฝั่งผ่าน property แล้วเรียก fakeGpuxMine() ครั้งเดียว
 * — callback อ่าน property ตอนมีคำขอเข้ามา จึงเปลี่ยนสถานะกลางเทสต์ได้
 * (เช่น aixman ล่มรอบแรก ฟื้นรอบสอง) คำขอที่ไม่ได้ตั้งไว้ทำให้เทสต์ล้มทันที
 */
trait FakesGpuxMineNetwork
{
    protected string $relayBase = 'https://relay.example.test:8443';

    protected string $aixmanBase = 'https://ai.example.test';

    /** @var array<int, array<string, mixed>>|null รายชื่อที่ relay ตอบ — null = relay ตอบ 500 */
    protected ?array $relayWorkers = [];

    /** สถานะที่ relay ตอบรายชื่อ (เมื่อ relayWorkers ไม่เป็น null) — 401 = admin key ผิด */
    protected int $relayListStatus = 200;

    /** สถานะที่ relay ตอบ POST /admin/workers/{id}/disable|enable|rotate */
    protected int $relayAdminActionStatus = 200;

    /** @var array<string, mixed>|null สิ่งที่ /enroll ตอบ — null = relay ปฏิเสธ */
    protected ?array $enrolment = null;

    protected int $relayDeleteStatus = 200;

    /**
     * สิ่งที่ POST /admin/workers/{id}/rotate ตอบเมื่อสถานะเป็น 2xx — null = กุญแจอุโมงค์ใหม่
     * แบบที่ relay ตอบ `?only=tunnel` (token เป็น null)
     *
     * @var array<string, mixed>|null
     */
    protected ?array $rotation = null;

    /** เรียกระหว่างที่ relay กำลังออก worker ให้ /enroll — จำลองสิ่งที่เกิดขึ้นกลางทาง */
    protected ?\Closure $whileEnrolling = null;

    /** null = ติดต่อ aixman ไม่ได้เลย */
    protected ?int $aixmanStatus = 200;

    /** @var array<string, mixed> */
    protected array $aixmanBody = [
        'status' => 'eligible',
        'note' => null,
        'modelKey' => 'sdxl-community',
        'worker' => ['id' => 1, 'externalId' => 'x', 'status' => 'warming', 'modelKey' => 'sdxl-community', 'lastError' => null],
    ];

    protected int $aixmanRetireStatus = 200;

    /** @var array<int, array{method:string, url:string, data:array<string, mixed>}> */
    protected array $gpuxCalls = [];

    protected function fakeGpuxMine(): void
    {
        config([
            'services.gpuxmine.relay_url' => $this->relayBase,
            'services.gpuxmine.admin_key' => 'relay-admin-key-for-tests',
            'services.gpuxmine.max_nodes_per_user' => 10,
            'services.gpuxmine.resync_minutes' => 10,
            'services.aixman.api_base' => $this->aixmanBase,
            'services.aixman.webhook_secret' => 'shared-webhook-secret-for-tests',
        ]);

        Sleep::fake();
        Http::preventStrayRequests();

        Http::fake(function (Request $request) {
            $url = $request->url();
            $method = $request->method();
            $this->gpuxCalls[] = ['method' => $method, 'url' => $url, 'data' => $request->data()];

            if (str_starts_with($url, $this->aixmanBase . '/api/gpux/nodes')) {
                if ($method === 'DELETE') {
                    return Factory::response(['ok' => true], $this->aixmanRetireStatus);
                }
                if ($this->aixmanStatus === null) {
                    return Factory::failedConnection();
                }

                return Factory::response($this->aixmanBody, $this->aixmanStatus);
            }

            if (str_starts_with($url, $this->relayBase . '/enroll')) {
                if ($this->whileEnrolling !== null) {
                    ($this->whileEnrolling)();
                }

                return $this->enrolment === null
                    ? Factory::response(['error' => 'nope'], 500)
                    : Factory::response($this->enrolment);
            }

            if ($url === $this->relayBase . '/admin/workers' && $method === 'GET') {
                if ($this->relayWorkers === null) {
                    return Factory::response('upstream down', 502);
                }

                return $this->relayListStatus === 200
                    ? Factory::response($this->relayWorkers)
                    : Factory::response(['error' => 'unauthorized'], $this->relayListStatus);
            }

            if (str_starts_with($url, $this->relayBase . '/admin/workers/') && $method === 'DELETE') {
                return Factory::response(['deleted' => true], $this->relayDeleteStatus);
            }

            if (str_starts_with($url, $this->relayBase . '/admin/workers/') && $method === 'POST') {
                if (str_contains($url, '/rotate') && $this->relayAdminActionStatus < 300) {
                    $workerId = rawurldecode((string) preg_replace('#^.*/admin/workers/([^/]+)/rotate.*$#', '$1', $url));

                    return Factory::response($this->rotation ?? [
                        'workerId' => $workerId,
                        'token' => null,
                        'tunnelToken' => 'tunnel-rotated-' . $workerId,
                    ]);
                }

                return Factory::response(['ok' => $this->relayAdminActionStatus < 300], $this->relayAdminActionStatus);
            }

            return Factory::response('unexpected ' . $method . ' ' . $url, 599);
        });
    }

    /** @return array<int, array<string, mixed>> ข้อมูลที่ถูกส่งไป POST /api/gpux/nodes ตามลำดับ */
    protected function aixmanPushes(): array
    {
        return array_values(array_map(
            fn ($c) => $c['data'],
            array_filter($this->gpuxCalls, fn ($c) => $c['method'] === 'POST' && str_starts_with($c['url'], $this->aixmanBase . '/api/gpux/nodes'))
        ));
    }

    /** @return array<int, string> workerId ที่ถูกสั่งถอนที่ aixman */
    protected function aixmanRetires(): array
    {
        return array_values(array_map(
            fn ($c) => (string) ($c['data']['workerId'] ?? ''),
            array_filter($this->gpuxCalls, fn ($c) => $c['method'] === 'DELETE' && str_starts_with($c['url'], $this->aixmanBase . '/api/gpux/nodes'))
        ));
    }

    /** @return array<int, string> workerId ที่ถูกลบที่ relay */
    protected function relayDeletes(): array
    {
        $prefix = $this->relayBase . '/admin/workers/';

        return array_values(array_map(
            fn ($c) => rawurldecode(substr($c['url'], strlen($prefix))),
            array_filter($this->gpuxCalls, fn ($c) => $c['method'] === 'DELETE' && str_starts_with($c['url'], $prefix))
        ));
    }

    /** @return array<int, string> คำสั่งแอดมินที่ส่งให้ relay ตามลำดับ เช่น 'gxm-abc/disable' */
    protected function relayAdminActions(): array
    {
        $prefix = $this->relayBase . '/admin/workers/';

        return array_values(array_map(
            fn ($c) => rawurldecode(substr($c['url'], strlen($prefix))),
            array_filter($this->gpuxCalls, fn ($c) => $c['method'] === 'POST' && str_starts_with($c['url'], $prefix))
        ));
    }

    protected function relayListings(): int
    {
        return count(array_filter(
            $this->gpuxCalls,
            fn ($c) => $c['method'] === 'GET' && $c['url'] === $this->relayBase . '/admin/workers'
        ));
    }

    protected function enrolments(): int
    {
        return count(array_filter($this->gpuxCalls, fn ($c) => str_starts_with($c['url'], $this->relayBase . '/enroll')));
    }

    /**
     * แถวหนึ่งในรายชื่อของ relay สำหรับเครื่องที่ออนไลน์และประเมินแล้ว
     *
     * $disabled = null คือ relay รุ่นก่อนที่ยังไม่บอกช่อง disabled (และไม่มีคำสั่งปิด/ลบ)
     *
     * @param  array<string, mixed>  $telemetry
     * @return array<string, mixed>
     */
    protected function liveWorker(string $workerId, array $telemetry = [], bool $online = true, ?bool $disabled = null): array
    {
        return ($disabled === null ? [] : [
            // relay รุ่นที่มีคำสั่ง disable/enable/delete บอกสองค่านี้ในรายชื่อ — รุ่นเก่าไม่บอก
            'disabled' => $disabled,
            'hasTunnelToken' => false,
        ]) + [
            'workerId' => $workerId,
            'label' => 'x',
            'online' => $online,
            'agentVersion' => $online ? '0.2.0' : null,
            'lastSeenAt' => $online ? now()->toIso8601String() : null,
            'telemetry' => $online ? $telemetry + [
                'gpuName' => 'NVIDIA GeForce RTX 3060',
                'vramTotalMb' => 12288,
                'accepting' => true,
                'freeSharePct' => 0,
                'assessed' => true,
                'score' => 1200,
                'tier' => 'b',
                'canRun' => ['image'],
                'lanes' => ['image' => 'full'],
            ] : null,
        ];
    }
}
