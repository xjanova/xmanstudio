<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCrawlLog;
use App\Models\AiCrawlSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiCrawlController extends Controller
{
    /**
     * Analytics dashboard.
     */
    public function index(Request $request)
    {
        $setting = AiCrawlSetting::getInstance();
        $period = $request->input('period', '7d');

        $startDate = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7),
        };

        // Total stats
        $totalRequests = AiCrawlLog::where('created_at', '>=', $startDate)->count();
        $blockedRequests = AiCrawlLog::where('created_at', '>=', $startDate)->blocked()->count();
        $uniqueBots = AiCrawlLog::where('created_at', '>=', $startDate)->distinct('bot_name')->count('bot_name');
        $todayRequests = AiCrawlLog::today()->count();

        // Requests by bot
        $botStats = AiCrawlLog::where('created_at', '>=', $startDate)
            ->select('bot_name', 'bot_category', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN was_blocked = 1 THEN 1 ELSE 0 END) as blocked'))
            ->groupBy('bot_name', 'bot_category')
            ->orderByDesc('total')
            ->get();

        // Requests by category
        $categoryStats = AiCrawlLog::where('created_at', '>=', $startDate)
            ->select('bot_category', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN was_blocked = 1 THEN 1 ELSE 0 END) as blocked'))
            ->groupBy('bot_category')
            ->orderByDesc('total')
            ->get();

        // Daily trend
        $dailyTrend = AiCrawlLog::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN was_blocked = 1 THEN 1 ELSE 0 END) as blocked'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Top crawled URLs
        $topUrls = AiCrawlLog::where('created_at', '>=', $startDate)
            ->select('url', DB::raw('COUNT(*) as hits'))
            ->groupBy('url')
            ->orderByDesc('hits')
            ->limit(20)
            ->get();

        // Recent logs
        $recentLogs = AiCrawlLog::orderByDesc('created_at')->limit(50)->get();

        return view('admin.ai-crawl.index', compact(
            'setting',
            'period',
            'totalRequests',
            'blockedRequests',
            'uniqueBots',
            'todayRequests',
            'botStats',
            'categoryStats',
            'dailyTrend',
            'topUrls',
            'recentLogs',
        ));
    }

    /**
     * Settings page.
     */
    public function settings()
    {
        $setting = AiCrawlSetting::getInstance();
        $knownBots = AiCrawlSetting::getKnownBots();

        return view('admin.ai-crawl.settings', compact('setting', 'knownBots'));
    }

    /**
     * Update settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'blocked_paths' => 'nullable|string',
            'llms_txt_content' => 'nullable|string',
        ]);

        $setting = AiCrawlSetting::getInstance();

        // Parse blocked paths (one per line)
        $blockedPaths = [];
        if ($request->filled('blocked_paths')) {
            $blockedPaths = array_filter(
                array_map('trim', explode("\n", $request->input('blocked_paths')))
            );
        }

        // Parse custom bot rules from the toggle inputs
        $customRules = [];
        $knownBots = AiCrawlSetting::getKnownBots();
        foreach ($knownBots as $botName => $info) {
            $action = $request->input("bot_rule_{$botName}", 'default');
            $action = in_array($action, ['default', 'allow', 'block']) ? $action : 'default';
            if ($action !== 'default') {
                $customRules[] = ['bot_name' => $botName, 'action' => $action];
            }
        }

        $setting->update([
            'enabled' => $request->has('enabled'),
            'logging_enabled' => $request->has('logging_enabled'),
            'block_training_bots' => $request->has('block_training_bots'),
            'allow_assistant_bots' => $request->has('allow_assistant_bots'),
            'allow_search_bots' => $request->has('allow_search_bots'),
            'custom_bot_rules' => $customRules ?: null,
            'blocked_paths' => $blockedPaths ?: null,
            'llms_txt_enabled' => $request->has('llms_txt_enabled'),
            'llms_txt_content' => $request->input('llms_txt_content'),
        ]);

        return redirect()
            ->route('admin.ai-crawl.settings')
            ->with('success', 'AI Crawl settings updated successfully!');
    }

    /**
     * Clear old logs.
     */
    public function clearLogs(Request $request)
    {
        $days = max(1, min(365, (int) $request->input('days', 30)));
        $deleted = AiCrawlLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()
            ->route('admin.ai-crawl.index')
            ->with('success', "Cleared {$deleted} logs older than {$days} days.");
    }

    /**
     * Export logs as CSV.
     */
    public function exportLogs(Request $request)
    {
        $period = $request->input('period', '30d');
        $startDate = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $filename = 'ai-crawl-logs-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($startDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Bot Name', 'Category', 'IP', 'URL', 'Method', 'Status', 'Blocked']);

            AiCrawlLog::where('created_at', '>=', $startDate)
                ->orderByDesc('created_at')
                ->cursor()
                ->each(function ($log) use ($handle) {
                    fputcsv($handle, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->bot_name,
                        $log->bot_category,
                        $log->ip_address,
                        $log->url,
                        $log->method,
                        $log->status_code,
                        $log->was_blocked ? 'Yes' : 'No',
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
