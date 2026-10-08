<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameCampaign;
use App\Models\GameComment;
use App\Models\GameDonation;
use App\Services\GameSupportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GameSupportController extends Controller
{
    public function index(Request $request, GameSupportService $service)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'void'])], 'comment_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]]);
        $status = $filters['status'] ?? 'pending';
        $commentStatus = $filters['comment_status'] ?? 'pending';

        return view('admin.game-support.index', [
            'donations' => GameDonation::with('campaign')->where('status', $status)->latest()->paginate(25)->withQueryString(),
            'comments' => GameComment::with('campaign')->where('status', $commentStatus)->latest()->paginate(25, ['*'], 'comments')->withQueryString(),
            'campaigns' => GameCampaign::orderBy('id')->get(), 'stats' => $service->summary(), 'status' => $status, 'commentStatus' => $commentStatus,
        ]);
    }

    public function slip(GameDonation $donation)
    {
        abort_unless(Storage::disk('local')->exists($donation->slip_path), 404);

        return Storage::disk('local')->response($donation->slip_path, 'slip-' . $donation->id, ['Content-Type' => $donation->slip_mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    public function review(Request $request, GameDonation $donation, GameSupportService $service)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])], 'review_note' => ['required', 'string', 'max:2000'],
            'verified_amount' => ['required_if:decision,approved', 'nullable', 'string', 'max:12'],
            'bank_reference' => ['required_if:decision,approved', 'nullable', 'regex:/^[A-Za-z0-9-]{6,120}$/'],
            'recipient_checked' => ['accepted_if:decision,approved'], 'money_received' => ['accepted_if:decision,approved'],
        ]);
        try {
            $service->review($donation, $data, $request->user()->id);
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'])) {
                throw ValidationException::withMessages(['bank_reference' => 'เลขอ้างอิงถูกใช้แล้ว']);
            } throw $e;
        }

        return back()->with('success', 'บันทึกผลตรวจแล้ว ยอดสาธารณะนับเฉพาะรายการอนุมัติ');
    }

    public function reward(Request $request, GameDonation $donation)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $donation, $data) {
            $row = GameDonation::lockForUpdate()->findOrFail($donation->id);
            abort_unless($row->status === 'approved' && $row->reward_status === 'available', 409);
            $row->update(['reward_status' => 'delivered', 'audit' => [...($row->audit ?? []), ['action' => 'reward_delivered', 'by' => $request->user()->id, 'at' => now()->toIso8601String(), 'note' => $data['note']]]]);
        });

        return back()->with('success', 'บันทึกการส่งมอบรางวัลแล้ว');
    }

    public function void(Request $request, GameDonation $donation)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $donation, $data) {
            $row = GameDonation::lockForUpdate()->findOrFail($donation->id);
            abort_unless($row->status === 'approved', 409);
            $row->update(['status' => 'void', 'reward_status' => 'revoked', 'audit' => [...($row->audit ?? []), ['action' => 'void', 'by' => $request->user()->id, 'at' => now()->toIso8601String(), 'note' => $data['note']]]]);
        });

        return back()->with('success', 'ยกเลิกการนับยอดแล้ว ประวัติรายการยังคงอยู่ การคืนเงินจริงต้องดำเนินการผ่านธนาคาร');
    }

    public function moderate(Request $request, GameComment $comment)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $comment->update($data + ['moderated_by' => $request->user()->id, 'moderated_at' => now()]);

        return back()->with('success', 'บันทึกผลตรวจความคิดเห็นแล้ว');
    }

    public function campaign(Request $request, ?GameCampaign $campaign = null)
    {
        $data = $request->validate([
            'slug' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:80', ...($campaign ? [Rule::in([$campaign->slug])] : []), Rule::unique('game_campaigns')->ignore($campaign?->id)],
            'name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:2000'],
            'goal' => ['required', 'integer', 'min:0', 'max:100000000'], 'active' => ['nullable', 'boolean'], 'tiers_json' => ['required', 'json'],
        ]);
        $tiers = json_decode($data['tiers_json'], true);
        validator(['tiers' => $tiers], ['tiers' => ['required', 'array', 'min:1', 'max:12'], 'tiers.*.minimum' => ['required', 'integer', 'min:1', 'max:1000000'], 'tiers.*.name' => ['required', 'string', 'max:80'], 'tiers.*.rewards' => ['required', 'array', 'min:1', 'max:10'], 'tiers.*.rewards.*' => ['required', 'string', 'max:250']])->validate();
        $values = ['slug' => $data['slug'], 'name' => $data['name'], 'description' => $data['description'] ?? null, 'goal_satang' => $data['goal'] * 100, 'active' => $request->boolean('active'), 'tiers' => $tiers];
        $campaign ? $campaign->update($values) : GameCampaign::create($values);

        return back()->with('success', 'บันทึกโครงการแล้ว รางวัลของรายการเดิมจะไม่เปลี่ยนตามการแก้ไขนี้');
    }
}
