<?php

namespace App\Services\LocalVpnFleet;

use App\Models\VpnNode;
use App\Models\VpnNodeUsageDaily;
use App\Support\LocalVpnFleet\FleetClock;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnFleet\Tier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps the fleet's bill flat (docs/RELAY_FLEET.md, "Backend: data and policy").
 *
 * Per node: this billing month's NIC bytes, counted the way the provider bills
 * them (billing_mode), against included_gb. At free_cut_pct the node stops
 * taking free users, at trial_cut_pct trial users, at pro_cut_pct everyone —
 * unless allow_overage, which only ever lifts the Pro cut.
 *
 * Fleet-wide: each node's month is projected linearly from its month-to-date
 * usage; cost = monthly_cost_thb + max(0, projected_gb − included_gb) ×
 * overage_thb_per_gb. When the projected cost of all nodes passes the policy's
 * monthly_thb, allow_overage is ignored everywhere. The projection always uses
 * the uncapped trend, so switching overage off cannot flip it back on.
 *
 * GB here is 10^9 bytes: it reads slightly higher than GiB, so the cut-offs
 * fire a little early rather than late.
 */
final class FleetBudget
{
    public const BYTES_PER_GB = 1_000_000_000;

    /** Projections start after a day, so one busy first hour can't fake a huge month. */
    public const MIN_ELAPSED_SECONDS = 86_400;

    private const CACHE_KEY = 'localvpn_fleet:budget';

    private const CACHE_SECONDS = 20;

