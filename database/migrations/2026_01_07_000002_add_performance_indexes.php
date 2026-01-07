<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            // Skip slug - already has unique index
            // Skip sku - already has unique index from previous migration
            $table->index('is_active');
            $table->index(['category_id', 'is_active']);
            $table->index('created_at');
        });

        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            // Skip order_number - already has unique index
            // Skip user_id - foreignId creates index automatically
            $table->index('payment_status');
            $table->index('status');
            $table->index('customer_email');
            $table->index(['user_id', 'payment_status']);
            $table->index(['payment_status', 'created_at']);
            $table->index('created_at');
        });

        // Order items indexes
        // Skip - order_id and product_id are foreignIds with automatic indexes

        // License keys indexes
        Schema::table('license_keys', function (Blueprint $table) {
            // Skip license_key - already has unique index
            // Skip order_id, product_id - foreignIds with automatic indexes
            $table->index('status');
            if (Schema::hasColumn('license_keys', 'license_type')) {
                $table->index('license_type');
            }
            $table->index(['status', 'expires_at']);
            if (Schema::hasColumn('license_keys', 'machine_fingerprint')) {
                $table->index('machine_fingerprint');
            }
        });

        // User rentals indexes
        Schema::table('user_rentals', function (Blueprint $table) {
            // Skip user_id, rental_package_id - foreignIds with automatic indexes
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('expires_at');
        });

        // Rental payments indexes
        Schema::table('rental_payments', function (Blueprint $table) {
            // Skip user_id, user_rental_id - foreignIds with automatic indexes
            $table->index('status');
            $table->index('payment_method');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Carts indexes
        Schema::table('carts', function (Blueprint $table) {
            // Skip user_id - foreignId with automatic index
            $table->index('session_id');
        });

        // Cart items indexes
        // Skip - cart_id and product_id are foreignIds with automatic indexes

        // Categories indexes
        Schema::table('categories', function (Blueprint $table) {
            // Skip slug - already has unique index
            $table->index('is_active');
            $table->index('order');
        });

        // Users indexes
        Schema::table('users', function (Blueprint $table) {
            // Skip email - already has unique index
            $table->index('role');
            if (Schema::hasColumn('users', 'is_active')) {
                $table->index('is_active');
            }
            if (Schema::hasColumn('users', 'line_uid')) {
                $table->index('line_uid');
            }
        });

        // Support tickets indexes (if table exists)
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                // Skip user_id - foreignId with automatic index
                $table->index('status');
                $table->index('priority');
                $table->index(['status', 'priority']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Products
        Schema::table('products', function (Blueprint $table) {
            // Skip slug and sku - managed by unique constraints
            $table->dropIndex(['is_active']);
            $table->dropIndex(['category_id', 'is_active']);
            $table->dropIndex(['created_at']);
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            // Skip order_number - managed by unique constraint
            // Skip user_id - managed by foreign key
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['customer_email']);
            $table->dropIndex(['user_id', 'payment_status']);
            $table->dropIndex(['payment_status', 'created_at']);
            $table->dropIndex(['created_at']);
        });

        // Order items - no indexes to drop (foreignIds only)

        // License keys
        Schema::table('license_keys', function (Blueprint $table) {
            // Skip license_key - managed by unique constraint
            // Skip order_id, product_id - managed by foreign keys
            $table->dropIndex(['status']);
            if (Schema::hasColumn('license_keys', 'license_type')) {
                $table->dropIndex(['license_type']);
            }
            $table->dropIndex(['status', 'expires_at']);
            if (Schema::hasColumn('license_keys', 'machine_fingerprint')) {
                $table->dropIndex(['machine_fingerprint']);
            }
        });

        // User rentals
        Schema::table('user_rentals', function (Blueprint $table) {
            // Skip user_id, rental_package_id - managed by foreign keys
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['status', 'expires_at']);
            $table->dropIndex(['expires_at']);
        });

        // Rental payments
        Schema::table('rental_payments', function (Blueprint $table) {
            // Skip user_id, user_rental_id - managed by foreign keys
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        // Carts
        Schema::table('carts', function (Blueprint $table) {
            // Skip user_id - managed by foreign key
            $table->dropIndex(['session_id']);
        });

        // Cart items - no indexes to drop (foreignIds only)

        // Categories
        Schema::table('categories', function (Blueprint $table) {
            // Skip slug - managed by unique constraint
            $table->dropIndex(['is_active']);
            $table->dropIndex(['order']);
        });

        // Users
        Schema::table('users', function (Blueprint $table) {
            // Skip email - managed by unique constraint
            $table->dropIndex(['role']);
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropIndex(['is_active']);
            }
            if (Schema::hasColumn('users', 'line_uid')) {
                $table->dropIndex(['line_uid']);
            }
        });

        // Support tickets
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                // Skip user_id - managed by foreign key
                $table->dropIndex(['status']);
                $table->dropIndex(['priority']);
                $table->dropIndex(['status', 'priority']);
                $table->dropIndex(['created_at']);
            });
        }
    }
};
