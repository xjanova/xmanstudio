<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A server a customer rents from us.
     *
     * Written BEFORE the supplier is called, for the same reason the domain
     * registration row is: the customer's money moves inside our transaction,
     * the purchase happens outside it, and if PHP dies in between this row —
     * `pending`, with the payment attached — is the only record that anything
     * was paid for. The reconciliation job finishes it or refunds it.
     *
     * Deliberately NOT stored: the root password. It goes to the supplier once
     * and is forgotten. When a purchase has to be finished later (a 202), the
     * password waits in the cache, encrypted, for a few hours — never in a
     * column that ends up in every database backup.
     *
     * `state` is the machine's power state as upstream last reported it
     * (running, stopped, …) — separate from `status`, which is where the
     * rental is in its life (provisioning, active, expired …). A stopped
     * machine is still an active rental.
     *
     * status is a plain string, not an enum, for the reason written on the
     * quotations table: an enum value the code knows and the column does not is
     * a 500 on MySQL and silence on SQLite.
     */
    public function up(): void
    {
        Schema::create('vps_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vps_plan_id')->nullable()->constrained('vps_plans')->nullOnDelete();

            // What was bought, frozen at the time — the plan row can be renamed
            // or resized by the next catalogue sync.
            $table->string('plan_name', 120);
            $table->json('specs')->nullable();
            $table->string('period', 8);
            $table->unsignedSmallInteger('months')->default(1);

            $table->string('status', 24)->default('pending');
            $table->string('hostname', 253);
            $table->unsignedInteger('template_id')->nullable();
            $table->string('template_name', 160)->nullable();
            $table->unsignedInteger('data_center_id')->nullable();
            $table->string('data_center_name', 120)->nullable();

            $table->unsignedBigInteger('remote_vm_id')->nullable()->index();
            $table->string('remote_order_id', 64)->nullable();
            $table->string('remote_subscription_id', 64)->nullable()->index();

            $table->string('state', 24)->nullable();
            $table->string('ipv4', 64)->nullable();
            $table->string('ipv6', 128)->nullable();

            $table->boolean('auto_renew')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('renewal_notice_sent_at')->nullable();
            // Which reminders went out this period, so a daily job cannot send
            // the same one twice. Cleared by a successful renewal.
            $table->json('reminders_sent')->nullable();

            // Set when the machine had to be installed without the password the
            // customer chose (it outlived its few hours in the cache): the
            // supplier generated one nobody knows, so the page asks for a new one.
            $table->boolean('needs_password_reset')->default(false);

            $table->timestamp('last_polled_at')->nullable();
            $table->unsignedInteger('poll_attempts')->default(0);
            $table->unsignedSmallInteger('setup_attempts')->default(0);
            // Can quote the supplier by name. Hidden from serialisation.
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['auto_renew', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_instances');
    }
};
