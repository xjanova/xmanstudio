<?php

namespace App\Services\LocalVpnFleet;

/** Every node's budget plus the fleet-wide projection, at one moment. */
final class FleetBudgetSnapshot
{
    /**
     * @param  array<int, NodeBudget>  $nodes  keyed by node id
     */
    public function __construct(
        public readonly array $nodes,
        public readonly float $costSoFarThb,
        public readonly float $projectedCostThb,
        public readonly float $monthlyBudgetThb,
        public readonly bool $overBudget,
    ) {}

    public function forNode(int $nodeId): ?NodeBudget
    {
        return $this->nodes[$nodeId] ?? null;
    }
}
