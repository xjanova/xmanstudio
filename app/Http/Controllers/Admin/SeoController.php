<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SeoController extends Controller
{
    /**
     * Display the SEO settings form.
     */
    public function index()
    {
        $setting = SeoSetting::getInstance();

        return view('admin.seo.index', compact('setting'));
    }

    /**
     * Update the SEO settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_title' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'site_keywords' => 'nullable|string|max:255',
            'site_author' => 'nullable|string|max:255',
            'og_image' => 'nullable|image|max:2048',
            'twitter_site' => 'nullable|string|max:255',
            'twitter_creator' => 'nullable|string|max:255',
            'google_site_verification' => 'nullable|string|max:255',
            'google_analytics_id' => 'nullable|string|max:255',
            'sitemap_enabled' => 'nullable|boolean',
            'robots_txt_enabled' => 'nullable|boolean',
            'robots_txt_content' => 'nullable|string',
            'structured_data_json' => 'nullable|json',
        ]);

        $setting = SeoSetting::getInstance();

        $data = [
            'site_name' => $request->input('site_name'),
            'site_title' => $request->input('site_title'),
            'site_description' => $request->input('site_description'),
            'site_keywords' => $request->input('site_keywords'),
            'site_author' => $request->input('site_author'),
            'twitter_site' => $request->input('twitter_site'),
            'twitter_creator' => $request->input('twitter_creator'),
            'google_site_verification' => $request->input('google_site_verification'),
            'google_analytics_id' => $request->input('google_analytics_id'),
            'sitemap_enabled' => $request->has('sitemap_enabled'),
            'robots_txt_enabled' => $request->has('robots_txt_enabled'),
            'robots_txt_content' => $request->input('robots_txt_content'),
        ];

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            // Delete old image
            if ($setting->og_image) {
                Storage::disk('public')->delete($setting->og_image);
            }

            $path = $request->file('og_image')->store('seo', 'public');
            $data['og_image'] = $path;
        }

        // Handle structured data
        if ($request->filled('structured_data_json')) {
            $data['structured_data'] = json_decode($request->input('structured_data_json'), true);
        }

        $setting->update($data);

        return redirect()
            ->route('admin.seo.index')
            ->with('success', 'SEO settings updated successfully!');
    }

    /**
     * Generate and download sitemap.
     */
    public function generateSitemap()
    {
        $setting = SeoSetting::getInstance();

        if (! $setting->sitemap_enabled) {
            return redirect()
                ->route('admin.seo.index')
                ->with('error', 'Sitemap is disabled.');
        }

        // Generate sitemap content
        $sitemap = $this->buildSitemapXml();

        // Save to public directory
        file_put_contents(public_path('sitemap.xml'), $sitemap);

        return redirect()
            ->route('admin.seo.index')
            ->with('success', 'Sitemap generated successfully at /sitemap.xml');
    }

    /**
     * Build sitemap XML content.
     */
    private function buildSitemapXml(): string
    {
        $urls = $this->getSitemapUrls();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Get sitemap URLs.
     */
    private function getSitemapUrls(): array
    {
        $urls = [];
        $now = now()->toIso8601String();

        // Homepage
        $urls[] = [
            'loc' => url('/'),
            'lastmod' => $now,
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Static pages
        $urls[] = [
            'loc' => route('products.index'),
            'lastmod' => $now,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ];

        $urls[] = [
            'loc' => route('services.index'),
            'lastmod' => $now,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ];

        $urls[] = [
            'loc' => route('support.index'),
            'lastmod' => $now,
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];

        $urls[] = [
            'loc' => route('rental.index'),
            'lastmod' => $now,
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];

        $urls[] = [
            'loc' => route('about'),
            'lastmod' => $now,
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ];

        // Add dynamic products/services if needed
        // Example:
        // foreach (Product::all() as $product) {
        //     $urls[] = [
        //         'loc' => route('products.show', $product->slug),
        //         'lastmod' => $product->updated_at->toIso8601String(),
        //         'changefreq' => 'monthly',
        //         'priority' => '0.7',
        //     ];
        // }

        return $urls;
    }
}
