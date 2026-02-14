<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bug Reports - รายงานข้อผิดพลาดจากผลิตภัณฑ์ต่างๆ
     */
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();

            // Product identification
            $table->string('product_name', 50)->index(); // smschecker, xmanstudio, etc.
            $table->string('product_version', 20)->nullable();
            $table->string('report_type', 50)->default('bug'); // bug, misclassification, feature_request, etc.

            // Report details
            $table->string('title');
            $table->text('description');
            $table->json('metadata')->nullable(); // Flexible JSON field for product-specific data

            // For SMS Misclassification (when report_type = 'misclassification')
            // metadata structure:
            // {
            //   "transaction_id": 123,
            //   "bank": "SCB",
            //   "amount": "5000.00",
            //   "detected_type": "CREDIT",
            //   "correct_type": "DEBIT",
            //   "issue_type": "WRONG_TRANSACTION_TYPE",
            //   "original_message": "ชำระเงิน 5,000 บาท...",
            //   "sender_address": "SCB",
            //   "timestamp": "2026-02-14T10:30:00Z",
            //   "device_info": {...}
            // }

            // User information
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_email')->nullable();
            $table->string('device_id')->nullable()->index();

            // Status tracking
            $table->enum('status', ['new', 'analyzing', 'confirmed', 'fixed', 'wont_fix', 'duplicate', 'closed'])
                ->default('new')->index();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('severity', ['minor', 'moderate', 'major', 'critical'])->default('moderate');

            // GitHub integration
            $table->string('github_issue_url')->nullable();
            $table->integer('github_issue_number')->nullable()->index();
            $table->timestamp('posted_to_github_at')->nullable();

            // Analysis tracking
            $table->boolean('is_analyzed')->default(false)->index();
            $table->text('analysis_notes')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->foreignId('analyzed_by')->nullable()->constrained('users')->onDelete('set null');

            // Resolution tracking
            $table->boolean('is_fixed')->default(false)->index();
            $table->text('fix_notes')->nullable();
            $table->string('fixed_in_version', 20)->nullable();
            $table->timestamp('fixed_at')->nullable();

            // Environment info
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->text('stack_trace')->nullable();
            $table->json('additional_info')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['product_name', 'report_type', 'status']);
            $table->index(['product_name', 'created_at']);
            $table->index(['is_analyzed', 'is_fixed']);
        });

        // Bug Report Comments - สำหรับการอภิปรายและติดตามปัญหา
        Schema::create('bug_report_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('comment');
            $table->boolean('is_internal')->default(false); // Internal note vs public comment
            $table->timestamps();

            $table->index(['bug_report_id', 'created_at']);
        });

        // Bug Report Attachments - สำหรับแนบไฟล์หลักฐาน (screenshots, logs, etc.)
        Schema::create('bug_report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_report_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_type', 50)->nullable(); // image, log, json, etc.
            $table->bigInteger('file_size')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('bug_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_report_attachments');
        Schema::dropIfExists('bug_report_comments');
        Schema::dropIfExists('bug_reports');
    }
};
