<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class RedisSettingsController extends Controller
{
    public function index()
    {
        $config = [
            'client' => config('database.redis.client'),
            'host' => config('database.redis.default.host'),
            'port' => config('database.redis.default.port'),
            'database' => config('database.redis.default.database'),
            'password' => config('database.redis.default.password') ? '********' : null,
            'prefix' => config('database.redis.options.prefix'),
        ];

        $services = [
            'cache' => config('cache.default'),
            'session' => config('session.driver'),
            'queue' => config('queue.default'),
        ];

        $status = $this->checkConnection();

        $info = null;
        if ($status['connected']) {
            $info = $this->getRedisInfo();
        }

        return view('admin.redis-settings.index', compact('config', 'services', 'status', 'info'));
    }

    public function testConnection()
    {
        $status = $this->checkConnection();

        return response()->json($status);
    }

    public function updateEnv(Request $request)
    {
        $request->validate([
            'redis_host' => 'required|string|max:255',
            'redis_port' => 'required|integer|min:1|max:65535',
            'redis_password' => 'nullable|string|max:255',
            'redis_db' => 'required|integer|min:0|max:15',
            'redis_client' => 'required|in:phpredis,predis',
            'cache_store' => 'required|in:database,redis,file,array',
            'session_driver' => 'required|in:database,redis,file,cookie,array',
            'queue_connection' => 'required|in:database,redis,sync',
        ]);

        $envUpdates = [
            'REDIS_HOST' => $request->redis_host,
            'REDIS_PORT' => $request->redis_port,
            'REDIS_DB' => $request->redis_db,
            'REDIS_CLIENT' => $request->redis_client,
            'CACHE_STORE' => $request->cache_store,
            'SESSION_DRIVER' => $request->session_driver,
            'QUEUE_CONNECTION' => $request->queue_connection,
        ];

        if ($request->filled('redis_password') && $request->redis_password !== '********') {
            $envUpdates['REDIS_PASSWORD'] = $request->redis_password;
        }

        $this->updateEnvFile($envUpdates);

        return redirect()->route('admin.redis-settings.index')
            ->with('success', 'บันทึกการตั้งค่า Redis สำเร็จ กรุณา restart queue worker หากเปลี่ยน queue driver');
    }

    private function checkConnection(): array
    {
        try {
            $client = config('database.redis.client', 'phpredis');

            if ($client === 'phpredis' && ! extension_loaded('redis')) {
                return [
                    'connected' => false,
                    'message' => 'PHP Redis extension ไม่ได้ติดตั้ง กรุณาติดตั้ง php-redis หรือเปลี่ยนเป็น predis',
                ];
            }

            Redis::connection('default')->ping();

            return [
                'connected' => true,
                'message' => 'เชื่อมต่อ Redis สำเร็จ',
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ Redis: ' . $e->getMessage(),
            ];
        }
    }

    private function getRedisInfo(): array
    {
        try {
            $info = Redis::connection('default')->info();

            // Handle both phpredis and predis response formats
            $server = $info['Server'] ?? $info;

            return [
                'version' => $server['redis_version'] ?? 'N/A',
                'uptime' => isset($server['uptime_in_seconds']) ? $this->formatUptime((int) $server['uptime_in_seconds']) : 'N/A',
                'connected_clients' => $server['connected_clients'] ?? ($info['Clients']['connected_clients'] ?? 'N/A'),
                'used_memory' => $server['used_memory_human'] ?? ($info['Memory']['used_memory_human'] ?? 'N/A'),
                'total_keys' => $this->getTotalKeys(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getTotalKeys(): string
    {
        try {
            $dbSize = Redis::connection('default')->dbsize();

            return number_format($dbSize);
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $mins = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' วัน';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ชม.';
        }
        $parts[] = $mins . ' นาที';

        return implode(' ', $parts);
    }

    private function updateEnvFile(array $updates): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($updates as $key => $value) {
            $escapedValue = str_contains($value, ' ') ? '"' . $value . '"' : $value;

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
            } else {
                $content .= "\n{$key}={$escapedValue}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
