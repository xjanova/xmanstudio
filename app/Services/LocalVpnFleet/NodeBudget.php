<?php

namespace App\Services\LocalVpnFleet;

use App\Support\LocalVpnFleet\Tier;
use Carbon\CarbonImmutable;

/** One node's place in its billing month, as worked out by FleetBudget. */
final class NodeBudget
{
    /**
     * @param  array<string, bool>  $accepts  tier => still takes new users of that tier
     */
    public function __construct(
        public readonly int $nodeId,
        public readonly CarbonImmutable $periodStart,
        public readonly CarbonImmutable $periodEnd,
        public readonly int $usedBytes,
        public readonly float $usedGb,
        public readonly int $quotaGb,
        public readonly float $usedPct,
        public readonly float $projectedGb,
        public readonly float $costSoFarThb,
        public readonly float $projectedCostThb,
        public readonly bool $overageAllowed,
        public readonly array $accepts,
        public readonly bool $active,
    ) {}

    public function accepts(string $tier): bool
    {
        return $this->active && ($this->accepts[$tier] ?? false);
    }

    /** Lowest tier still taken, or null when the node takes nobody new. */
    public function minTier(): ?string
    {
        foreach (Tier::ALL as $tier) {
            if ($this->accepts($tier)) {
                return $tier;
            }
        }

        return null;
    }

    public function acceptNew(): bool
    {
        return $this->minTier() !== null;
    }

    /** The `budget` object of the node sync response. */
    public function toSyncArray(): array
    {
        return [
            'accept_new' => $this->acceptNew(),
            'min_tier' => $this->minTier(),
            'used_gb' => round($this->usedGb, 2),
            'quota_gb' => $this->quotaGb,
        ];
    }
}
