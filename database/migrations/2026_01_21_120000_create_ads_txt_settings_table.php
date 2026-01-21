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
        Schema::create('ads_txt_settings', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable()->comment('Content of ads.txt file');
            $table->boolean('enabled')->default(true)->comment('Enable/disable ads.txt');
            $table->timestamps();
        });

        // Insert default content
        DB::table('ads_txt_settings')->insert([
            'content' => implode("\n", [
                '# Google AdSense',
                '# google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0',
                '',
                '# Add your ads.txt entries here',
                '# Format: domain, publisher ID, relationship, certification authority ID',
            ]),
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads_txt_settings');
    }
};
