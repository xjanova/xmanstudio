<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetalXChannelController extends Controller
{
    public function index()
    {
        $channels = MetalXChannel::orderByDesc('is_default')->orderBy('name')->get();

        return view('admin.metal-x.channels.index', compact('channels'));
    }

    public function edit(MetalXChannel $channel)
    {
        return view('admin.metal-x.channels.edit', compact('channel'));
    }

    public function update(Request $request, MetalXChannel $channel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $channel->update($validated);

        return redirect()->route('admin.metal-x.channels.index')
            ->with('success', 'อัปเดตช่องสำเร็จ');
    }

    public function destroy(MetalXChannel $channel)
    {
        try {
            $accessToken = $channel->access_token;

            if ($accessToken) {
                Http::post('https://oauth2.googleapis.com/revoke', [
                    'token' => $accessToken,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("[Channel] Token revoke failed: {$e->getMessage()}");
        }

        $channel->delete();

        return redirect()->route('admin.metal-x.channels.index')
            ->with('success', 'ยกเลิกการเชื่อมต่อช่องสำเร็จ');
    }

    public function setDefault(MetalXChannel $channel)
    {
        // Unset all defaults
        MetalXChannel::where('is_default', true)->update(['is_default' => false]);

        $channel->update(['is_default' => true]);

        return back()->with('success', "ตั้ง {$channel->name} เป็นช่องหลัก");
    }

    public function sync(MetalXChannel $channel)
    {
        $accessToken = $channel->getValidAccessToken();

        if (! $accessToken) {
            return back()->with('error', 'ไม่สามารถเชื่อมต่อ YouTube ได้ กรุณาเชื่อมต่อใหม่');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet,statistics',
                'mine' => 'true',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $item = $data['items'][0] ?? null;

                if ($item) {
                    $channel->update([
                        'name' => $item['snippet']['title'] ?? $channel->name,
                        'channel_thumbnail_url' => $item['snippet']['thumbnails']['default']['url'] ?? $channel->channel_thumbnail_url,
                        'subscriber_count' => $item['statistics']['subscriberCount'] ?? 0,
                        'video_count' => $item['statistics']['videoCount'] ?? 0,
                        'last_synced_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Sync ล้มเหลว: ' . $e->getMessage());
        }

        return back()->with('success', "ซิงค์ข้อมูล {$channel->name} สำเร็จ");
    }
}
