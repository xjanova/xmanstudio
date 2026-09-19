<?php

namespace App\Models;

use App\Support\DomainPricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One sellable TLD, with what it costs us and what we charge for it.
 *
 * The selling price is never stored. It is derived from the upstream cost,
 * the exchange rate and the margin every time it is asked for, so a change to
 * any of the three takes effect everywhere at once — the search page, the
 * order form and the renewal notice cannot disagree with each other.
 */
class DomainTld extends Model
{
    use HasFactory;

    protected $fillable = [
        'tld',
        'item_id_register',
        'item_id_renew',
        'cost_usd_cents',
        'renew_cost_usd_cents',
        'transfer_cost_usd_cents',
        'cost_currency',
        'margin_percent',
        'is_active',
        'is_featured',
        'search_by_default',
        'sort_order',
        'extra_fields',
        'description_th',
        'description_en',
        'synced_at',
    ];

    protected $casts = [
        'cost_usd_cents' => 'integer',
        'renew_cost_usd_cents' => 'integer',
        'transfer_cost_usd_cents' => 'integer',
        'margin_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'search_by_default' => 'boolean',
        'sort_order' => 'integer',
        'extra_fields' => 'array',
        'synced_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearchDefault($query)
    {
        return $query->where('is_active', true)->where('search_by_default', true);
    }

    /**
     * Price the customer pays to register for one year, in THB.
     */
    public function registerPriceThb(): float
    {
        return DomainPricing::sell($this->cost_usd_cents, $this->effectiveMargin(), $this->costCurrency());
    }

    /**
     * Price the customer pays to renew for one year, in THB.
     *
     * Falls back to the registration cost only when the catalogue gave us no
     * renewal price — never to the registration *price*, because the whole
     * reason renewal is tracked separately is that it is usually dearer.
     */
    public function renewPriceThb(): float
    {
        $cost = $this->renew_cost_usd_cents ?: $this->cost_usd_cents;

        return DomainPricing::sell($cost, $this->effectiveMargin(), $this->costCurrency());
    }

    /**
     * The money the registrar bills us in for this TLD.
     *
     * Rows that predate the catalogue knowing about currencies are USD, which
     * is what the seeded fallback prices were quoted in.
     */
    public function costCurrency(): string
    {
        return strtoupper((string) ($this->cost_currency ?: 'USD'));
    }

    /**
     * What this TLD costs us, written the way an operator reads it.
     */
    public function costLabel(): string
    {
        $major = $this->cost_usd_cents / 100;

        return $this->costCurrency() === 'THB'
            ? number_format($major, 2) . ' ฿'
            : '$' . number_format($major, 2);
    }

    /**
     * This TLD's margin, or the site-wide default when it has no override.
     */
    public function effectiveMargin(): float
    {
        if ($this->margin_percent !== null) {
            return (float) $this->margin_percent;
        }

        return DomainPricing::defaultMargin();
    }

    /**
     * True when the renewal costs noticeably more than the first year, which
     * the customer is entitled to know before they buy.
     */
    public function renewalIsDearer(): bool
    {
        return $this->renew_cost_usd_cents > $this->cost_usd_cents;
    }

    /**
     * Registrant fields this TLD demands beyond the usual ones.
     *
     * @return array<int,string>
     */
    public function requiredExtraFields(): array
    {
        $fields = $this->extra_fields ?? [];

        return array_values(array_filter($fields, 'is_string'));
    }

    public function getRouteKeyName(): string
    {
        return 'tld';
    }
}
