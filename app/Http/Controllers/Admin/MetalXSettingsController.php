<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetalXSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            // Channel
            'channel_name' => Setting::getValue('metalx_channel_name', 'Metal-X Project'),
            'channel_description' => Setting::getValue('metalx_channel_description'),
            'channel_url' => Setting::getValue('metalx_channel_url', 'https://www.youtube.com/@Metal-XProject'),
            'channel_logo' => Setting::getValue('metalx_channel_logo'),
            'channel_banner' => Setting::getValue('metalx_channel_banner'),
            'channel_id' => Setting::getValue('metalx_channel_id'),

            // YouTube API
            'youtube_api_key' => Setting::getValue('youtube_api_key'),
            'youtube_client_id' => Setting::getValue('youtube_client_id'),
            'youtube_client_secret' => Setting::getValue('youtube_client_secret'),

            // Suno AI Music
            'suno_api_key' => Setting::getValue('suno_api_key'),
            'suno_base_url' => Setting::getValue('suno_base_url', 'https://apibox.erweima.ai'),

            // AI Provider
            'metalx_ai_provider' => Setting::getValue('metalx_ai_provider', 'groq'),
            'groq_api_key' => Setting::getValue('groq_api_key'),
            'metalx_openai_key' => Setting::getValue('ai_openai_key'),
            'metalx_claude_key' => Setting::getValue('ai_claude_key'),

            // FFmpeg
            'ffmpeg_binary' => Setting::getValue('ffmpeg_binary', 'ffmpeg'),
            'ffprobe_binary' => Setting::getValue('ffprobe_binary', 'ffprobe'),

            // Video Defaults
            'metalx_video_resolution' => Setting::getValue('metalx_video_resolution', '1920x1080'),
            'metalx_default_privacy' => Setting::getValue('metalx_default_privacy', 'private'),
            'metalx_promo_max_per_video_per_day' => Setting::getValue('metalx_promo_max_per_video_per_day', 2),
        ];

        return view('admin.metal-x.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // Channel
            'channel_name' => 'required|string|max:255',
            'channel_description' => 'nullable|string',
            'channel_url' => 'required|url',
            'channel_logo' => 'nullable|image|max:2048',
            'channel_banner' => 'nullable|image|max:2048',
            'channel_id' => 'nullable|string|max:50',

            // YouTube API
            'youtube_api_key' => 'nullable|string',
            'youtube_client_id' => 'nullable|string|max:255',
            'youtube_client_secret' => 'nullable|string|max:255',

            // Suno AI Music
            'suno_api_key' => 'nullable|string|max:255',
            'suno_base_url' => 'nullable|url|max:255',

            // AI Provider
            'metalx_ai_provider' => 'nullable|string|in:groq,openai,claude,gemini,ollama',
            'groq_api_key' => 'nullable|string|max:255',
            'metalx_openai_key' => 'nullable|string|max:255',
            'metalx_claude_key' => 'nullable|string|max:255',

            // FFmpeg
            'ffmpeg_binary' => 'nullable|string|max:500',
            'ffprobe_binary' => 'nullable|string|max:500',

            // Video Defaults
            'metalx_video_resolution' => 'nullable|string|in:1920x1080,1280x720,3840x2160',
            'metalx_default_privacy' => 'nullable|string|in:public,private,unlisted',
            'metalx_promo_max_per_video_per_day' => 'nullable|integer|min:1|max:20',
        ]);

        // Handle logo upload
        if ($request->hasFile('channel_logo')) {
            $oldLogo = Setting::getValue('metalx_channel_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $logoPath = $request->file('channel_logo')->store('metal-x/channel', 'public');
            Setting::setValue('metalx_channel_logo', $logoPath, 'string', 'metalx');
        }

        // Handle banner upload
        if ($request->hasFile('channel_banner')) {
            $oldBanner = Setting::getValue('metalx_channel_banner');
            if ($oldBanner) {
                Storage::disk('public')->delete($oldBanner);
            }
            $bannerPath = $request->file('channel_banner')->store('metal-x/channel', 'public');
            Setting::setValue('metalx_channel_banner', $bannerPath, 'string', 'metalx');
        }

        // Channel settings
        Setting::setValue('metalx_channel_name', $validated['channel_name'], 'string', 'metalx');
        Setting::setValue('metalx_channel_description', $validated['channel_description'] ?? '', 'string', 'metalx');
        Setting::setValue('metalx_channel_url', $validated['channel_url'], 'string', 'metalx');
        Setting::setValue('metalx_channel_id', $validated['channel_id'] ?? '', 'string', 'metalx');

        // YouTube API
        Setting::setValue('youtube_api_key', $validated['youtube_api_key'] ?? '', 'string', 'metalx');
        Setting::setValue('youtube_client_id', $validated['youtube_client_id'] ?? '', 'string', 'metalx');
        Setting::setValue('youtube_client_secret', $validated['youtube_client_secret'] ?? '', 'string', 'metalx');

        // Suno
        Setting::setValue('suno_api_key', $validated['suno_api_key'] ?? '', 'string', 'metalx');
        Setting::setValue('suno_base_url', $validated['suno_base_url'] ?? 'https://apibox.erweima.ai', 'string', 'metalx');

        // AI Provider
        Setting::setValue('metalx_ai_provider', $validated['metalx_ai_provider'] ?? 'groq', 'string', 'metalx');
        Setting::setValue('groq_api_key', $validated['groq_api_key'] ?? '', 'string', 'metalx');
        Setting::setValue('ai_openai_key', $validated['metalx_openai_key'] ?? '', 'string', 'metalx');
        Setting::setValue('ai_claude_key', $validated['metalx_claude_key'] ?? '', 'string', 'metalx');

        // FFmpeg
        Setting::setValue('ffmpeg_binary', $validated['ffmpeg_binary'] ?? 'ffmpeg', 'string', 'metalx');
        Setting::setValue('ffprobe_binary', $validated['ffprobe_binary'] ?? 'ffprobe', 'string', 'metalx');

        // Video Defaults
        Setting::setValue('metalx_video_resolution', $validated['metalx_video_resolution'] ?? '1920x1080', 'string', 'metalx');
        Setting::setValue('metalx_default_privacy', $validated['metalx_default_privacy'] ?? 'private', 'string', 'metalx');
        Setting::setValue('metalx_promo_max_per_video_per_day', $validated['metalx_promo_max_per_video_per_day'] ?? 2, 'integer', 'metalx');

        return redirect()->route('admin.metal-x.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ!');
    }
}
