<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metal_x_promo_comments', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('status');
            $table->boolean('should_pin')->default(false)->after('is_pinned');
            $table->timestamp('pinned_at')->nullable()->after('posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('metal_x_promo_comments', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'should_pin', 'pinned_at']);
        });
    }
};
