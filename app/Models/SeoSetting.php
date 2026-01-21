<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'seo_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'site_name',
        'site_title',
        'site_description',
        'site_keywords',
        'site_author',
        'og_image',
        'twitter_site',
        'twitter_creator',
        'google_site_verification',
        'google_analytics_id',
        'sitemap_enabled',
        'robots_txt_enabled',
        'robots_txt_content',
        'structured_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sitemap_enabled' => 'boolean',
        'robots_txt_enabled' => 'boolean',
        'structured_data' => 'array',
    ];

    /**
     * Get the singleton instance of SEO settings.
     */
    public static function getInstance(): self
    {
        $setting = self::first();

        if (! $setting) {
            $setting = self::create([
                'site_name' => 'XMAN Studio',
                'site_title' => 'XMAN Studio - รับทำเว็บไซต์ ออกแบบเว็บไซต์',
                'site_description' => 'XMAN Studio ให้บริการรับทำเว็บไซต์ ออกแบบเว็บไซต์ พัฒนาระบบ CMS ครบวงจร',
                'sitemap_enabled' => true,
                'robots_txt_enabled' => true,
            ]);
        }

        return $setting;
    }

    /**
     * Get meta tags as HTML.
     */
    public function getMetaTags(?string $pageTitle = null, ?string $pageDescription = null, ?string $pageImage = null): string
    {
        $title = $pageTitle ?? $this->site_title;
        $description = $pageDescription ?? $this->site_description;
        $image = $pageImage ?? $this->og_image ?? asset('images/og-default.jpg');

        $tags = [];

        // Basic meta tags
        $tags[] = '<meta name="description" content="'.e($description).'">';
        if ($this->site_keywords) {
            $tags[] = '<meta name="keywords" content="'.e($this->site_keywords).'">';
        }
        if ($this->site_author) {
            $tags[] = '<meta name="author" content="'.e($this->site_author).'">';
        }

        // Open Graph
        $tags[] = '<meta property="og:title" content="'.e($title).'">';
        $tags[] = '<meta property="og:description" content="'.e($description).'">';
        $tags[] = '<meta property="og:image" content="'.e($image).'">';
        $tags[] = '<meta property="og:url" content="'.e(url()->current()).'">';
        $tags[] = '<meta property="og:type" content="website">';
        $tags[] = '<meta property="og:site_name" content="'.e($this->site_name).'">';

        // Twitter Card
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        $tags[] = '<meta name="twitter:title" content="'.e($title).'">';
        $tags[] = '<meta name="twitter:description" content="'.e($description).'">';
        $tags[] = '<meta name="twitter:image" content="'.e($image).'">';
        if ($this->twitter_site) {
            $tags[] = '<meta name="twitter:site" content="'.e($this->twitter_site).'">';
        }
        if ($this->twitter_creator) {
            $tags[] = '<meta name="twitter:creator" content="'.e($this->twitter_creator).'">';
        }

        // Google verification
        if ($this->google_site_verification) {
            $tags[] = '<meta name="google-site-verification" content="'.e($this->google_site_verification).'">';
        }

        return implode("\n    ", $tags);
    }

    /**
     * Get structured data as JSON-LD.
     */
    public function getStructuredData(?array $additionalData = null): string
    {
        $data = $this->structured_data ?? [];

        if ($additionalData) {
            $data = array_merge($data, $additionalData);
        }

        return '<script type="application/ld+json">'.json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).'</script>';
    }
}
