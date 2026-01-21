<?php

namespace App\View\Components;

use App\Models\AdPlacement;
use Illuminate\View\Component;

class GoogleAd extends Component
{
    public string $position;

    public string $page;

    public ?AdPlacement $ad;

    public function __construct(string $position, string $page = 'all')
    {
        $this->position = $position;
        $this->page = $page;
        $this->ad = AdPlacement::getForPosition($position, $page);
    }

    public function shouldRender(): bool
    {
        return $this->ad !== null && $this->ad->enabled && ! empty($this->ad->code);
    }

    public function render()
    {
        return view('components.google-ad');
    }
}
