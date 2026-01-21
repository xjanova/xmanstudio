<?php

namespace App\View\Components;

use App\Models\SeoSetting;
use Illuminate\View\Component;

class SeoMeta extends Component
{
    public $title;

    public $description;

    public $image;

    public $keywords;

    public function __construct(?string $title = null, ?string $description = null, ?string $image = null, ?string $keywords = null)
    {
        $setting = SeoSetting::getInstance();

        $this->title = $title ?? $setting->site_title;
        $this->description = $description ?? $setting->site_description;
        $this->image = $image ?? ($setting->og_image ? asset('storage/' . $setting->og_image) : null);
        $this->keywords = $keywords ?? $setting->site_keywords;
    }

    public function render()
    {
        return view('components.seo-meta');
    }
}
