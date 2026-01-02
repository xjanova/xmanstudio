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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->default('general');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, json, encrypted
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('bank_code');
            $table->string('account_number');
            $table->string('account_name');
            $table->string('branch')->nullable();
            $table->string('swift_code')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('payment_settings');
    }
};
