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
        Schema::create('metal_x_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->unique();
            $table->string('channel_name');
            $table->string('reason'); // gambling, spam, unsafe, harassment, etc.
            $table->text('notes')->nullable();
            $table->integer('violation_count')->default(1);
            $table->timestamp('first_violation_at');
            $table->timestamp('last_violation_at');
            $table->boolean('is_blocked')->default(true);
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->timestamps();

            $table->foreign('blocked_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->index(['is_blocked', 'reason']);
            $table->index('channel_id');
        });

        // Add blacklist tracking to comments table
        Schema::table('metal_x_comments', function (Blueprint $table) {
            $table->boolean('is_blacklisted_author')->default(false)->after('is_hidden');
            $table->string('violation_type')->nullable()->after('is_blacklisted_author'); // gambling, unsafe, spam, harassment
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metal_x_comments', function (Blueprint $table) {
            $table->dropColumn(['is_blacklisted_author', 'violation_type', 'deleted_at']);
        });

        Schema::dropIfExists('metal_x_blacklist');
    }
};
