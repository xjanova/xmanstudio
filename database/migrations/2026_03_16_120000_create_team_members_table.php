<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->string('position');
            $table->string('position_th')->nullable();
            $table->text('bio')->nullable();
            $table->text('bio_th')->nullable();
            $table->string('image')->nullable();
            $table->string('department')->nullable();
            $table->string('skills')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_leader')->default(false);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('order');
            $table->index('is_active');
            $table->index('is_leader');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
