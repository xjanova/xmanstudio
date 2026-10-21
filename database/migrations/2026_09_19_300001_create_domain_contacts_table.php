<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The registrant behind each domain — the customer, never us.
     *
     * We buy through our own registrar account, but the domain must be
     * registered in the customer's name. A domain whose WHOIS owner is the
     * reseller is a domain the customer cannot transfer away, cannot prove
     * they own, and cannot recover if we disappear. Getting this wrong is not
     * a bug, it is taking someone's property.
     *
     * So every registration carries a contact row, the contact is mirrored
     * upstream as a WHOIS profile, and remote_whois_id is the handle we pass
     * when placing the order. The mirror is created lazily on first use and
     * reused afterwards — the registrar rate-limits us at 90 requests a
     * minute and a profile per order would burn that for nothing.
     *
     * remote_whois_id is nullable because the row exists locally before it
     * exists upstream, and because the upstream profile can be deleted out
     * from under us. A null means "push it again", not "broken".
     *
     * This table holds personal data under PDPA. It is deliberately separate
     * from users so it can be exported and erased on its own, and so a
     * customer can hold several registrant identities (personal, company)
     * without either one becoming their account identity.
     */
    public function up(): void
    {
        Schema::create('domain_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('remote_whois_id')->nullable();

            $table->string('label')->nullable();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('organization')->nullable();
            $table->string('email');
            // E.164 with the country code split out, which is how the
            // registrar wants it — "+66" and "812345678", not one string.
            $table->string('phone_country_code', 8);
            $table->string('phone', 32);

            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('zip', 32);
            // ISO 3166-1 alpha-2, uppercase.
            $table->string('country', 2);

            // TLD-specific registrant data keyed by TLD, e.g.
            // {"th": {"company_registration_id": "0105..."}}. Free-form
            // because each registry asks for something different.
            $table->json('extra_fields')->nullable();

            $table->boolean('is_default')->default(false);

            // The registrant row is always kept — the registration points at
            // it and it is the proof of who owns the domain. This only says
            // whether to offer it as a saved option on the next order, which
            // is what the "save for next time" box on the form actually asks.
            $table->boolean('hidden_from_picker')->default(false);

            // Set when the upstream mirror last succeeded, so a stale profile
            // can be detected and re-pushed after the customer edits it.
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_contacts');
    }
};
