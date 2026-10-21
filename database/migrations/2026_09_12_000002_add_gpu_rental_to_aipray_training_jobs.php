<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Training on a rented GPU (SimplePod) instead of a permanent ML server.
 * AiprayGpuTrainingRunner drives these columns from the scheduler.
 *
 * Dated 2026-09-12 but first deployed after many later migrations, so each
 * column is added only if missing. `runner` is the one NOT NULL column and
 * carries a default, so existing rows (all local runs) fill in as 'local';
 * every timestamp is nullable, which strict MySQL/MariaDB require.
 */
return new class extends Migration
{
    /** Column => definition, in table order. */
    private function columns(): array
    {
        return [
            'runner' => fn (Blueprint $t) => $t->string('runner', 16)->default('local'),
            'gpu_state' => fn (Blueprint $t) => $t->string('gpu_state', 24)->nullable()->index(),
            'gpu_instance_id' => fn (Blueprint $t) => $t->string('gpu_instance_id', 64)->nullable(),
            'gpu_offer' => fn (Blueprint $t) => $t->string('gpu_offer', 255)->nullable(),
            'gpu_endpoint' => fn (Blueprint $t) => $t->string('gpu_endpoint', 512)->nullable(),
            'gpu_token' => fn (Blueprint $t) => $t->text('gpu_token')->nullable(),
            'gpu_price_per_hour' => fn (Blueprint $t) => $t->decimal('gpu_price_per_hour', 8, 4)->nullable(),
            'gpu_rented_at' => fn (Blueprint $t) => $t->timestamp('gpu_rented_at')->nullable(),
            'gpu_ready_at' => fn (Blueprint $t) => $t->timestamp('gpu_ready_at')->nullable(),
            'gpu_deadline_at' => fn (Blueprint $t) => $t->timestamp('gpu_deadline_at')->nullable(),
            'gpu_terminated_at' => fn (Blueprint $t) => $t->timestamp('gpu_terminated_at')->nullable(),
            'gpu_last_seen_at' => fn (Blueprint $t) => $t->timestamp('gpu_last_seen_at')->nullable(),
            'estimated_cost_usd' => fn (Blueprint $t) => $t->decimal('estimated_cost_usd', 8, 2)->nullable(),
            'max_cost_usd' => fn (Blueprint $t) => $t->decimal('max_cost_usd', 8, 2)->nullable(),
            'actual_cost_usd' => fn (Blueprint $t) => $t->decimal('actual_cost_usd', 8, 2)->nullable(),
            'dataset_path' => fn (Blueprint $t) => $t->string('dataset_path')->nullable(),
            'dataset_samples' => fn (Blueprint $t) => $t->integer('dataset_samples')->nullable(),
            'dataset_seconds' => fn (Blueprint $t) => $t->float('dataset_seconds')->nullable(),
            'artifact_path' => fn (Blueprint $t) => $t->string('artifact_path')->nullable(),
        ];
    }

    public function up(): void
    {
        $missing = array_filter(
            $this->columns(),
            fn (string $column) => ! Schema::hasColumn('aipray_training_jobs', $column),
            ARRAY_FILTER_USE_KEY,
        );
        if ($missing === []) {
            return;
        }

        Schema::table('aipray_training_jobs', function (Blueprint $table) use ($missing) {
            foreach ($missing as $add) {
                $add($table);
            }
        });
    }

    public function down(): void
    {
        $present = array_values(array_filter(
            array_keys($this->columns()),
            fn (string $column) => Schema::hasColumn('aipray_training_jobs', $column),
        ));
        if ($present === []) {
            return;
        }

        Schema::table('aipray_training_jobs', function (Blueprint $table) use ($present) {
            if (in_array('gpu_state', $present, true)) {
                $table->dropIndex(['gpu_state']);
            }
            $table->dropColumn($present);
        });
    }
};
