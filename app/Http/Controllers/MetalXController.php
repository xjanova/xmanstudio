<?php

namespace App\Http\Controllers;

use App\Models\MetalXTeamMember;
use App\Models\MetalXVideo;
use App\Models\Setting;

class MetalXController extends Controller
{
    public function index()
    {
        $teamMembers = MetalXTeamMember::active()
            ->ordered()
            ->get();

        $channelSettings = [
            'channel_name' => Setting::getValue('metalx_channel_name', 'Metal-X Project'),
            'channel_description' => Setting::getValue('metalx_channel_description'),
            'channel_url' => Setting::getValue('metalx_channel_url', 'https://www.youtube.com/@Metal-XProject'),
            'channel_logo' => Setting::getValue('metalx_channel_logo'),
            'channel_banner' => Setting::getValue('metalx_channel_banner'),
            'youtube_api_key' => Setting::getValue('youtube_api_key'),
        ];

        // Popular videos with >50K views from database
        $popularVideos = MetalXVideo::active()
            ->where('view_count', '>=', 50000)
            ->orderByDesc('view_count')
            ->limit(12)
            ->get();

        return view('metal-x.index', compact('teamMembers', 'channelSettings', 'popularVideos'));
    }
}
