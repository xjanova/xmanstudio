<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameCampaign;
use App\Models\GameComment;
use App\Models\GameDonation;
use App\Models\GameEntitlement;
use App\Models\GameItem;
use App\Models\GamesHubAnnouncement;
use App\Models\Review;
use App\Models\User;
use App\Services\GameItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The XGamesHub back office: everything xgameshub.xman4289.com shows that is not baked
 * into its static build — supporter items, player reviews, comments, the hero order and
 * announcements. Slip review itself stays in Admin\GameSupportController.
 */
class GamesHubController extends Controller
{
    public function dashboard()
    {
        $reviews = Review::where('reviewable_type', GameCampaign::class);

        return view('admin.gameshub.dashboard', [
            'pending' => [
                'donations' => GameDonation::where('status', 'pending')->count(),
                'reviews' => (clone $reviews)->where('status', 'pending')->count(),
                'comments' => GameComment::where('status', 'pending')->count(),
            ],
            'raised' => (int) GameDonation::where('status', 'approved')->sum('amount_satang') / 100,
            'supporters' => GameDonation::where('status', 'approved')->distinct()->count('user_id'),
            'items' => GameItem::count(),
            'granted' => GameEntitlement::where('status', 'granted')->count(),
            'redeemed' => GameEntitlement::where('status', 'granted')->where('redeem_count', '>', 0)->count(),
            'approvedReviews' => (clone $reviews)->where('status', 'approved')->count(),
            'liveAnnouncements' => GamesHubAnnouncement::live()->count(),
            'recentDonations' => GameDonation::with('campaign:id,name')->latest()->limit(6)->get(),
            'recentReviews' => (clone $reviews)->where('status', 'pending')->with(['user:id,name', 'reviewable'])->latest()->limit(5)->get(),
        ]);
    }

    /* ---------------- items & entitlements ---------------- */

    public function items(Request $request)
    {
        $filters = $request->validate(['game' => ['nullable', 'string', 'max:80'], 'q' => ['nullable', 'string', 'max:120']]);
        $campaign = isset($filters['game']) ? GameCampaign::where('slug', $filters['game'])->first() : null;
        $q = trim($filters['q'] ?? '');
        $entitlements = GameEntitlement::with(['user:id,name,email', 'item.campaign:id,slug,name'])
            ->when($campaign, fn ($query) => $query->whereHas('item', fn ($i) => $i->where('game_campaign_id', $campaign->id)))
            ->when($q !== '', function ($query) use ($q) {
                // an exact code, or a member's e-mail / name
                $query->where(fn ($w) => $w->where('code_hash', GameItemService::hash($q))
                    ->orWhereHas('user', fn ($u) => $u->where('email', $q)->orWhere('name', 'like', '%' . addcslashes($q, '%_\\') . '%')));
            })
            ->latest()->paginate(30)->withQueryString();

        return view('admin.gameshub.items', [
            'campaigns' => GameCampaign::withCount('items')->orderBy('name')->get(),
            'campaign' => $campaign,
            'items' => $campaign ? $campaign->items()->withCount(['entitlements as granted_count' => fn ($e) => $e->where('status', 'granted')])->orderBy('id')->get() : collect(),
            'entitlements' => $entitlements,
            'q' => $q,
        ]);
    }

