<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Support\Auth\LoginLog;
use App\Support\SqlDialect;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "ตรวจสอบการเข้าสู่ระบบ" — what has been happening at the front door.
 *
 * The Telegram alerts answer "is something happening right now". This answers
 * the questions that come after: who has been trying, from where, for how
 * long, and is the address that was blocked at 3am one we should let back in.
 */
class LoginSecurityController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'outcome' => (string) $request->query('outcome', ''),
            'ip' => trim((string) $request->query('ip', '')),
            'email' => trim((string) $request->query('email', '')),
            'method' => (string) $request->query('method', ''),
            'admin_only' => $request->boolean('admin_only'),
            'range' => in_array($request->query('range'), ['24h', '7d', '30d', 'all'], true)
                ? $request->query('range')
                : '7d',
        ];

        $since = match ($filters['range']) {
            '24h' => now()->subDay(),
            '30d' => now()->subDays(30),
            'all' => null,
            default => now()->subDays(7),
        };

        $attempts = LoginAttempt::query()
            ->with('user:id,name,email,role')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->when($filters['outcome'], fn ($q, $v) => $q->where('outcome', $v))
            ->when($filters['method'], fn ($q, $v) => $q->where('method', $v))
            ->when($filters['ip'], fn ($q, $v) => $q->where('ip', $v))
            // A partial match on purpose: staff chasing an incident have the
            // domain from a customer's ticket, rarely the exact address.
            ->when($filters['email'], fn ($q, $v) => $q->where('email', 'like', '%' . $v . '%'))
            ->when($filters['admin_only'], fn ($q) => $q->where('is_admin_target', true))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.security.logins', [
            'attempts' => $attempts,
            'filters' => $filters,
            'stats' => $this->stats(),
            'hourly' => $this->hourly(),
            'topIps' => $this->topIps(),
            'blocks' => BlockedIp::with('blocker:id,name')
                ->orderByRaw('CASE WHEN expires_at IS NULL THEN 0 ELSE 1 END')
                ->latest('updated_at')
                ->limit(50)
                ->get(),
            'autoBlock' => config('security.auto_block'),
        ]);
    }

    /**
     * The four numbers along the top.
     *
     * "Blocked right now" counts rows, not events, because that is the number
     * an operator acts on — how many addresses are currently being turned away.
     */
    private function stats(): array
    {
        $day = now()->subDay();

        $byOutcome = LoginAttempt::query()
            ->where('created_at', '>=', $day)
            ->groupBy('outcome')
            ->selectRaw('outcome, COUNT(*) as total')
            ->pluck('total', 'outcome')
            ->map(fn ($n) => (int) $n)
            ->all();

        return [
            'success_24h' => $byOutcome[LoginAttempt::OUTCOME_SUCCESS] ?? 0,
            'failed_24h' => ($byOutcome[LoginAttempt::OUTCOME_FAILED] ?? 0)
                + ($byOutcome[LoginAttempt::OUTCOME_LOCKOUT] ?? 0),
            'deflected_24h' => ($byOutcome[LoginAttempt::OUTCOME_TURNSTILE] ?? 0)
                + ($byOutcome[LoginAttempt::OUTCOME_BLOCKED] ?? 0),
            'blocked_now' => BlockedIp::query()->active()->count(),
            'admin_failed_24h' => LoginAttempt::query()
                ->where('created_at', '>=', $day)
                ->where('is_admin_target', true)
                ->unsuccessful()
                ->count(),
        ];
    }

    /**
     * Failures per hour for the last 24, for the bar strip.
     *
     * Grouped in SQL rather than in PHP because a busy day is tens of
     * thousands of rows — and through SqlDialect because production is MySQL
     * and this machine is SQLite.
     */
    private function hourly(): array
    {
        $expression = SqlDialect::dateFormat('created_at', '%Y-%m-%d %H');

        $rows = LoginAttempt::query()
            ->where('created_at', '>=', now()->subDay())
            ->unsuccessful()
            ->groupBy(DB::raw($expression))
            ->selectRaw($expression . ' as bucket, COUNT(*) as total')
            ->pluck('total', 'bucket')
            ->map(fn ($n) => (int) $n)
            ->all();

        // Fill the gaps so a quiet hour is a gap in the chart, not a missing bar.
        $buckets = [];

        for ($i = 23; $i >= 0; $i--) {
            $at = now()->subHours($i);
            $key = $at->format('Y-m-d H');
            $buckets[] = [
                'label' => $at->format('H:i'),
                'count' => $rows[$key] ?? 0,
            ];
        }

        return $buckets;
    }

    /** Addresses with the most failures in the window, and whether each is already blocked. */
    private function topIps(): array
    {
        $window = now()->subDays(7);

        $rows = LoginAttempt::query()
            ->where('created_at', '>=', $window)
            ->unsuccessful()
            ->whereNotNull('ip')
            ->groupBy('ip')
            ->select('ip', DB::raw('COUNT(*) as failures'), DB::raw('MAX(created_at) as last_seen'))
            ->orderByDesc('failures')
            ->limit(10)
            ->get();

        $blocked = BlockedIp::query()
            ->active()
            ->whereIn('ip', $rows->pluck('ip'))
            ->pluck('ip')
            ->flip();

        return $rows->map(fn ($row) => [
            'ip' => $row->ip,
            'failures' => (int) $row->failures,
            'last_seen' => Carbon::parse($row->last_seen),
            'blocked' => $blocked->has($row->ip),
            // Why a busy address is still free: an admin has signed in from it
            // recently, so the automatic blocker leaves it alone on purpose.
            'trusted' => LoginLog::isTrustedAddress($row->ip),
            // Distinct accounts touched — the tell for credential stuffing.
            'accounts' => LoginAttempt::query()
                ->where('ip', $row->ip)
                ->where('created_at', '>=', $window)
                ->whereNotNull('email')
                ->distinct()
                ->count('email'),
        ])->all();
    }

    public function block(Request $request)
    {
        $validated = $request->validate([
            'ip' => ['required', 'string', 'ip'],
            'reason' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
        ], [], [
            'ip' => 'หมายเลข IP',
        ]);

        $ip = $validated['ip'];

        // Blocking the address you are sitting behind takes the admin panel
        // away from you and leaves no way back in except the command line.
        if ($ip === $request->ip()) {
            return back()->with('error', 'นี่คือ IP ของคุณเอง — บล็อกแล้วคุณจะเข้าหลังบ้านไม่ได้');
        }

        $hours = $validated['hours'] ?? null;

        BlockedIp::block(
            ip: $ip,
            reason: $validated['reason'] ?: 'บล็อกด้วยตนเองโดยแอดมิน',
            until: $hours ? now()->addHours($hours) : null,
            source: BlockedIp::SOURCE_MANUAL,
            byUserId: $request->user()->id,
        );

        return back()->with('success', 'บล็อก ' . $ip . ' แล้ว' . ($hours ? " ({$hours} ชั่วโมง)" : ' (ถาวร)'));
    }

    public function unblock(Request $request)
    {
        $validated = $request->validate([
            'ip' => ['required', 'string', 'ip'],
        ]);

        BlockedIp::unblock($validated['ip']);

        return back()->with('success', 'ปลดบล็อก ' . $validated['ip'] . ' แล้ว');
    }
}
