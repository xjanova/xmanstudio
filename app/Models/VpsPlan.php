<?php

namespace App\Models;

use App\Support\VpsPricing;
use Illuminate\Database\Eloquent\Model;

/**
 * One VPS size we rent out, with what each billing period costs us.
 *
 * Prices are derived, never stored — see the migration. Every period carries
 * its own catalogue price id, and a period without one cannot be bought, so
 * it is not offered.
 */
class VpsPlan extends Model
{
    protected $fillable = [
        'slug',
        'remote_item_id',
        'remote_name',
        'name',
        'category',
        'cpus',
        'memory_mb',
        'disk_mb',
        'bandwidth_mb',
        'network_mbps',
        'prices',
        'margin_percent',
        'is_active',
        'is_featured',
        'sort_order',
        'description_th',
        'description_en',
        'synced_at',
    ];

    protected $casts = [
        'cpus' => 'integer',
        'memory_mb' => 'integer',
        'disk_mb' => 'integer',
        'bandwidth_mb' => 'integer',
        'network_mbps' => 'integer',
        'prices' => 'array',
        'margin_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'synced_at' => 'datetime',
    ];

    public function instances()
    {
        return $this->hasMany(VpsInstance::class);
    }

    /**
     * Switched on AND orderable. A plan the catalogue gave no price id for is
     * not advertised — the domain shop learned that the expensive way, with a
     * TLD that could be searched, quoted and filled in, and then not bought.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('prices');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('cpus')->orderBy('memory_mb');
    }

    /**
     * The catalogue row for one billing period, or null when there is none.
     *
     * @return array{item_id:string,currency:string,first:int,renew:int,months:int}|null
     */
    public function price(string $period): ?array
    {
        $row = $this->prices[$period] ?? null;

        if (! is_array($row) || empty($row['item_id']) || (int) ($row['first'] ?? 0) <= 0) {
            return null;
        }

        return [
            'item_id' => (string) $row['item_id'],
            'currency' => strtoupper((string) ($row['currency'] ?? 'THB')),
            'first' => (int) $row['first'],
            'renew' => (int) (($row['renew'] ?? 0) ?: $row['first']),
            'months' => (int) ($row['months'] ?? VpsPricing::months($period)),
        ];
    }

    /**
     * The periods this plan can be bought for, in reading order.
     *
     * @return array<int,string>
     */
    public function periods(): array
    {
        return array_values(array_filter(
            array_keys(VpsPricing::PERIODS),
            fn (string $p) => $this->price($p) !== null,
        ));
    }

    public function isSellable(): bool
    {
        return $this->is_active && $this->periods() !== [];
    }

    /** What the customer pays for the first period. */
    public function firstPriceThb(string $period): float
    {
        $price = $this->price($period);

        return $price ? VpsPricing::sell($price['first'], $this->effectiveMargin(), $price['currency']) : 0.0;
    }

    /**
     * What every period after the first costs.
     *
     * Priced from the supplier's RENEWAL cost, which for these plans is up to
     * twice the first period. Charging the first-period price again is how a
     * reseller loses money on every server from month two.
     */
    public function renewPriceThb(string $period): float
    {
        $price = $this->price($period);

        return $price ? VpsPricing::sell($price['renew'], $this->effectiveMargin(), $price['currency']) : 0.0;
    }

    public function renewalIsDearer(string $period): bool
    {
        return $this->renewPriceThb($period) > $this->firstPriceThb($period);
    }

    /**
     * The per-month figure for the cheapest way in — what "from ฿X/month"
     * on a plan card means. Yearly plans are divided out so they can be
     * compared with monthly ones.
     */
    public function fromMonthlyThb(): float
    {
        $best = 0.0;

        foreach ($this->periods() as $period) {
            $monthly = $this->firstPriceThb($period) / max(1, VpsPricing::months($period));

            if ($best === 0.0 || $monthly < $best) {
                $best = $monthly;
            }
        }

        return $best > 0 ? ceil($best) : 0.0;
    }

    public function effectiveMargin(): float
    {
        return $this->margin_percent !== null ? (float) $this->margin_percent : VpsPricing::defaultMargin();
    }

    /** What the first period costs US, in the supplier's minor units — for margin reporting. */
    public function costCents(string $period, bool $renewal = false): int
    {
        $price = $this->price($period);

        return $price ? ($renewal ? $price['renew'] : $price['first']) : 0;
    }

    public function memoryLabel(): string
    {
        return self::sizeLabel($this->memory_mb) . ' RAM';
    }

    public function diskLabel(): string
    {
        return self::sizeLabel($this->disk_mb) . ' NVMe';
    }

    public function bandwidthLabel(): string
    {
        return self::sizeLabel($this->bandwidth_mb);
    }

    /**
     * The spec sheet frozen onto an instance at the moment it was bought.
     *
     * @return array{cpus:int,memory_mb:int,disk_mb:int,bandwidth_mb:int}
     */
    public function specSnapshot(): array
    {
        return [
            'cpus' => (int) $this->cpus,
            'memory_mb' => (int) $this->memory_mb,
            'disk_mb' => (int) $this->disk_mb,
            'bandwidth_mb' => (int) $this->bandwidth_mb,
        ];
    }

    /** 4096 → "4 GB", 4096000 → "4 TB". The catalogue reports everything in megabytes. */
    public static function sizeLabel(int|float|null $megabytes): string
    {
        $mb = (float) $megabytes;

        if ($mb >= 1_000_000) {
            return rtrim(rtrim(number_format($mb / 1_024_000, 1), '0'), '.') . ' TB';
        }

        if ($mb >= 1024) {
            return rtrim(rtrim(number_format($mb / 1024, 1), '0'), '.') . ' GB';
        }

        return (int) $mb . ' MB';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
