<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Disk speed-test results that WinXTools uploads anonymously, so the app can show what is
 * normal for a drive model as measured on real users' PCs (WinxDiskBenchmarkController).
 *
 * Nothing here points at a person: install_id is a random id the app makes per installation —
 * not its license key, machine id or a user — and the uploader's IP is never stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winx_disk_benchmarks', function (Blueprint $table) {
            $table->id();
            // Counts distinct PCs and caps each installation's uploads per day
            $table->string('install_id', 36)->index();
            $table->string('app_version', 20);
            $table->unsignedInteger('os_build')->nullable();

            // As the drive reports it (trimmed), and the key rows are matched on:
            // uppercase letters and digits only, so "WD_BLACK SN770" finds "wd black sn770"
            $table->string('model', 80);
            $table->string('model_key', 80)->index();
            $table->string('kind', 12);
            $table->string('bus', 16);
            $table->string('media', 8);
            $table->unsignedInteger('capacity_gb');
            $table->string('firmware', 32)->nullable();
            $table->unsignedInteger('rpm')->nullable();

            $table->unsignedInteger('file_mb');
            $table->unsignedInteger('seconds_per_test');
            // '' | before | after (a cleanup's before/after pair)
            $table->string('label', 8)->default('');
            $table->decimal('free_pct', 5, 2)->nullable();

            // MB/s; null when that test was skipped (the write tests are optional)
            $table->decimal('seq1m_q8_read', 10, 2)->nullable();
            $table->decimal('seq1m_q8_write', 10, 2)->nullable();
            $table->decimal('seq1m_q1_read', 10, 2)->nullable();
            $table->decimal('seq1m_q1_write', 10, 2)->nullable();
            $table->decimal('rnd4k_q32_read', 10, 2)->nullable();
            $table->decimal('rnd4k_q32_write', 10, 2)->nullable();
            $table->decimal('rnd4k_q1_read', 10, 2)->nullable();
            $table->decimal('rnd4k_q1_write', 10, 2)->nullable();
            $table->unsignedInteger('rnd4k_q32_read_iops')->nullable();
            $table->unsignedInteger('rnd4k_q1_read_iops')->nullable();
            $table->decimal('access_ms', 8, 3)->nullable();

            // Read speed across the disk surface: min/avg/max and 20 evenly spaced points
            $table->decimal('surface_min', 10, 2)->nullable();
            $table->decimal('surface_avg', 10, 2)->nullable();
            $table->decimal('surface_max', 10, 2)->nullable();

            $table->unsignedInteger('score_total');
            $table->unsignedInteger('score_read');
            $table->unsignedInteger('score_write')->nullable();

            $table->json('surface_points')->nullable();
            $table->timestamps();

            $table->index(['model_key', 'capacity_gb']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winx_disk_benchmarks');
    }
};
