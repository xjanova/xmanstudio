<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add hierarchy fields to affiliates
        Schema::table('affiliates', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id')
                ->constrained('affiliates')->onDelete('set null');
            $table->integer('depth')->default(0)->after('parent_id');
            $table->string('path')->nullable()->after('depth');

            $table->index('parent_id');
        });

        // 2. Upgrade affiliate_commissions for multi-source support
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            // Add source tracking columns
            $table->string('source_type', 50)->nullable()->after('order_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('source_description')->nullable()->after('source_id');

            $table->index(['source_type', 'source_id']);
        });

        // Make order_id nullable (rentals don't have orders)
        // SQLite doesn't support ALTER COLUMN, so we need to handle this carefully
        if (config('database.default') !== 'sqlite') {
            Schema::table('affiliate_commissions', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->change();
            });
        }

        // Backfill source_type for existing records
        \Illuminate\Support\Facades\DB::table('affiliate_commissions')
            ->whereNull('source_type')
            ->whereNotNull('order_id')
            ->update([
                'source_type' => 'tping',
            ]);

        // 3. Add affiliate tracking to rental_payments
        if (Schema::hasTable('rental_payments')) {
            Schema::table('rental_payments', function (Blueprint $table) {
                $table->foreignId('affiliate_id')->nullable()->after('admin_notes')
                    ->constrained('affiliates')->onDelete('set null');
                $table->string('referral_code', 32)->nullable()->after('affiliate_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rental_payments') && Schema::hasColumn('rental_payments', 'affiliate_id')) {
            Schema::table('rental_payments', function (Blueprint $table) {
                $table->dropForeign(['affiliate_id']);
                $table->dropColumn(['affiliate_id', 'referral_code']);
            });
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id', 'source_description']);
        });

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['parent_id', 'depth', 'path']);
        });
    }
};
