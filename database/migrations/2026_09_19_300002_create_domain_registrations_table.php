<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A domain the customer bought from us.
     *
     * This row is the only record that the customer's money turned into a
     * domain, so it is written BEFORE the upstream call, not after. The order
     * is: take the money inside a transaction, write this row as `pending`,
     * then go out to the registrar. If the registrar call dies halfway, or the
     * server is restarted mid-request, the row is still here with a wallet
     * transaction attached and the reconciliation job can finish or refund it.
     * A row that only appears after a successful API call is a row that loses
     * a customer's money the first time the network blinks.
     *
     * idempotency_key carries the same job. The customer double-taps "buy",
     * two requests race, and both find enough balance. The unique index makes
     * the second one lose at the database rather than at a check that has
     * already gone stale. The key is derived from user + domain + the pricing
     * quote, so a genuine second purchase of a domain that later expired is
     * still allowed.
     *
     * Both what the customer paid and what it cost us are stored, in the
     * currency each was denominated in. price_thb is the receipt; cost_usd_cents
     * and fx_rate are what let us answer "did we actually make money on this"
     * a year later, when the rate and the registrar's price have both moved.
     *
     * status is a plain string, not an enum — same reasoning as the quotations
     * table, where a value the enum did not know about was a 500 on MySQL and
     * silence on SQLite. The whitelist lives in the model.
     */
    public function up(): void
    {
        Schema::create('domain_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Full name including the TLD, lowercase, no trailing dot.
            $table->string('domain')->index();
            $table->string('tld', 63);

            $table->string('status', 24)->default('pending');
            // 'register' or 'renew' — a renewal reuses this table so the
            // customer sees one history per domain.
            $table->string('kind', 16)->default('register');

            $table->foreignId('domain_contact_id')->nullable()
                ->constrained('domain_contacts')->nullOnDelete();

            // Upstream handles. subscription_id is what identifies the domain
            // in the registrar's billing once the order completes; order_id is
            // the purchase itself. Both nullable until the call returns.
            $table->string('remote_order_id')->nullable();
            $table->string('remote_subscription_id')->nullable();

            // What the customer paid us, in THB, VAT-inclusive as displayed.
            $table->decimal('price_thb', 12, 2);
            // What it cost us upstream, as the catalogue quoted it.
            $table->unsignedInteger('cost_usd_cents')->default(0);
            // The USD→THB rate used at the moment of sale.
            $table->decimal('fx_rate', 10, 4)->default(0);
            $table->unsignedSmallInteger('years')->default(1);

            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete();
            // Set when a failed registration was paid back, so a retry of the
            // refund is a no-op rather than a second credit.
            $table->foreignId('refund_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete();

            $table->string('idempotency_key', 80)->unique();

            $table->boolean('privacy_protection')->default(true);
            $table->boolean('auto_renew')->default(false);

            // Snapshot of the nameservers we last pushed, so the DNS screen
            // can tell "customer moved this elsewhere" from "never set".
            $table->json('nameservers')->nullable();

            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            // When the reconciliation job last asked upstream about a
            // still-pending order, so it can back off instead of hammering.
            $table->timestamp('last_polled_at')->nullable();
            $table->unsignedSmallInteger('poll_attempts')->default(0);

            // Operator-facing only. Never rendered to the customer: it can
            // carry upstream wording that would give away who the registrar is.
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_polled_at']);
            $table->index(['auto_renew', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_registrations');
    }
};
