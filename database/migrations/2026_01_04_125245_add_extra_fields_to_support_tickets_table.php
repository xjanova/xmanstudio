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
        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'category')) {
                $table->string('category')->default('general')->after('subject');
            }
            if (! Schema::hasColumn('support_tickets', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('user_id')
                    ->constrained('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('support_tickets', 'attachments')) {
                $table->json('attachments')->nullable()->after('message');
            }
            if (! Schema::hasColumn('support_tickets', 'last_reply_at')) {
                $table->timestamp('last_reply_at')->nullable()->after('responded_at');
            }
            if (! Schema::hasColumn('support_tickets', 'last_reply_by')) {
                $table->foreignId('last_reply_by')->nullable()->after('last_reply_at')
                    ->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['category', 'assigned_to', 'attachments', 'last_reply_at', 'last_reply_by']);
        });
    }
};
