<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_crawl_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->boolean('logging_enabled')->default(true);
            $table->boolean('block_training_bots')->default(true);
            $table->boolean('allow_assistant_bots')->default(true);
            $table->boolean('allow_search_bots')->default(true);
            $table->text('custom_bot_rules')->nullable();
            $table->text('blocked_paths')->nullable();
            $table->boolean('llms_txt_enabled')->default(true);
            $table->text('llms_txt_content')->nullable();
            $table->timestamps();
        });

        DB::table('ai_crawl_settings')->insert([
            'enabled' => true,
            'logging_enabled' => true,
            'block_training_bots' => true,
            'allow_assistant_bots' => true,
            'allow_search_bots' => true,
            'blocked_paths' => json_encode(['/admin/', '/customer/', '/api/', '/downloads/']),
            'llms_txt_enabled' => true,
            'llms_txt_content' => implode("\n", [
                '# XMAN Studio',
                '> XMAN Studio - Digital Products, Software Licenses & Services Platform',
                '',
                '## About',
                'XMAN Studio is a comprehensive business management platform for selling digital products, managing software licenses, rental packages, and content creation services based in Thailand.',
                '',
                '## Products & Services',
                '- Digital Products: Software licenses, tools, and digital assets',
                '- VPN Services: LocalVPN packages and rental services',
                '- Web Development: Custom websites, CMS systems, and web solutions',
                '- Metal-X YouTube: Creative content and media production',
                '',
                '## Links',
                '- Homepage: /',
                '- Products: /products',
                '- Services: /services',
                '- Support: /support',
                '- Rental Packages: /rental',
                '',
                '## Policies',
                '- Terms of Service: /terms',
                '- Privacy Policy: /privacy',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_crawl_settings');
    }
};
