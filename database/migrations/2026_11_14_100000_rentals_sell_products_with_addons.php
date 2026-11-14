<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Rentals for more than one product, with add-ons — first for XMAN MLM (mlm.xman4289.com).
 *
 *  - rental_packages.product_key / code: which product a package rents ("mlm") and its stable
 *    name ("mlm-core"; the monthly and yearly rows share it). Old packages stay null = the
 *    original generic rental, and keep their one-active-rental rule among themselves.
 *  - user_rentals.uuid: the id the MLM webhook names a rental by. period_starts_at: when the
 *    current paid cycle began — the denominator of the add-on proration.
 *  - rental_addons: the add-on catalogue, prices in satang (integer — never float money).
 *  - user_rental_addons: an add-on bought for one rental. It runs and renews with that rental.
 *
 * The MLM catalogue is inserted here so it reaches production with the deploy (auto-deploy runs
 * migrations, not seeders). Packages go in switched OFF: the prices are the first-round proposal
 * (price_is_proposal) and an admin turns them on at /admin/rentals/packages once settled.
 * Re-running never overwrites a row an admin has edited — rows are matched by code and skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_packages', function (Blueprint $table) {
            $table->string('product_key', 40)->nullable()->after('id');
            $table->string('code', 64)->nullable()->after('product_key');
            $table->boolean('price_is_proposal')->default(false)->after('original_price');
            $table->index(['product_key', 'code']);
        });

        Schema::table('user_rentals', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->timestamp('period_starts_at')->nullable()->after('starts_at');
        });

        DB::table('user_rentals')->whereNull('uuid')->select('id')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('user_rentals')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
                }
            });

        Schema::create('rental_addons', function (Blueprint $table) {
            $table->id();
            $table->string('product_key', 40)->index();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->text('description_th')->nullable();
            // Price for one full cycle of the main package, per unit.
            $table->unsignedInteger('price_monthly_satang');
            $table->unsignedInteger('price_yearly_satang');
            // "ผู้ให้บริการ" for per-provider pricing; null = one per rental.
            $table->string('unit_label_th', 50)->nullable();
            $table->unsignedSmallInteger('max_quantity')->default(1);
            // Add-ons in one group replace each other (member caps 5k / 20k / unlimited): one per rental.
            $table->string('exclusive_group', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('price_is_proposal')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_rental_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_addon_id')->constrained()->restrictOnDelete();
            // The payment that bought it: the first checkout, or a later add-on purchase.
            $table->foreignId('rental_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('status', 20)->default('pending'); // pending | active | cancelled
            // What that payment charged for it, and — for a mid-cycle purchase — the days it covered.
            $table->unsignedInteger('price_satang')->default(0);
            $table->unsignedSmallInteger('days_charged')->nullable();
            $table->unsignedSmallInteger('cycle_days')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_rental_id', 'status']);
        });

        $this->insertMlmCatalogue();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rental_addons');
        Schema::dropIfExists('rental_addons');

        // The MLM packages only, and only if nobody ever rented one (user_rentals cascade on delete).
        DB::table('rental_packages')->where('product_key', 'mlm')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('user_rentals')
                ->whereColumn('user_rentals.rental_package_id', 'rental_packages.id'))
            ->delete();

        Schema::table('user_rentals', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn(['uuid', 'period_starts_at']);
        });

        Schema::table('rental_packages', function (Blueprint $table) {
            $table->dropIndex(['product_key', 'code']);
            $table->dropColumn(['product_key', 'code', 'price_is_proposal']);
        });
    }

    private function insertMlmCatalogue(): void
    {
        $now = now();
        $features = [
            'โครงสร้างแผน 1 แบบ พร้อมโมดูลโบนัสครบ',
            'เครื่องมือกันจ่ายเกิน (Overpay Guard)',
            'หน้าร้าน + หลังบ้านเจ้าของและสมาชิก',
            'ธีมสำเร็จรูป + ซับโดเมนของคุณ',
            'รับโอนเข้าบัญชีธนาคารของคุณเอง',
            'สมาชิกสูงสุด 1,000 คน · ทีมงาน 3 คน',
        ];

        // Monthly and yearly rows of one package share its code; the cycle picks the row.
        $packages = [
            ['monthly', 690, 'XMAN MLM Core (Monthly)', 'XMAN MLM แพ็กหลัก รายเดือน', 1],
            ['yearly', 6900, 'XMAN MLM Core (Yearly)', 'XMAN MLM แพ็กหลัก รายปี (จ่าย 10 เดือน)', 2],
        ];
        foreach ($packages as [$cycle, $price, $name, $nameTh, $sort]) {
            $exists = DB::table('rental_packages')
                ->where('code', 'mlm-core')->where('duration_type', $cycle)->exists();
            if ($exists) {
                continue;
            }
            DB::table('rental_packages')->insert([
                'product_key' => 'mlm',
                'code' => 'mlm-core',
                'name' => $name,
                'name_th' => $nameTh,
                'description' => 'Run your own MLM business on mlm.xman4289.com',
                'description_th' => 'ระบบธุรกิจเครือข่ายของคุณเองบน mlm.xman4289.com — เพิ่มตัวเลือกเสริมได้ทีหลัง',
                'duration_type' => $cycle,
                'duration_value' => 1,
                'price' => $price,
                'price_is_proposal' => true,
                'currency' => 'THB',
                'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
                'is_active' => false,
                'is_featured' => $cycle === 'yearly',
                'is_popular' => false,
                'sort_order' => 100 + $sort,
                'has_trial' => false,
                'trial_days' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // [code, name, name_th, description_th, baht per month, unit, max qty, group]
        $addons = [
            ['mlm-extra-structure', 'Extra plan structure', 'โครงสร้างแผนเพิ่ม / Hybrid', 'เพิ่มโครงสร้างแผนอีก 1 แบบ หรือผสมเป็น Hybrid', 190, 'แบบ', 2, null],
            ['mlm-members-5k', 'Up to 5,000 members', 'สมาชิกสูงสุด 5,000 คน', 'ขยายจาก 1,000 คน', 190, null, 1, 'members'],
            ['mlm-members-20k', 'Up to 20,000 members', 'สมาชิกสูงสุด 20,000 คน', 'ขยายจาก 1,000 คน', 490, null, 1, 'members'],
            ['mlm-members-unlimited', 'Unlimited members', 'สมาชิกไม่จำกัด', 'ไม่จำกัดจำนวนสมาชิก', 990, null, 1, 'members'],
            ['mlm-custom-domain', 'Custom domain + SSL', 'โดเมนของคุณเอง + SSL', 'ใช้โดเมนของคุณแทนซับโดเมน', 90, null, 1, null],
            ['mlm-auto-verify', 'Automatic transfer verification', 'ตรวจยอดโอนอัตโนมัติ', 'ยืนยันยอดโอนเข้าบัญชีของคุณให้อัตโนมัติ', 290, null, 1, null],
            ['mlm-page-builder', 'Page builder + premium themes', 'ตัวสร้างหน้าเว็บ + ธีมพรีเมียม', 'ออกแบบหน้าร้านเองด้วยบล็อกสำเร็จรูป', 190, null, 1, null],
            ['mlm-ocpb-kit', 'OCPB filing kit', 'ชุดเอกสารยื่น สคบ.', 'สร้างเอกสารยื่นจดทะเบียนขายตรงจากข้อมูลในระบบ', 190, null, 1, null],
            ['mlm-flowaccount', 'FlowAccount connector', 'เชื่อมต่อ FlowAccount', 'ส่งรายการขายเข้าบัญชี FlowAccount ของคุณ', 190, null, 1, null],
            ['mlm-gateway', 'Payment gateway connector', 'เชื่อมเกตเวย์รับชำระเงิน', 'คิดราคาต่อผู้ให้บริการที่เชื่อม', 190, 'ผู้ให้บริการ', 5, null],
        ];
        foreach ($addons as $i => [$code, $name, $nameTh, $description, $baht, $unit, $max, $group]) {
            if (DB::table('rental_addons')->where('code', $code)->exists()) {
                continue;
            }
            DB::table('rental_addons')->insert([
                'product_key' => 'mlm',
                'code' => $code,
                'name' => $name,
                'name_th' => $nameTh,
                'description_th' => $description,
                'price_monthly_satang' => $baht * 100,
                'price_yearly_satang' => $baht * 100 * 10,   // a year costs 10 months
                'unit_label_th' => $unit,
                'max_quantity' => $max,
                'exclusive_group' => $group,
                'is_active' => true,
                'price_is_proposal' => true,
                'sort_order' => ($i + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
