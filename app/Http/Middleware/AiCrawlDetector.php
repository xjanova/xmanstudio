<?php

namespace App\Http\Middleware;

use App\Models\AiCrawlLog;
use App\Models\AiCrawlSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AiCrawlDetector
{
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->userAgent() ?? '';
        $botInfo = $this->detectAiBot($userAgent);

        if (! $botInfo) {
            return $next($request);
        }

        $setting = AiCrawlSetting::getInstance();

        if (! $setting->enabled) {
            return $next($request);
        }

        $path = '/' . ltrim($request->path(), '/');
        $shouldBlock = $setting->shouldBlockBot($botInfo['name'], $botInfo['category'], $path);

        // Log the interaction
        if ($setting->logging_enabled) {
            AiCrawlLog::create([
                'bot_name' => $botInfo['name'],
                'bot_category' => $botInfo['category'],
                'ip_address' => $request->ip(),
                'url' => mb_substr($request->fullUrl(), 0, 2048),
                'method' => $request->method(),
                'status_code' => $shouldBlock ? 403 : 200,
                'user_agent' => mb_substr($userAgent, 0, 1024),
                'was_blocked' => $shouldBlock,
            ]);
        }

        if ($shouldBlock) {
            return response('Access denied for AI crawlers on this path.', 403)
                ->header('Content-Type', 'text/plain')
                ->header('X-Robots-Tag', 'noai, noimageai');
        }

        $response = $next($request);

        return $response;
    }

    private function detectAiBot(string $userAgent): ?array
    {
        if (empty($userAgent)) {
            return null;
        }

        $bots = AiCrawlSetting::getKnownBots();

        foreach ($bots as $name => $info) {
            if (stripos($userAgent, $info['pattern']) !== false) {
                return [
                    'name' => $name,
                    'category' => $info['category'],
                    'owner' => $info['owner'],
                ];
            }
        }

        return null;
    }
}
