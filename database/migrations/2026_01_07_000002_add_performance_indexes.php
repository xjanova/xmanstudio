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
            $table->index('slug');
            $table->index('sku');
            $table->index('is_active');
            $table->index(['category_id', 'is_active']);
            $table->index('created_at');
        });

        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_number');
            $table->index('user_id');
            $table->index('payment_status');
            $table->index('status');
            $table->index('customer_email');
            $table->index(['user_id', 'payment_status']);
            $table->index(['payment_status', 'created_at']);
            $table->index('created_at');
        });

        // Order items indexes
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('product_id');
        });

        // License keys indexes
        Schema::table('license_keys', function (Blueprint $table) {
            $table->index('license_key');
            $table->index('order_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('license_type');
            $table->index(['status', 'expires_at']);
            $table->index('machine_fingerprint');
        });

        // User rentals indexes
        Schema::table('user_rentals', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('rental_package_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('expires_at');
        });

        // Rental payments indexes
        Schema::table('rental_payments', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('user_rental_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Carts indexes
        Schema::table('carts', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('session_id');
        });

        // Cart items indexes
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('cart_id');
            $table->index('product_id');
        });

        // Categories indexes
        Schema::table('categories', function (Blueprint $table) {
            $table->index('slug');
            $table->index('is_active');
            $table->index('order');
        });

        // Users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('email'); // If not already indexed by unique constraint
            $table->index('role');
            $table->index('is_active');
            $table->index('line_uid');
        });

        // Support tickets indexes (if table exists)
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->index('user_id');
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
            $table->dropIndex(['slug']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['category_id', 'is_active']);
            $table->dropIndex(['created_at']);
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_number']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['customer_email']);
            $table->dropIndex(['user_id', 'payment_status']);
            $table->dropIndex(['payment_status', 'created_at']);
            $table->dropIndex(['created_at']);
        });

        // Order items
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['product_id']);
        });

        // License keys
        Schema::table('license_keys', function (Blueprint $table) {
            $table->dropIndex(['license_key']);
            $table->dropIndex(['order_id']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['license_type']);
            $table->dropIndex(['status', 'expires_at']);
            $table->dropIndex(['machine_fingerprint']);
        });

        // User rentals
        Schema::table('user_rentals', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['rental_package_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['status', 'expires_at']);
            $table->dropIndex(['expires_at']);
        });

        // Rental payments
        Schema::table('rental_payments', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['user_rental_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        // Carts
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['session_id']);
        });

        // Cart items
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['cart_id']);
            $table->dropIndex(['product_id']);
        });

        // Categories
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['order']);
        });

        // Users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['line_uid']);
        });

        // Support tickets
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropIndex(['status']);
                $table->dropIndex(['priority']);
                $table->dropIndex(['status', 'priority']);
                $table->dropIndex(['created_at']);
            });
        }
    }
};
