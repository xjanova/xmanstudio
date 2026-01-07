<?php

namespace App\Http\Controllers;

use App\Models\MetalXTeamMember;
use App\Models\Setting;
use Illuminate\Http\Request;

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

        return view('metal-x.index', compact('teamMembers', 'channelSettings'));
    }
}
