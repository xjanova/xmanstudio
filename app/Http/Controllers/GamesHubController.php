<?php

namespace App\Http\Controllers;

use App\Models\GameCampaign;
use App\Models\GameEntitlement;
use App\Models\GamesHubAnnouncement;
use App\Models\Review;
use App\Support\HubOrigin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What the static XGamesHub site reads at runtime (hub.json, reviews.json — public,
 * aggregate, no member ids) and the member-side pages for reviews and supporter items.
 */
class GamesHubController extends Controller
{
    private function hubJson(array $body): JsonResponse
    {
        return response()->json($body)
            ->header('Access-Control-Allow-Origin', HubOrigin::forRequest())
            ->header('Vary', 'Origin')
            ->header('Cache-Control', 'public, max-age=60');
    }

    /** "Somchai J." — reviews show a short form of the XMAN ID name, never the e-mail. */
    public static function publicName(?string $name): string
    {
        $name = trim(explode('@', (string) $name)[0]);
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (! $parts) {
            return 'ผู้เล่น XMAN';
        }
        $first = mb_substr($parts[0], 0, 24);

        return count($parts) > 1 ? $first . ' ' . mb_substr(end($parts), 0, 1) . '.' : $first;
    }

    /** Key art for a game, served by the hub itself. */
    public static function artFor(string $slug): string
    {
        return rtrim(config('game-support.hub_origin'), '/') . (config('game-support.art')[$slug] ?? "/art/{$slug}.webp");
    }

    /** A game's logo, served by the hub itself. */
    public static function logoFor(string $slug): string
    {
        return rtrim(config('game-support.hub_origin'), '/') . (config('game-support.logos')[$slug] ?? "/art/logos/{$slug}.webp");
    }

    /**
     * Sign-in and sign-up in the community's own frame. The forms post to the usual
     * XMAN ID endpoints (same Turnstile and social sign-in); afterwards the member
     * lands back on the community page they came from.
     */
    public function login(Request $request)
    {
        $this->rememberReturn($request);

        return view('game-support.auth', ['mode' => 'login']);
    }

    public function register(Request $request)
    {
        $this->rememberReturn($request);

        return view('game-support.auth', ['mode' => 'register']);
    }

    private function rememberReturn(Request $request): void
    {
        $back = (string) $request->query('back', '');
        $base = url('/games-support');
        // only pages of this community on this host; never the sign-in pages themselves
        $community = $back === $base || str_starts_with($back, $base . '/');
        $authPage = str_starts_with($back, $base . '/login') || str_starts_with($back, $base . '/register');
        if ($community && ! $authPage) {
            $request->session()->put('url.intended', $back);

            return;
        }
        if (! str_starts_with((string) $request->session()->get('url.intended'), $base)) {
            $request->session()->put('url.intended', route('game-support.index'));
        }
    }

    public function hub()
    {
        $campaigns = GameCampaign::get(['slug', 'hero_rank', 'hero_hidden']);

        return $this->hubJson([
            'announcements' => GamesHubAnnouncement::live()->latest('id')->limit(3)->get(['id', 'message', 'link_url', 'link_label', 'tone']),
            'hero' => [
                // pinned first in this order; everything else keeps the hub's own order
                'order' => $campaigns->whereNotNull('hero_rank')->where('hero_hidden', false)->sortBy('hero_rank')->pluck('slug')->values(),
                'hidden' => $campaigns->where('hero_hidden', true)->pluck('slug')->values(),
            ],
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function reviewsJson(GameCampaign $campaign)
    {
        abort_unless($campaign->active, 404);
        $approved = $campaign->reviews()->where('status', 'approved');
        $stats = DB::table('game_ratings')->where('game_campaign_id', $campaign->id)->selectRaw('AVG(stars) AS average, COUNT(*) AS total')->first();

        return $this->hubJson([
            'slug' => $campaign->slug,
            'name' => $campaign->name,
            'stars' => round((float) ($stats->average ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
            'review_count' => (clone $approved)->count(),
            'write_url' => route('game-support.show', $campaign) . '#reviews',
            'reviews' => (clone $approved)->with('user:id,name')->orderByDesc('is_featured')->orderByDesc('approved_at')->limit(30)->get()
                ->map(fn (Review $r) => [
                    'name' => self::publicName($r->user?->name),
                    'rating' => $r->rating,
                    'title' => $r->title,
                    'comment' => $r->comment,
                    'date' => ($r->approved_at ?? $r->created_at)->toDateString(),
                    'featured' => $r->is_featured,
                ])->values(),
        ]);
    }

    /** One review per member per game; editing it sends it back for moderation. */
    public function review(Request $request, GameCampaign $campaign)
    {
        abort_unless($campaign->active, 404);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:100'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ], ['comment.min' => 'เขียนรีวิวอย่างน้อย 10 ตัวอักษร']);
        $user = $request->user();
        DB::transaction(function () use ($data, $user, $campaign) {
            Review::updateOrCreate(
                ['user_id' => $user->id, 'reviewable_type' => GameCampaign::class, 'reviewable_id' => $campaign->id],
                $data + ['status' => 'pending', 'admin_note' => null, 'approved_at' => null, 'is_featured' => false],
            );
            // the stars count right away, the same single rating the star form keeps
            DB::table('game_ratings')->upsert([['user_id' => $user->id, 'game_campaign_id' => $campaign->id, 'stars' => $data['rating'], 'created_at' => now(), 'updated_at' => now()]], ['user_id', 'game_campaign_id'], ['stars', 'updated_at']);
        });

        return redirect()->to(route('game-support.show', $campaign) . '#reviews')
            ->with('success', 'ได้รับรีวิวแล้ว ดาวนับในคะแนนเฉลี่ยทันที ส่วนข้อความจะแสดงหลังทีมตรวจ');
    }

    public function myItems(Request $request)
    {
        return view('game-support.my-items', [
            'entitlements' => GameEntitlement::with(['item.campaign:id,slug,name', 'donation:id,public_id'])
                ->where('user_id', $request->user()->id)->latest()->get(),
        ]);
    }
}