    private function itemRules(?GameItem $item, GameCampaign $campaign): array
    {
        return [
            'key' => ['required', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', 'max:80', Rule::unique('game_items')->where('game_campaign_id', $campaign->id)->ignore($item?->id), ...($item ? [Rule::in([$item->key])] : [])],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'kind' => ['required', Rule::in(array_keys(GameItem::KINDS))],
            'image_url' => ['nullable', 'url:https', 'max:500'],
            'max_devices' => ['required', 'integer', 'between:1,20'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function storeItem(Request $request)
    {
        $campaign = GameCampaign::where('slug', $request->input('game'))->firstOrFail();
        $data = $request->validate($this->itemRules(null, $campaign), ['key.regex' => 'รหัสไอเท็มใช้ a-z 0-9 - _ เท่านั้น เช่น founder-badge']);
        $campaign->items()->create($data + ['active' => $request->boolean('active')]);

        return back()->with('success', 'เพิ่มไอเท็มแล้ว ใส่รหัสไอเท็มในระดับรางวัลของเกมเพื่อให้ผู้สนับสนุนได้รับ');
    }

    public function updateItem(Request $request, GameItem $item)
    {
        $data = $request->validate($this->itemRules($item, $item->campaign), ['key.in' => 'เปลี่ยนรหัสไอเท็มไม่ได้ เพราะเกมและโค้ดที่แจกไปแล้วอ้างถึงรหัสนี้']);
        $item->update($data + ['active' => $request->boolean('active')]);

        return back()->with('success', 'บันทึกไอเท็มแล้ว');
    }

    public function grant(Request $request, GameItemService $service)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:game_items,id'],
            'email' => ['required', 'email', 'max:255'],
            'note' => ['required', 'string', 'max:1000'],
        ]);
        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => 'ไม่พบสมาชิก XMAN ID ที่ใช้อีเมลนี้']);
        }
        $service->grantManual($user->id, GameItem::findOrFail($data['item_id']), $request->user()->id, $data['note']);

        return back()->with('success', 'มอบไอเท็มให้ ' . $user->name . ' แล้ว โค้ดแสดงในหน้า "ไอเท็มของฉัน" ของสมาชิก');
    }

    public function revokeEntitlement(Request $request, GameEntitlement $entitlement, GameItemService $service)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $service->revoke($entitlement, $request->user()->id, $data['note']);

        return back()->with('success', 'ยกเลิกโค้ดแล้ว เกมจะปฏิเสธโค้ดนี้ตั้งแต่ตอนนี้ (เครื่องที่ใช้ไปแล้วไม่ถูกดึงคืนอัตโนมัติ)');
    }

    /* ---------------- reviews ---------------- */

    private function gameReview(Review $review): Review
    {
        abort_unless($review->reviewable_type === GameCampaign::class, 404);

        return $review;
    }

    public function reviews(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])], 'game' => ['nullable', 'string', 'max:80']]);
        $status = $filters['status'] ?? 'pending';
        $campaign = isset($filters['game']) ? GameCampaign::where('slug', $filters['game'])->first() : null;
        $base = Review::where('reviewable_type', GameCampaign::class);

        return view('admin.gameshub.reviews', [
            'reviews' => (clone $base)->where('status', $status)->when($campaign, fn ($q) => $q->where('reviewable_id', $campaign->id))
                ->with(['user:id,name,email', 'reviewable'])->orderByDesc('is_featured')->latest()->paginate(25)->withQueryString(),
            'counts' => (clone $base)->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status'),
            'campaigns' => GameCampaign::orderBy('name')->get(['id', 'slug', 'name']),
            'status' => $status,
            'campaign' => $campaign,
        ]);
    }

    public function moderateReview(Request $request, Review $review)
    {
        $this->gameReview($review);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'admin_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000']], ['admin_note.required_if' => 'ใส่เหตุผลที่ไม่อนุมัติ เพื่อให้ตรวจย้อนหลังได้']);
        $review->update($this->reviewDecision($data['status'], $data['admin_note'] ?? null, $review));

        return back()->with('success', $data['status'] === 'approved' ? 'อนุมัติรีวิวแล้ว แสดงบนหน้าเกมและในฮับ' : 'ไม่อนุมัติรีวิวแล้ว');
    }

    public function featureReview(Review $review)
    {
        $this->gameReview($review);
        abort_unless($review->status === 'approved', 409);
        $review->update(['is_featured' => ! $review->is_featured]);

        return back()->with('success', $review->is_featured ? 'ปักรีวิวนี้ไว้บนสุดแล้ว' : 'เลิกปักรีวิวแล้ว');
    }

    public function bulkReviews(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['integer'],
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'admin_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
        ], ['ids.required' => 'เลือกรีวิวอย่างน้อยหนึ่งรายการ', 'admin_note.required_if' => 'ใส่เหตุผลที่ไม่อนุมัติ']);
        $rows = Review::where('reviewable_type', GameCampaign::class)->whereIn('id', $data['ids'])->get();
        foreach ($rows as $review) {
            $review->update($this->reviewDecision($data['status'], $data['admin_note'] ?? null, $review));
        }

        return back()->with('success', 'บันทึกผลตรวจ ' . $rows->count() . ' รีวิวแล้ว');
    }

    private function reviewDecision(string $status, ?string $note, Review $review): array
    {
        return [
            'status' => $status,
            'admin_note' => $note,
            'approved_at' => $status === 'approved' ? ($review->approved_at ?? now()) : null,
            'is_featured' => $status === 'approved' && $review->is_featured,
        ];
    }

    /* ---------------- comments ---------------- */

    public function comments(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])], 'game' => ['nullable', 'string', 'max:80']]);
        $status = $filters['status'] ?? 'pending';
        $campaign = isset($filters['game']) ? GameCampaign::where('slug', $filters['game'])->first() : null;

        return view('admin.gameshub.comments', [
            'comments' => GameComment::with('campaign:id,slug,name')->where('status', $status)->when($campaign, fn ($q) => $q->where('game_campaign_id', $campaign->id))
                ->latest()->paginate(30)->withQueryString(),
            'counts' => GameComment::selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status'),
            'campaigns' => GameCampaign::orderBy('name')->get(['id', 'slug', 'name']),
            'status' => $status,
            'campaign' => $campaign,
        ]);
    }

    public function bulkComments(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['integer'],
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'moderation_note' => ['nullable', 'string', 'max:500'],
        ], ['ids.required' => 'เลือกความเห็นอย่างน้อยหนึ่งรายการ']);
        $n = GameComment::whereIn('id', $data['ids'])->update([
            'status' => $data['status'], 'moderation_note' => $data['moderation_note'] ?? null,
            'moderated_by' => $request->user()->id, 'moderated_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'บันทึกผลตรวจ ' . $n . ' ความเห็นแล้ว');
    }

    /* ---------------- games: campaigns, tiers, hero ---------------- */

    public function games()
    {
        return view('admin.gameshub.games', [
            'campaigns' => GameCampaign::with('items:id,game_campaign_id,key,name,active')->orderByRaw('hero_rank IS NULL')->orderBy('hero_rank')->orderBy('id')->get(),
        ]);
    }

    public function updateHero(Request $request, GameCampaign $campaign)
    {
        $data = $request->validate(['hero_rank' => ['nullable', 'integer', 'between:1,999'], 'hero_hidden' => ['nullable', 'boolean']]);
        $campaign->update(['hero_rank' => $data['hero_rank'] ?? null, 'hero_hidden' => $request->boolean('hero_hidden')]);

        return back()->with('success', 'บันทึกการแสดงในฮีโร่ของ ' . $campaign->name . ' แล้ว ฮับอัปเดตภายในหนึ่งนาที');
    }

    /* ---------------- announcements ---------------- */

    public function announcements()
    {
        return view('admin.gameshub.announcements', ['announcements' => GamesHubAnnouncement::latest('id')->paginate(20)]);
    }

    private function announcementData(Request $request): array
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:300'],
            // https links or a path on the hub itself; never javascript:, data: or //other-host
            'link_url' => ['nullable', 'string', 'max:500', 'regex:#^(https://[^\s<>"]+|/(?!/)[^\s<>"]*)$#'],
            'link_label' => ['nullable', 'required_with:link_url', 'string', 'max:60'],
            'tone' => ['required', Rule::in(array_keys(GamesHubAnnouncement::TONES))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'active' => ['nullable', 'boolean'],
        ], ['link_url.regex' => 'ลิงก์ต้องขึ้นต้นด้วย https:// หรือ / (หน้าในฮับ)', 'link_label.required_with' => 'ใส่ข้อความบนปุ่มลิงก์ด้วย']);

        // the form is in Thai time; the database keeps UTC like the rest of the app
        foreach (['starts_at', 'ends_at'] as $field) {
            $data[$field] = empty($data[$field]) ? null : Carbon::parse($data[$field], 'Asia/Bangkok')->utc();
        }

        return $data + ['active' => $request->boolean('active')];
    }

    public function storeAnnouncement(Request $request)
    {
        GamesHubAnnouncement::create($this->announcementData($request) + ['created_by' => $request->user()->id]);

        return back()->with('success', 'เพิ่มประกาศแล้ว ฮับแสดงภายในหนึ่งนาทีเมื่อถึงเวลาเริ่ม');
    }

    public function updateAnnouncement(Request $request, GamesHubAnnouncement $announcement)
    {
        $announcement->update($this->announcementData($request));

        return back()->with('success', 'บันทึกประกาศแล้ว');
    }

    public function destroyAnnouncement(GamesHubAnnouncement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'ลบประกาศแล้ว');
    }
}
