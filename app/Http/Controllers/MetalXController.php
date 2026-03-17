<?php

namespace App\Http\Controllers;

use App\Models\MetalXChannel;
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

        // Get the default Metal-X channel
        $defaultChannel = MetalXChannel::getDefault();

        $channelSettings = [
            'channel_name' => Setting::getValue('metalx_channel_name', 'Metal-X Project'),
            'channel_description' => Setting::getValue('metalx_channel_description'),
            'channel_url' => Setting::getValue('metalx_channel_url', 'https://www.youtube.com/@Metal-XProject'),
            'channel_logo' => Setting::getValue('metalx_channel_logo'),
            'channel_banner' => Setting::getValue('metalx_channel_banner'),
            'youtube_api_key' => Setting::getValue('youtube_api_key'),
        ];

        // Build base query scoped to default channel
        $channelVideoQuery = MetalXVideo::active();
        if ($defaultChannel) {
            $channelVideoQuery->where('metal_x_channel_id', $defaultChannel->id);
        }

        // Featured videos from Metal-X channel only
        $featuredVideos = (clone $channelVideoQuery)
            ->featured()
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();

        // Hero video for background — supports multiple modes
        $heroVideoMode = Setting::getValue('metalx_hero_video_mode', 'featured');
        $heroVideo = null;

        switch ($heroVideoMode) {
            case 'locked':
                // Locked to a specific video
                $lockedId = Setting::getValue('metalx_hero_video_id');
                if ($lockedId) {
                    $heroVideo = MetalXVideo::find($lockedId);
                }
                break;

            case 'random':
                // Random video from active videos
                $heroVideo = (clone $channelVideoQuery)->inRandomOrder()->first();
                break;

            case 'playlist':
                // Use featured videos as a playlist (rotate through them)
                if ($featuredVideos->isNotEmpty()) {
                    // Pick video based on day of year for daily rotation
                    $dayIndex = now()->dayOfYear % $featuredVideos->count();
                    $heroVideo = $featuredVideos->values()->get($dayIndex);
                }
                break;

            case 'featured':
            default:
                // First featured or highest views (original behavior)
                $heroVideo = $featuredVideos->first();
                break;
        }

        // Fallback: highest views
        if (! $heroVideo) {
            $heroVideo = (clone $channelVideoQuery)->orderByDesc('view_count')->first();
        }

        // Pass all featured videos for playlist mode in frontend
        $heroPlaylistIds = $featuredVideos->pluck('youtube_id')->toArray();

        // Popular videos with >50K views from Metal-X channel
        $popularVideos = (clone $channelVideoQuery)
            ->where('view_count', '>=', 50000)
            ->orderByDesc('view_count')
            ->limit(12)
            ->get();

        // Partner channels (non-default active channels) with their top videos
        $partnerChannels = MetalXChannel::active()
            ->where('is_default', false)
            ->with(['videos' => function ($query) {
                $query->active()
                    ->orderByDesc('view_count')
                    ->limit(6);
            }])
            ->get();

        return view('metal-x.index', compact(
            'teamMembers',
            'channelSettings',
            'featuredVideos',
            'heroVideo',
            'heroVideoMode',
            'heroPlaylistIds',
            'popularVideos',
            'partnerChannels',
        ));
    }
}
