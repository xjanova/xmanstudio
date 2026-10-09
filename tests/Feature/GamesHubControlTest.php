<?php

namespace Tests\Feature;

use App\Models\GameCampaign;
use App\Models\GameComment;
use App\Models\GameDonation;
use App\Models\GameEntitlement;
use App\Models\GameItem;
use App\Models\GamesHubAnnouncement;
use App\Models\Review;
use App\Models\User;
use App\Services\GameItemService;
use App\Services\GameSupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GamesHubControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutVite();
        config(['security.two_factor.required_for_admins' => false]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function item(array $values = []): GameItem
    {
        return GameCampaign::where('slug', 'breaker')->firstOrFail()->items()->create($values + ['key' => 'founder-badge', 'name' => 'ตรา Founder', 'kind' => 'badge', 'max_devices' => 2, 'active' => true]);
    }

    private function donation(User $donor, int $satang = 30000): GameDonation
    {
        $campaign = GameCampaign::where('slug', 'breaker')->firstOrFail();

        return GameDonation::create(['public_id' => (string) Str::uuid(), 'game_campaign_id' => $campaign->id, 'user_id' => $donor->id, 'amount_satang' => $satang, 'display_name' => 'Pilot', 'publish_name' => false, 'slip_path' => 'game-slips/t.png', 'slip_mime' => 'image/png', 'slip_hash' => hash('sha256', Str::uuid()), 'bank_snapshot' => config('game-support.bank'), 'reward_snapshot' => app(GameSupportService::class)->reward($campaign, $satang), 'status' => 'pending']);
    }

    private function approve(GameDonation $d): void
    {
        $this->post('/admin/game-support/donations/' . $d->id . '/review', ['decision' => 'approved', 'verified_amount' => number_format($d->amount_satang / 100, 2, '.', ''), 'bank_reference' => 'SCB-' . Str::random(16), 'recipient_checked' => '1', 'money_received' => '1', 'review_note' => 'ตรวจเงินเข้าแล้ว'])->assertSessionHasNoErrors();
    }

    private function tiersWithItem(array $items): array
    {
        return ['slug' => 'breaker', 'name' => 'X-NOVA: BREAKER', 'goal' => 150000, 'active' => 1, 'tiers_json' => json_encode([
            ['minimum' => 100, 'name' => 'SPARK', 'rewards' => ['ตราบนหน้าโครงการ']],
            ['minimum' => 300, 'name' => 'SALVAGER', 'rewards' => ['ตราบนหน้าโครงการ'], 'items' => $items],
        ])];
    }

    public function test_approved_donation_grants_item_codes_once_and_void_revokes_them(): void
    {
        $this->actingAs($admin = $this->admin());
        $this->post('/admin/gameshub/items', ['game' => 'breaker', 'key' => 'founder-badge', 'name' => 'ตรา Founder', 'kind' => 'badge', 'max_devices' => 2, 'active' => '1'])->assertSessionHasNoErrors();
        $this->post('/admin/game-support/campaigns/breaker', $this->tiersWithItem(['founder-badge']))->assertSessionHasNoErrors();

        $donor = User::factory()->create();
        $small = $this->donation($donor, 10000);
        $d = $this->donation($donor, 30000);
        $this->assertSame([['key' => 'founder-badge', 'name' => 'ตรา Founder', 'kind' => 'badge']], $d->reward_snapshot['items']);
        $this->assertSame([], $small->reward_snapshot['items']);

        $this->approve($d);
        $this->approve($small);
        $this->assertSame(1, GameEntitlement::count());
        $e = GameEntitlement::firstOrFail();
        $this->assertMatchesRegularExpression('/^XG-[0-9A-HJKMNP-TV-Z]{4}(-[0-9A-HJKMNP-TV-Z]{4}){3}$/', $e->code);
        $this->assertSame(GameItemService::hash($e->code), $e->code_hash);
        $this->assertStringNotContainsString($e->code, (string) DB::table('game_entitlements')->value('code'));
        // granting again for the same donation never adds a copy
        app(GameItemService::class)->grantForDonation($d->fresh(), $admin->id);
        $this->assertSame(1, GameEntitlement::count());

        $this->actingAs($donor)->get('/games-support/my-items')->assertOk()->assertSee($e->code)->assertSee('ตรา Founder');
        $this->get('/games-support/breaker')->assertOk()->assertSee($e->code);
        $this->actingAs(User::factory()->create())->get('/games-support/my-items')->assertOk()->assertDontSee($e->code);

        $this->actingAs($admin)->post('/admin/game-support/donations/' . $d->id . '/void', ['note' => 'คืนเงินแล้ว'])->assertSessionHasNoErrors();
        $this->assertSame('revoked', $e->fresh()->status);
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => $e->code, 'device' => 'device-aaaa'])->assertStatus(410)->assertJson(['error' => 'revoked']);
    }

    public function test_tier_items_must_exist_and_item_keys_cannot_change(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/game-support/campaigns/breaker', $this->tiersWithItem(['missing-item']))->assertSessionHasErrors();
        $item = $this->item();
        $this->post('/admin/gameshub/items/' . $item->id, ['key' => 'renamed', 'name' => 'x', 'kind' => 'badge', 'max_devices' => 1])->assertSessionHasErrors('key');
        $this->post('/admin/gameshub/items', ['game' => 'breaker', 'key' => 'founder-badge', 'name' => 'dup', 'kind' => 'badge', 'max_devices' => 1])->assertSessionHasErrors('key');
        $this->post('/admin/gameshub/items', ['game' => 'breaker', 'key' => 'Bad Key!', 'name' => 'x', 'kind' => 'badge', 'max_devices' => 1])->assertSessionHasErrors('key');

        // a deactivated item is no longer promised to new donations
        $this->post('/admin/game-support/campaigns/breaker', $this->tiersWithItem(['founder-badge']))->assertSessionHasNoErrors();
        $campaign = GameCampaign::where('slug', 'breaker')->firstOrFail();
        $this->assertCount(1, app(GameSupportService::class)->reward($campaign, 30000)['items']);
        $item->update(['active' => false]);
        $this->assertSame([], app(GameSupportService::class)->reward($campaign->fresh(), 30000)['items']);
    }

    public function test_redeem_checks_game_counts_devices_once_and_stops_at_the_limit(): void
    {
        $item = $this->item();
        $member = User::factory()->create(['email' => 'pilot@example.com']);
        $this->actingAs($this->admin())->post('/admin/gameshub/entitlements', ['item_id' => $item->id, 'email' => 'nobody@example.com', 'note' => 'x'])->assertSessionHasErrors('email');
        $this->post('/admin/gameshub/entitlements', ['item_id' => $item->id, 'email' => 'pilot@example.com', 'note' => 'ชดเชยบั๊ก'])->assertSessionHasNoErrors();
        $e = GameEntitlement::where('user_id', $member->id)->firstOrFail();
        $typed = strtolower(str_replace(['XG-', '-'], ['xg ', ' '], $e->code));

        $this->postJson('/api/gameshub/redeem', ['game' => 'xnova', 'code' => $e->code, 'device' => 'device-aaaa'])->assertStatus(409)->assertJson(['error' => 'wrong_game']);
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => $typed, 'device' => 'device-aaaa'])->assertOk()
            ->assertJson(['ok' => true, 'item' => ['key' => 'founder-badge'], 'devices_used' => 1, 'devices_max' => 2, 'already_on_this_device' => false]);
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => $e->code, 'device' => 'device-aaaa'])->assertOk()->assertJson(['devices_used' => 1, 'already_on_this_device' => true]);
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => $e->code, 'device' => 'device-bbbb'])->assertOk()->assertJson(['devices_used' => 2]);
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => $e->code, 'device' => 'device-cccc'])->assertStatus(409)->assertJson(['error' => 'device_limit']);
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => 'XG-0000-0000-0000-0000', 'device' => 'device-aaaa'])->assertNotFound()->assertJson(['error' => 'invalid_code']);
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => $e->code])->assertStatus(422)->assertJson(['error' => 'invalid_request']);
        $this->assertSame(2, $e->fresh()->redeem_count);
        $this->assertDatabaseCount('game_item_redemptions', 2);
        $this->assertNotNull($e->fresh()->first_redeemed_at);

        $this->post('/admin/gameshub/entitlements/' . $e->id . '/revoke', ['note' => 'แจกผิดคน'])->assertSessionHasNoErrors();
        $this->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => $e->code, 'device' => 'device-aaaa'])->assertStatus(410);
    }

    public function test_redeem_is_open_to_games_on_other_origins(): void
    {
        $this->withHeaders(['Origin' => 'https://xgameshub.xman4289.com'])
            ->postJson('/api/gameshub/redeem', ['game' => 'breaker', 'code' => 'XG-1111-2222-3333-4444', 'device' => 'device-aaaa'])
            ->assertNotFound()->assertHeader('Access-Control-Allow-Origin');
    }

    public function test_reviews_need_approval_show_short_names_and_editing_sends_them_back(): void
    {
        $player = User::factory()->create(['name' => 'Somchai Jaidee', 'email' => 'somchai@example.com']);
        $this->actingAs($player)->post('/games-support/breaker/review', ['rating' => 4, 'title' => 'สนุก', 'comment' => '<b>ยิงชิ้นส่วนศัตรูมาใช้ สนุกมาก</b>'])->assertSessionHasNoErrors();
        $this->post('/games-support/breaker/review', ['rating' => 5, 'comment' => 'สั้น'])->assertSessionHasErrors('comment');
        $review = Review::where('reviewable_type', GameCampaign::class)->firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertSame(4, DB::table('game_ratings')->where('user_id', $player->id)->value('stars'));
        $this->getJson('/games-support/breaker/reviews.json')->assertOk()->assertJson(['review_count' => 0, 'rating_count' => 1, 'reviews' => []])
            ->assertHeader('Access-Control-Allow-Origin', 'https://xgameshub.xman4289.com');

        $this->post('/admin/gameshub/reviews/' . $review->id, ['status' => 'approved'])->assertForbidden();
        $this->actingAs($admin = $this->admin());
        $this->post('/admin/gameshub/reviews/' . $review->id, ['status' => 'rejected'])->assertSessionHasErrors('admin_note');
        $this->post('/admin/gameshub/reviews/' . $review->id, ['status' => 'rejected', 'admin_note' => 'ข้อความโฆษณา'])->assertSessionHasNoErrors();
        $this->actingAs($player)->get('/games-support/breaker')->assertSee('ข้อความโฆษณา');
        $this->post('/games-support/breaker/review', ['rating' => 5, 'comment' => '<b>ยิงชิ้นส่วนศัตรูมาใช้ สนุกมาก</b>'])->assertSessionHasNoErrors();
        $this->assertSame('pending', $review->fresh()->status);
        $this->assertSame(1, Review::count());

        $this->actingAs($admin)->post('/admin/gameshub/reviews/' . $review->id, ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->post('/admin/gameshub/reviews/' . $review->id . '/feature')->assertSessionHasNoErrors();
        $json = $this->getJson('/games-support/breaker/reviews.json')->assertOk()
            ->assertJson(['review_count' => 1, 'reviews' => [['name' => 'Somchai J.', 'rating' => 5, 'featured' => true]]])->json();
        $this->assertStringNotContainsString('somchai@example.com', json_encode($json));
        $this->get('/games-support/breaker')->assertSee('Somchai J.')->assertDontSee('<b>ยิงชิ้นส่วน', false);
        $this->getJson('/games-support/summary.json')->assertJsonFragment(['slug' => 'breaker', 'review_count' => 1]);
    }

    public function test_bulk_moderation_for_reviews_and_comments_only_touches_game_rows(): void
    {
        $campaign = GameCampaign::where('slug', 'breaker')->firstOrFail();
        $ids = collect(range(1, 3))->map(fn () => Review::create(['user_id' => User::factory()->create()->id, 'reviewable_type' => GameCampaign::class, 'reviewable_id' => $campaign->id, 'rating' => 5, 'comment' => 'เกมดีมาก ๆ เลยครับ'])->id);
        $other = Review::create(['user_id' => User::factory()->create()->id, 'reviewable_type' => 'App\\Models\\Product', 'reviewable_id' => 1, 'rating' => 1, 'comment' => 'not a game review']);
        $comments = collect(range(1, 2))->map(fn () => GameComment::create(['game_campaign_id' => $campaign->id, 'user_id' => User::factory()->create()->id, 'display_name' => 'P', 'body' => 'อยากได้ด่านใหม่', 'status' => 'pending'])->id);

        $this->actingAs($this->admin());
        $this->post('/admin/gameshub/reviews/bulk', ['ids' => [...$ids, $other->id], 'status' => 'rejected'])->assertSessionHasErrors('admin_note');
        $this->post('/admin/gameshub/reviews/bulk', ['ids' => [...$ids, $other->id], 'status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame(3, Review::where('status', 'approved')->count());
        $this->assertSame('pending', $other->fresh()->status);
        $this->post('/admin/gameshub/reviews/' . $other->id, ['status' => 'approved'])->assertNotFound();

        $this->post('/admin/gameshub/comments/bulk', ['ids' => $comments->all(), 'status' => 'rejected', 'moderation_note' => 'ซ้ำ'])->assertSessionHasNoErrors();
        $this->assertSame(2, GameComment::where('status', 'rejected')->where('moderation_note', 'ซ้ำ')->count());
        $this->post('/admin/game-support/comments/' . $comments[0], ['status' => 'approved', 'moderation_note' => 'ตรวจใหม่แล้ว'])->assertSessionHasNoErrors();
        $this->get('/games-support/breaker')->assertSee('อยากได้ด่านใหม่');
    }

    public function test_hub_json_shows_live_announcements_and_the_hero_order(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/gameshub/announcements', ['message' => 'x', 'tone' => 'info', 'link_url' => 'javascript:alert(1)', 'link_label' => 'go', 'active' => '1'])->assertSessionHasErrors('link_url');
        $this->post('/admin/gameshub/announcements', ['message' => 'x', 'tone' => 'info', 'link_url' => '//evil.example/x', 'link_label' => 'go', 'active' => '1'])->assertSessionHasErrors('link_url');
        $this->post('/admin/gameshub/announcements', ['message' => 'จบเองพรุ่งนี้', 'tone' => 'info', 'ends_at' => now('Asia/Bangkok')->addDay()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
        $this->post('/admin/gameshub/announcements', ['message' => 'เปิดเกมใหม่แล้ว', 'tone' => 'event', 'link_url' => '/play/krungsri/', 'link_label' => 'เล่นเลย', 'active' => '1'])->assertSessionHasNoErrors();
        $this->post('/admin/gameshub/announcements', ['message' => 'อนาคต', 'tone' => 'info', 'starts_at' => now('Asia/Bangkok')->addDay()->format('Y-m-d\TH:i'), 'active' => '1'])->assertSessionHasNoErrors();
        $this->post('/admin/gameshub/announcements', ['message' => 'ปิดอยู่', 'tone' => 'info', 'active' => '0'])->assertSessionHasNoErrors();
        $this->post('/admin/gameshub/announcements', ['message' => 'เวลาไทย', 'tone' => 'info', 'starts_at' => '2026-10-09T10:00', 'ends_at' => '2026-10-09T09:00'])->assertSessionHasErrors('ends_at');
        $this->post('/admin/gameshub/announcements', ['message' => 'เวลาไทย', 'tone' => 'info', 'starts_at' => '2026-10-09T10:00', 'active' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-09 03:00:00', GamesHubAnnouncement::where('message', 'เวลาไทย')->first()->starts_at->utc()->format('Y-m-d H:i:s'));

        $this->post('/admin/gameshub/games/xnova/hero', ['hero_rank' => 1])->assertSessionHasNoErrors();
        $this->post('/admin/gameshub/games/breaker/hero', ['hero_rank' => 2])->assertSessionHasNoErrors();
        $this->post('/admin/gameshub/games/snake/hero', ['hero_hidden' => '1'])->assertSessionHasNoErrors();

        $json = $this->getJson('/games-support/hub.json')->assertOk()->assertHeader('Access-Control-Allow-Origin', 'https://xgameshub.xman4289.com')->json();
        $this->assertSame(['เวลาไทย', 'เปิดเกมใหม่แล้ว'], array_column($json['announcements'], 'message'));
        $this->assertSame(['xnova', 'breaker'], $json['hero']['order']);
        $this->assertSame(['snake'], $json['hero']['hidden']);

        $a = GamesHubAnnouncement::where('message', 'เปิดเกมใหม่แล้ว')->firstOrFail();
        $this->delete('/admin/gameshub/announcements/' . $a->id)->assertSessionHasNoErrors();
        $this->assertModelMissing($a);
    }

    public function test_back_office_pages_render_for_admins_only(): void
    {
        $this->item();
        $pages = ['/admin/gameshub', '/admin/gameshub/items', '/admin/gameshub/items?game=breaker', '/admin/gameshub/reviews', '/admin/gameshub/comments', '/admin/gameshub/games', '/admin/gameshub/announcements', '/admin/game-support'];
        $this->actingAs(User::factory()->create());
        foreach ($pages as $page) {
            $this->get($page)->assertForbidden();
        }
        $this->actingAs($this->admin());
        foreach ($pages as $page) {
            $this->get($page)->assertOk()->assertSee('XGamesHub');
        }
        $this->get('/admin/gameshub/items?game=breaker')->assertSee('founder-badge');
    }
}
