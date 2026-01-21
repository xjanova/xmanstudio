<?php

namespace App\View\Components;

use App\Models\Banner;
use Illuminate\View\Component;

class BannerDisplay extends Component
{
    public string $position;

    public string $page;

    public ?Banner $banner;

    public function __construct(string $position, string $page = 'all')
    {
        $this->position = $position;
        $this->page = $page;
        $this->banner = Banner::getForPosition($position, $page);
    }

    public function shouldRender(): bool
    {
        return $this->banner !== null && $this->banner->isActive();
    }

    public function render()
    {
        return view('components.banner-display');
    }
}
