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
        // Project Orders - Main project tracking table
        Schema::create('project_orders', function (Blueprint $table) {
            $table->id();
            $table->string('project_number')->unique(); // PRJ-YYYYMMdd-XXXX
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quotation_id')->nullable()->constrained()->onDelete('set null');

            // Project info
            $table->string('project_name');
            $table->text('project_description')->nullable();
            $table->string('project_type'); // web, mobile, blockchain, ai, etc.

            // Timeline
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();

            // Status
            $table->enum('status', [
                'pending',      // รอเริ่มงาน
                'in_progress',  // กำลังดำเนินการ
                'on_hold',      // พักงานชั่วคราว
                'review',       // รอตรวจสอบ
                'revision',     // แก้ไข
                'completed',    // เสร็จสิ้น
                'cancelled'     // ยกเลิก
            ])->default('pending');

            // Progress
            $table->integer('progress_percent')->default(0); // 0-100

            // Pricing (from quotation)
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // Additional info
            $table->string('repository_url')->nullable(); // GitHub/GitLab link
            $table->string('staging_url')->nullable(); // Staging/Preview URL
            $table->string('production_url')->nullable(); // Production URL

            $table->text('admin_notes')->nullable();
            $table->text('customer_notes')->nullable();

            $table->timestamps();
        });

        // Project Team Members - Responsible persons
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Staff member

            $table->string('name');
            $table->string('role'); // project_manager, developer, designer, tester
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_lead')->default(false); // หัวหน้าโครงการ

            $table->timestamps();
        });

        // Project Features/Milestones - ฟีเจอร์ที่ต้องทำ
        Schema::create('project_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_order_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // ลำดับการแสดงผล

            $table->enum('status', [
                'pending',      // รอดำเนินการ
                'in_progress',  // กำลังทำ
                'completed',    // เสร็จแล้ว
                'cancelled'     // ยกเลิก
            ])->default('pending');

            $table->integer('progress_percent')->default(0);
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // Project Progress Updates - รายงานความคืบหน้า
        Schema::create('project_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_feature_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('title');
            $table->text('description');

            $table->enum('type', [
                'update',       // อัพเดททั่วไป
                'milestone',    // ครบไมล์สโตน
                'issue',        // พบปัญหา
                'delivery',     // ส่งมอบงาน
                'meeting',      // นัดประชุม
                'change_request' // ขอเปลี่ยนแปลง
            ])->default('update');

            // Attachments (stored as JSON array of file paths)
            $table->json('attachments')->nullable();

            // For customer visibility
            $table->boolean('is_public')->default(true); // ลูกค้าเห็นหรือไม่
            $table->boolean('notify_customer')->default(false); // ส่งแจ้งเตือนลูกค้าหรือไม่

            $table->timestamps();
        });

        // Project Timeline Events - เหตุการณ์สำคัญ
        Schema::create('project_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_order_id')->constrained()->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date');

            $table->enum('type', [
                'start',        // เริ่มโครงการ
                'milestone',    // ไมล์สโตน
                'deadline',     // กำหนดส่ง
                'delivery',     // ส่งมอบ
                'meeting',      // ประชุม
                'payment',      // ชำระเงิน
                'end'           // จบโครงการ
            ])->default('milestone');

            $table->boolean('is_completed')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_timeline');
        Schema::dropIfExists('project_progress');
        Schema::dropIfExists('project_features');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_orders');
    }
};
