<?php

namespace Tests\Unit\LocalVpnFleet;

use App\Models\VpnNode;
use App\Services\LocalVpnFleet\FleetBudget;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnFleet\Tier;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * The relay fleet's budget rules (docs/RELAY_FLEET.md): cut-offs per tier,
 * linear projection, billing modes, billing-day rollover, and allow_overage
 * being ignored once the fleet's projected cost passes the monthly budget.
 * Pure calculation — no database.
 */
class FleetBudgetTest extends TestCase
{
    private const GB = 1_000_000_000;

    private int $nextId = 1;

    private function node(array $attributes = []): VpnNode
    {
        $node = new VpnNode(array_merge([
            'code' => 'n' . $this->nextId,
            'roles' => ['relay'],
            'status' => VpnNode::STATUS_ACTIVE,
            'monthly_cost_thb' => 0,
            'included_gb' => 100,
            'overage_thb_per_gb' => 0,
            'billing_day' => 1,
            'billing_mode' => 'out',
            'allow_overage' => false,
        ], $attributes));
        $node->id = $this->nextId++;

        return $node;
    }

    private function row(VpnNode $node, string $date, float $txGb, float $rxGb = 0): array
    {
        return ['node_id' => $node->id, 'date' => $date, 'nic_rx' => (int) ($rxGb * self::GB), 'nic_tx' => (int) ($txGb * self::GB)];
    }

    private function at(string $bangkok): CarbonImmutable
    {
        return CarbonImmutable::parse($bangkok, 'Asia/Bangkok');
    }

    private function evaluate(array $nodes, array $rows, string $now, array $budget = [])
    {
        $policy = FleetPolicy::DEFAULTS;
        $policy['budget'] = array_merge($policy['budget'], $budget);

        return (new FleetBudget)->evaluate($nodes, $rows, $policy, $this->at($now));
    }

    public function test_each_tier_is_cut_at_its_own_threshold(): void
    {
        // Keys are strings: PHP would truncate float keys to ints.
        $cases = [
            '79' => [true, true, true],
            '80' => [false, true, true],
            '89.9' => [false, true, true],
            '90' => [false, false, true],
            '97.9' => [false, false, true],
            '98' => [false, false, false],
            '120' => [false, false, false],
        ];

        foreach ($cases as $usedGb => [$free, $trial, $pro]) {
            $node = $this->node();
            $budget = $this->evaluate([$node], [$this->row($node, '2026-10-05', (float) $usedGb)], '2026-10-11 12:00')->forNode($node->id);

            $this->assertSame($free, $budget->accepts(Tier::FREE), "free at {$usedGb} GB");
            $this->assertSame($trial, $budget->accepts(Tier::TRIAL), "trial at {$usedGb} GB");
            $this->assertSame($pro, $budget->accepts(Tier::PRO), "pro at {$usedGb} GB");
        }
    }

    public function test_min_tier_and_accept_new_follow_the_cut_offs_and_the_status(): void
    {
        $fresh = $this->node();
        $nearlyFull = $this->node();
        $full = $this->node();
        $draining = $this->node(['status' => VpnNode::STATUS_DRAINING]);

        $snapshot = $this->evaluate([$fresh, $nearlyFull, $full, $draining], [
            $this->row($nearlyFull, '2026-10-02', 85),
            $this->row($full, '2026-10-02', 99),
        ], '2026-10-11 12:00');

        $this->assertSame(['accept_new' => true, 'min_tier' => 'free', 'used_gb' => 0.0, 'quota_gb' => 100], $snapshot->forNode($fresh->id)->toSyncArray());
        $this->assertSame('trial', $snapshot->forNode($nearlyFull->id)->minTier());
        $this->assertFalse($snapshot->forNode($full->id)->acceptNew());
        $this->assertNull($snapshot->forNode($full->id)->minTier());
        // Draining keeps its users but takes nobody new, whatever the budget.
        $this->assertFalse($snapshot->forNode($draining->id)->acceptNew());
    }

    public function test_allow_overage_lifts_only_the_pro_cut(): void
    {
        $node = $this->node(['allow_overage' => true]);
        $budget = $this->evaluate([$node], [$this->row($node, '2026-10-05', 150)], '2026-10-11 12:00')->forNode($node->id);

        $this->assertTrue($budget->overageAllowed);
        $this->assertTrue($budget->accepts(Tier::PRO));
        $this->assertFalse($budget->accepts(Tier::TRIAL));
        $this->assertFalse($budget->accepts(Tier::FREE));
    }

    public function test_overage_is_ignored_everywhere_once_the_projected_fleet_cost_passes_the_budget(): void
    {
        $overNode = $this->node(['allow_overage' => true, 'monthly_cost_thb' => 1000]);
        $rows = [$this->row($overNode, '2026-10-05', 99)];

        // 1000 + 1500 = 2500 ≤ 3000: overage still allowed.
        $cheap = $this->node(['monthly_cost_thb' => 1500]);
        $under = $this->evaluate([$overNode, $cheap], $rows, '2026-10-11 12:00');
        $this->assertFalse($under->overBudget);
        $this->assertTrue($under->forNode($overNode->id)->accepts(Tier::PRO));

        // 1000 + 2500 = 3500 > 3000: every node loses overage, even one that set it.
        $pricey = $this->node(['monthly_cost_thb' => 2500]);
        $over = $this->evaluate([$overNode, $pricey], $rows, '2026-10-11 12:00');
        $this->assertTrue($over->overBudget);
        $this->assertSame(3500.0, $over->projectedCostThb);
        $this->assertFalse($over->forNode($overNode->id)->overageAllowed);
        $this->assertFalse($over->forNode($overNode->id)->accepts(Tier::PRO));
    }

