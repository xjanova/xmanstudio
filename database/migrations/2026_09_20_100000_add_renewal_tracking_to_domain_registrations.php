<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a renewal needs to be honest about itself.
     *
     * The domain page already tells the customer, in both languages, that we
     * charge their wallet thirty days before expiry and "always warn you
     * first". Neither half existed: nothing renewed anything, and nothing sent
     * a warning. These two columns are what make the sentence true.
     *
     * renewal_notice_sent_at is the lock on the warning — one per period, and
     * the charge refuses to run until a notice has been sitting in the
     * customer's inbox for a few days.
     *
     * renewal_of ties the payment row to the domain it paid for. A renewal
     * reuses this table (kind = 'renew'), so without it the customer's domain
     * list would grow a second copy of the same name every year.
     */
    public function up(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            $table->timestamp('renewal_notice_sent_at')->nullable()->after('expires_at');

            $table->unsignedBigInteger('renewal_of')->nullable()->after('kind');
            $table->index('renewal_of');
        });
    }

    public function down(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            $table->dropIndex(['renewal_of']);
            $table->dropColumn(['renewal_notice_sent_at', 'renewal_of']);
        });
    }
};
