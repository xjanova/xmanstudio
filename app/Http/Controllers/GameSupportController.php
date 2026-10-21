<?php

namespace App\Http\Controllers;

use App\Models\GameCampaign;
use App\Models\GameComment;
use App\Models\GameDonation;
use App\Services\GameSupportService;
use App\Support\HubOrigin;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GameSupportController extends Controller
{
    public function index(GameSupportService $service)
    {
        return view('game-support.index', ['games' => $service->summary()]);
    }

    public function summary(GameSupportService $service)
    {
        return response()->json(['games' => $service->summary(), 'updated_at' => now()->toIso8601String()])
            ->header('Access-Control-Allow-Origin', HubOrigin::forRequest())->header('Vary', 'Origin')->header('Cache-Control', 'public, max-age=30');
    }

    public function show(Request $request, GameCampaign $campaign, GameSupportService $service)
    {
        if (! $campaign->active) {
            return response()->view('game-support.missing', ['campaign' => $campaign], 404);
        }

        return view('game-support.show', [
            'campaign' => $campaign, 'bank' => config('game-support.bank'),
            'stats' => collect($service->summary())->firstWhere('slug', $campaign->slug),
            'donors' => $campaign->donations()->where('status', 'approved')->where('publish_name', true)->latest('reviewed_at')->paginate(20, ['display_name', 'amount_satang', 'reward_snapshot', 'reward_status', 'reviewed_at'], 'donors'),
            'comments' => $campaign->comments()->where('status', 'approved')->latest()->paginate(20, ['display_name', 'body', 'created_at'], 'comments'),
            'mine' => $request->user() ? $campaign->donations()->where('user_id', $request->user()->id)->with('entitlements.item')->latest()->limit(20)->get() : collect(),
            'reviews' => $campaign->reviews()->where('status', 'approved')->with('user:id,name')->orderByDesc('is_featured')->orderByDesc('approved_at')->paginate(10, ['*'], 'reviews'),
            'myReview' => $request->user() ? $campaign->reviews()->where('user_id', $request->user()->id)->first() : null,
            'myVote' => $request->user() ? DB::table('game_votes')->where('user_id', $request->user()->id)->value('game_campaign_id') : null,
            'myRating' => $request->user() ? DB::table('game_ratings')->where('user_id', $request->user()->id)->where('game_campaign_id', $campaign->id)->value('stars') : null,
        ]);
    }

    public function donate(Request $request, GameCampaign $campaign, GameSupportService $service)
    {
        abort_unless($campaign->active, 404);
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:12'], 'display_name' => ['required', 'string', 'max:80'],
            'publish_name' => ['nullable', 'boolean'], 'comment' => ['nullable', 'string', 'max:2000'], 'publish_comment' => ['nullable', 'boolean'],
            'consent' => ['accepted'], 'slip' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
        ]);
        $amount = $service::satang($data['amount']);
        $file = $request->file('slip');
        $hash = hash_file('sha256', $file->getRealPath());
        if (GameDonation::where('slip_hash', $hash)->exists()) {
            throw ValidationException::withMessages(['slip' => 'สลิปนี้ส่งไว้แล้ว กรุณาดูสถานะรายการเดิม']);
        }
        $path = $file->store('game-slips', 'local');
        if (! $path) {
            throw ValidationException::withMessages(['slip' => 'บันทึกสลิปไม่สำเร็จ กรุณาลองใหม่']);
        }
        try {
            DB::transaction(function () use ($request, $campaign, $service, $data, $amount, $file, $hash, $path) {
                $donation = GameDonation::create([
                    'public_id' => (string) Str::uuid(), 'game_campaign_id' => $campaign->id, 'user_id' => $request->user()->id,
                    'amount_satang' => $amount, 'display_name' => $data['display_name'], 'publish_name' => $request->boolean('publish_name'),
                    'comment' => $data['comment'] ?? null, 'slip_path' => $path, 'slip_hash' => $hash, 'slip_mime' => $file->getMimeType(),
                    'bank_snapshot' => config('game-support.bank'), 'reward_snapshot' => $service->reward($campaign, $amount), 'status' => 'pending',
                ]);
                if ($request->boolean('publish_comment') && ! empty($data['comment'])) {
                    GameComment::create([
                        'game_campaign_id' => $campaign->id, 'user_id' => $request->user()->id, 'game_donation_id' => $donation->id,
                        'display_name' => $request->boolean('publish_name') ? $data['display_name'] : 'ผู้สนับสนุนไม่เปิดเผยชื่อ', 'body' => $data['comment'], 'status' => 'pending',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            if ($e instanceof QueryException && GameDonation::where('slip_hash', $hash)->exists()) {
                throw ValidationException::withMessages(['slip' => 'สลิปนี้ส่งไว้แล้ว']);
            }
            throw $e;
        }

        return redirect()->route('game-support.show', $campaign)->with('success', 'รับสลิปแล้ว อยู่ระหว่างตรวจสอบ ยังไม่เพิ่มยอดจนกว่าทีมยืนยันเงินเข้า');
    }

    public function vote(Request $request, GameCampaign $campaign)
    {
        abort_unless($campaign->active, 404);
        DB::table('game_votes')->upsert([['user_id' => $request->user()->id, 'game_campaign_id' => $campaign->id, 'created_at' => now(), 'updated_at' => now()]], ['user_id'], ['game_campaign_id', 'updated_at']);

        return back()->with('success', 'บันทึกเกมที่อยากให้ทำมากที่สุดแล้ว เปลี่ยนใจได้โดยโหวตเกมใหม่');
    }

    public function rate(Request $request, GameCampaign $campaign)
    {
        abort_unless($campaign->active, 404);
        $data = $request->validate(['stars' => ['required', 'integer', 'between:1,5']]);
        DB::table('game_ratings')->upsert([['user_id' => $request->user()->id, 'game_campaign_id' => $campaign->id, 'stars' => $data['stars'], 'created_at' => now(), 'updated_at' => now()]], ['user_id', 'game_campaign_id'], ['stars', 'updated_at']);

        return back()->with('success', 'บันทึกคะแนนดาวแล้ว หนึ่งบัญชีให้คะแนนได้หนึ่งครั้งต่อเกมและแก้ไขได้');
    }

    public function comment(Request $request, GameCampaign $campaign)
    {
        abort_unless($campaign->active, 404);
        $data = $request->validate(['display_name' => ['required', 'string', 'max:80'], 'body' => ['required', 'string', 'max:2000']]);
        GameComment::create($data + ['user_id' => $request->user()->id, 'game_campaign_id' => $campaign->id, 'status' => 'pending']);

        return back()->with('success', 'ขอบคุณสำหรับคำแนะนำ ทีมจะตรวจข้อความก่อนแสดงสาธารณะ');
    }
}
