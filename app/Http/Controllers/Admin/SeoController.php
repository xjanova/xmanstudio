<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SitemapController;
use App\Models\SeoSetting;
use App\Services\ImageService;
use Illuminate\Http\Request;

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
            $data['og_image'] = app(ImageService::class)->replaceWithWebp(
                $request->file('og_image'), $setting->og_image, 'seo', maxWidth: 1200,
            );
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
     * "Refresh sitemap": /sitemap.xml is built from the live site (SitemapController), so this
     * only drops its 30-minute cache — and deletes a public_html/sitemap.xml file if one is
     * there, because the web server would serve that stale file instead of the live one.
     */
    public function generateSitemap()
    {
        $setting = SeoSetting::getInstance();

        if (! $setting->sitemap_enabled) {
            return redirect()
                ->route('admin.seo.index')
                ->with('error', 'Sitemap ปิดอยู่ — เปิดสวิตช์ Sitemap แล้วบันทึกก่อน');
        }

        foreach (['sitemap.xml', 'robots.txt'] as $static) {
            if (is_file(public_path($static))) {
                @unlink(public_path($static));
            }
        }

        SitemapController::forget();

        return redirect()
            ->route('admin.seo.index')
            ->with('success', 'อัปเดต sitemap แล้ว — /sitemap.xml สร้างจากหน้าเว็บ สินค้า บริการ และบทความจริงทุกครั้ง');
    }
}
