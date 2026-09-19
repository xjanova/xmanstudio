<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The TLD catalogue we sell, and what each one costs us.
     *
     * Prices are NOT hardcoded anywhere in the app — they are synced from the
     * upstream registrar's catalogue into this table, and the price the
     * customer sees is computed from cost + margin at render time. That is the
     * whole point: when the registrar raises the price of .com, we change
     * nothing and still make our margin.
     *
     * Two costs, not one. Registering a domain for the first year is often
     * discounted while the renewal is full price — storing only the first-year
     * cost is how a reseller ends up losing money in year two. Both are kept,
     * and the customer-facing renewal price is computed from renew_cost.
     *
     * Costs are stored in USD cents as integers, exactly as the upstream
     * catalogue returns them. No floats: a price that goes through a float and
     * back is a price that can be off by a satang, and these numbers are
     * multiplied by an exchange rate before anyone sees them.
     *
     * margin_percent is nullable on purpose — null means "use the global
     * default from settings", a number means this TLD overrides it. Popular
     * TLDs can carry a thinner margin as a loss leader without touching the
     * rest of the catalogue.
     */
    public function up(): void
    {
        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();

            // Stored without the leading dot ("com", not ".com") because that
            // is what the availability API expects on the way out.
            $table->string('tld', 63)->unique();

            // Catalogue item IDs from the registrar, e.g.
            // "hostingercom-domain-com-usd-1y". Needed verbatim when placing
            // the order — we never construct these ourselves.
            $table->string('item_id_register')->nullable();
            $table->string('item_id_renew')->nullable();

            $table->unsignedInteger('cost_usd_cents')->default(0);
            $table->unsignedInteger('renew_cost_usd_cents')->default(0);
            $table->unsignedInteger('transfer_cost_usd_cents')->nullable();

            // Per-TLD override; null falls back to the global margin setting.
            $table->decimal('margin_percent', 5, 2)->nullable();

            $table->boolean('is_active')->default(true);
            // Shown as a suggestion chip on the search page.
            $table->boolean('is_featured')->default(false);
            // Checked by default alongside the exact match the customer typed.
            $table->boolean('search_by_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(100);

            // Some TLDs demand extra registrant data (.th wants a company
            // registration, .de wants a local contact). The shape differs per
            // TLD, so the requirement list is data, not columns.
            $table->json('extra_fields')->nullable();

            $table->string('description_th')->nullable();
            $table->string('description_en')->nullable();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['is_active', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_tlds');
    }
};