    public function test_the_month_is_projected_linearly_and_priced_with_overage(): void
    {
        // October has 31 days; on the 11th at 00:00 ten days have passed and 21 remain.
        $node = $this->node(['included_gb' => 200, 'monthly_cost_thb' => 500, 'overage_thb_per_gb' => 2]);
        $budget = $this->evaluate([$node], [
            $this->row($node, '2026-10-02', 60),
            $this->row($node, '2026-10-10', 40),
        ], '2026-10-11 00:00')->forNode($node->id);

        $this->assertEqualsWithDelta(100.0, $budget->usedGb, 0.0001);
        $this->assertEqualsWithDelta(310.0, $budget->projectedGb, 0.0001);
        // 500 + (310 − 200) × 2
        $this->assertSame(720.0, $budget->projectedCostThb);
        // 500 × 10/31 so far, no overage yet (100 ≤ 200)
        $this->assertSame(round(500 * 10 / 31, 2), $budget->costSoFarThb);
    }

    public function test_a_node_added_mid_period_is_projected_from_when_it_was_added(): void
    {
        $node = $this->node();
        // Timestamps are stored in the app's timezone (UTC), like a real row.
        $node->created_at = $this->at('2026-10-06 00:00')->utc();

        // 50 GB in the five days since it was added, 21 days to go → 50 + 10 × 21.
        $budget = $this->evaluate([$node], [$this->row($node, '2026-10-08', 50)], '2026-10-11 00:00')->forNode($node->id);

        $this->assertEqualsWithDelta(260.0, $budget->projectedGb, 0.0001);
    }

    public function test_one_busy_first_hour_does_not_project_a_huge_month(): void
    {
        $node = $this->node();

        // One hour into the period: projected as if a full day had passed.
        $budget = $this->evaluate([$node], [$this->row($node, '2026-10-01', 1)], '2026-10-01 01:00')->forNode($node->id);

        $hoursLeft = 31 * 24 - 1;
        $this->assertEqualsWithDelta(1 + $hoursLeft / 24, $budget->projectedGb, 0.0001);
    }

    public function test_billing_modes_count_what_the_provider_bills(): void
    {
        $this->assertSame(300, FleetBudget::billedBytes('out', 500, 300));
        $this->assertSame(800, FleetBudget::billedBytes('in_out', 500, 300));
        $this->assertSame(500, FleetBudget::billedBytes('max', 500, 300));

        // "max" is the larger direction over the whole month, not per day.
        $node = $this->node(['billing_mode' => 'max']);
        $budget = $this->evaluate([$node], [
            $this->row($node, '2026-10-02', 10, 40),
            $this->row($node, '2026-10-03', 45, 0),
        ], '2026-10-11 12:00')->forNode($node->id);
        $this->assertEqualsWithDelta(55.0, $budget->usedGb, 0.0001);

        $inOut = $this->node(['billing_mode' => 'in_out']);
        $both = $this->evaluate([$inOut], [$this->row($inOut, '2026-10-02', 10, 40)], '2026-10-11 12:00')->forNode($inOut->id);
        $this->assertEqualsWithDelta(50.0, $both->usedGb, 0.0001);
    }

    public function test_the_billing_period_starts_on_billing_day_and_rolls_over(): void
    {
        [$start, $end] = FleetBudget::billingPeriod(15, $this->at('2026-10-14 23:59'));
        $this->assertSame(['2026-09-15', '2026-10-15'], [$start->toDateString(), $end->toDateString()]);

        [$start, $end] = FleetBudget::billingPeriod(15, $this->at('2026-10-15 00:00'));
        $this->assertSame(['2026-10-15', '2026-11-15'], [$start->toDateString(), $end->toDateString()]);

        // Day 31 clamps to short months on both ends.
        [$start, $end] = FleetBudget::billingPeriod(31, $this->at('2027-02-10 12:00'));
        $this->assertSame(['2027-01-31', '2027-02-28'], [$start->toDateString(), $end->toDateString()]);

        // Midnight Bangkok, not UTC: 2026-10-14 18:00 UTC is already the 15th in Bangkok.
        [$start] = FleetBudget::billingPeriod(15, CarbonImmutable::parse('2026-10-14 18:00', 'UTC'));
        $this->assertSame('2026-10-15', $start->toDateString());
    }

    public function test_usage_from_the_previous_period_stops_counting_on_billing_day(): void
    {
        $node = $this->node(['billing_day' => 15]);
        $rows = [
            $this->row($node, '2026-09-14', 500), // previous period
            $this->row($node, '2026-09-20', 70),
            $this->row($node, '2026-10-15', 5),
        ];

        $before = $this->evaluate([$node], $rows, '2026-10-14 12:00')->forNode($node->id);
        $this->assertEqualsWithDelta(70.0, $before->usedGb, 0.0001);
        $this->assertTrue($before->accepts(Tier::FREE));

        $after = $this->evaluate([$node], $rows, '2026-10-15 12:00')->forNode($node->id);
        $this->assertEqualsWithDelta(5.0, $after->usedGb, 0.0001);
    }
}