    /** Cached for hot paths (heartbeat tickets, node sync answers). */
    public function snapshot(): FleetBudgetSnapshot
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => $this->fresh());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Straight from the database (admin pages). */
    public function fresh(?CarbonImmutable $now = null): FleetBudgetSnapshot
    {
        $now = ($now ?? FleetClock::now())->setTimezone(FleetClock::TZ);
        $nodes = VpnNode::query()->orderBy('id')->get();

        $earliest = $nodes->isEmpty() ? $now : $nodes
            ->map(fn (VpnNode $node) => self::billingPeriod($node->billing_day, $now)[0])
            ->min();

        $rows = $nodes->isEmpty() ? [] : VpnNodeUsageDaily::query()
            ->where('date', '>=', $earliest->toDateString())
            ->toBase()
            ->get(['node_id', 'date', 'nic_rx', 'nic_tx'])
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->evaluate($nodes, $rows, FleetPolicy::current(), $now);
    }

    /**
     * The whole calculation, free of the database.
     *
     * @param  iterable<VpnNode>  $nodes
     * @param  list<array{node_id: int, date: string, nic_rx: int, nic_tx: int}>  $dailyRows
     */
    public function evaluate(iterable $nodes, array $dailyRows, array $policy, CarbonImmutable $now): FleetBudgetSnapshot
    {
        $now = $now->setTimezone(FleetClock::TZ);
        $budget = $policy['budget'];

        $totals = [];
        foreach ($dailyRows as $row) {
            $totals[(int) $row['node_id']][] = $row;
        }

        // Pass 1: usage and projection per node.
        $measured = [];
        $projectedTotal = 0.0;
        $soFarTotal = 0.0;
        foreach ($nodes as $node) {
            [$start, $end] = self::billingPeriod($node->billing_day, $now);
            $from = $start->toDateString();
            $until = $end->toDateString();

            $rx = 0;
            $tx = 0;
            foreach ($totals[$node->id] ?? [] as $row) {
                $date = substr((string) $row['date'], 0, 10);
                if ($date >= $from && $date < $until) {
                    $rx += (int) $row['nic_rx'];
                    $tx += (int) $row['nic_tx'];
                }
            }

            $used = self::billedBytes((string) $node->billing_mode, $rx, $tx);
            $projected = $this->projectBytes($used, $node, $start, $end, $now);

            $usedGb = $used / self::BYTES_PER_GB;
            $projectedGb = $projected / self::BYTES_PER_GB;
            $quotaGb = (int) $node->included_gb;
            $monthly = (float) $node->monthly_cost_thb;
            $rate = (float) $node->overage_thb_per_gb;

            $periodSeconds = max(1, $end->getTimestamp() - $start->getTimestamp());
            $elapsedShare = min(1.0, max(0.0, ($now->getTimestamp() - $start->getTimestamp()) / $periodSeconds));

            $projectedCost = $monthly + max(0.0, $projectedGb - $quotaGb) * $rate;
            $costSoFar = $monthly * $elapsedShare + max(0.0, $usedGb - $quotaGb) * $rate;

            $projectedTotal += $projectedCost;
            $soFarTotal += $costSoFar;

            $measured[$node->id] = compact('node', 'start', 'end', 'used', 'usedGb', 'projectedGb', 'quotaGb', 'projectedCost', 'costSoFar');
        }

        $monthlyBudget = (float) $budget['monthly_thb'];
        $overBudget = $projectedTotal > $monthlyBudget;

        // Pass 2: who each node still takes, now that the fleet total is known.
        $result = [];
        foreach ($measured as $id => $m) {
            $node = $m['node'];
            $pct = $m['quotaGb'] > 0 ? $m['usedGb'] / $m['quotaGb'] * 100 : ($m['used'] > 0 ? INF : 0.0);
            $overage = (bool) $node->allow_overage && ! $overBudget;

            $result[$id] = new NodeBudget(
                nodeId: $id,
                periodStart: $m['start'],
                periodEnd: $m['end'],
                usedBytes: $m['used'],
                usedGb: $m['usedGb'],
                quotaGb: $m['quotaGb'],
                usedPct: $pct,
                projectedGb: $m['projectedGb'],
                costSoFarThb: round($m['costSoFar'], 2),
                projectedCostThb: round($m['projectedCost'], 2),
                overageAllowed: $overage,
                accepts: [
                    Tier::FREE => $pct < $budget['free_cut_pct'],
                    Tier::TRIAL => $pct < $budget['trial_cut_pct'],
                    Tier::PRO => $pct < $budget['pro_cut_pct'] || $overage,
                ],
                active: $node->status === VpnNode::STATUS_ACTIVE,
            );
        }

        return new FleetBudgetSnapshot(
            nodes: $result,
            costSoFarThb: round($soFarTotal, 2),
            projectedCostThb: round($projectedTotal, 2),
            monthlyBudgetThb: $monthlyBudget,
            overBudget: $overBudget,
        );
    }

    /**
     * The billing period containing $now: from billing_day (00:00 Bangkok) to
     * the same day next month. Days past a short month's end clamp to its
     * last day.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [start, end)
     */
    public static function billingPeriod(int $billingDay, CarbonImmutable $now): array
    {
        $now = $now->setTimezone(FleetClock::TZ);
        $billingDay = max(1, min(31, $billingDay));
        $anchor = fn (CarbonImmutable $month) => $month->setDay(min($billingDay, $month->daysInMonth))->startOfDay();

        $thisMonth = $now->startOfMonth();
        $start = $anchor($thisMonth);
        if ($now->lessThan($start)) {
            $start = $anchor($thisMonth->subMonthNoOverflow());
        }

        return [$start, $anchor($start->startOfMonth()->addMonthNoOverflow())];
    }

    /** Bytes the provider bills for: outbound only, both ways, or the larger direction. */
    public static function billedBytes(string $mode, int $rx, int $tx): int
    {
        return match ($mode) {
            'in_out' => $rx + $tx,
            'max' => max($rx, $tx),
            default => $tx,
        };
    }

    /**
     * Month-to-date usage stretched over the whole period at the same rate.
     * A node added mid-period is measured from when it was added.
     */
    private function projectBytes(int $used, VpnNode $node, CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $now): float
    {
        $from = $start;
        if ($node->created_at !== null) {
            $created = CarbonImmutable::instance($node->created_at)->setTimezone(FleetClock::TZ);
            if ($created->greaterThan($from)) {
                $from = $created;
            }
        }

        $elapsed = max(self::MIN_ELAPSED_SECONDS, $now->getTimestamp() - $from->getTimestamp());
        $remaining = max(0, $end->getTimestamp() - $now->getTimestamp());

        return $used + $used / $elapsed * $remaining;
    }
}
