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
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('XMAN Studio');
            $table->string('site_title')->nullable();
            $table->text('site_description')->nullable();
            $table->string('site_keywords')->nullable();
            $table->string('site_author')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_site')->nullable();
            $table->string('twitter_creator')->nullable();
            $table->string('google_site_verification')->nullable();
            $table->string('google_analytics_id')->nullable();
            $table->boolean('sitemap_enabled')->default(true);
            $table->boolean('robots_txt_enabled')->default(true);
            $table->text('robots_txt_content')->nullable();
            $table->json('structured_data')->nullable();
            $table->timestamps();
        });

        // Insert default SEO settings
        DB::table('seo_settings')->insert([
            'site_name' => 'XMAN Studio',
            'site_title' => 'XMAN Studio - รับทำเว็บไซต์ ออกแบบเว็บไซต์ ระบบจัดการเนื้อหา CMS',
            'site_description' => 'XMAN Studio ให้บริการรับทำเว็บไซต์ ออกแบบเว็บไซต์ พัฒนาระบบ CMS ครบวงจร ด้วยทีมงานมืออาชีพ ราคาย่อมเยา ติดต่อสอบถาม',
            'site_keywords' => 'รับทำเว็บไซต์, ออกแบบเว็บไซต์, CMS, ระบบจัดการเนื้อหา, พัฒนาเว็บ, Laravel, XMAN Studio',
            'site_author' => 'XMAN Studio',
            'sitemap_enabled' => true,
            'robots_txt_enabled' => true,
            'robots_txt_content' => implode("\n", [
                'User-agent: *',
                'Allow: /',
                'Disallow: /admin/',
                'Disallow: /api/',
                '',
                'Sitemap: ' . url('/sitemap.xml'),
            ]),
            'structured_data' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'XMAN Studio',
                'url' => url('/'),
                'logo' => url('/images/logo.png'),
                'description' => 'รับทำเว็บไซต์ ออกแบบเว็บไซต์ พัฒนาระบบ CMS',
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => '+66-XX-XXXX-XXXX',
                    'contactType' => 'customer service',
                    'availableLanguage' => ['th', 'en'],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
