<?php

namespace Tests\Feature;

use App\Models\GameCampaign;
use App\Models\GameComment;
use App\Models\GameDonation;
use App\Models\User;
use App\Services\GameSupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GameSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutVite();
        config(['security.two_factor.required_for_admins' => false]);
    }

    private function donation(array $values = []): GameDonation
    {
        $campaign = GameCampaign::where('slug', 'breaker')->firstOrFail();

        return GameDonation::create($values + ['public_id' => (string) Str::uuid(), 'game_campaign_id' => $campaign->id, 'user_id' => User::factory()->create()->id, 'amount_satang' => 30000, 'display_name' => 'Private pilot', 'publish_name' => false, 'slip_path' => 'game-slips/test.png', 'slip_mime' => 'image/png', 'slip_hash' => hash('sha256', Str::uuid()), 'bank_snapshot' => config('game-support.bank'), 'reward_snapshot' => app(GameSupportService::class)->reward($campaign, 30000), 'status' => 'pending']);
    }

    private function approval(array $values = []): array
    {
        return $values + ['decision' => 'approved', 'verified_amount' => '300.00', 'bank_reference' => 'SCB-' . Str::random(20), 'recipient_checked' => '1', 'money_received' => '1', 'review_note' => 'ตรวจเงินเข้าและบัญชีผู้รับแล้ว'];
    }

    public function test_public_pages_and_catalog_render_without_fake_totals(): void
    {
        $this->get('/games-support')->assertOk()->assertSee('อันดับ')->assertSee('X-NOVA: BREAKER');
        $this->get('/games-support/breaker')->assertOk()->assertSee('411-148476-9')->assertSee('150,000')->assertDontSee('Private pilot');
        $this->get('/games-support/summary.json')->assertOk()->assertHeader('Access-Control-Allow-Origin', 'https://xgameshub.xman4289.com')->assertJsonFragment(['slug' => 'breaker', 'raised' => 0]);
    }

    public function test_guest_cannot_write_and_non_admin_cannot_read_slips_or_approve(): void
    {
        $d = $this->donation();
        $this->post('/games-support/breaker/donations', [])->assertRedirect(route('game-support.login'));
        $this->actingAs(User::factory()->create())->get('/admin/game-support/donations/' . $d->id . '/slip')->assertForbidden();
        $this->post('/admin/game-support/donations/' . $d->id . '/review', $this->approval())->assertForbidden();
        $this->assertSame('pending', $d->fresh()->status);
    }

    public function test_donation_stores_private_slip_and_comment_pending_with_reward_snapshot(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post('/games-support/breaker/donations', ['amount' => '500.25', 'display_name' => 'Pilot', 'publish_name' => '0', 'comment' => 'ลองเพิ่มอาวุธ', 'publish_comment' => '1', 'consent' => '1', 'slip' => UploadedFile::fake()->image('slip.png')])->assertRedirect()->assertSessionHasNoErrors();
        $d = GameDonation::firstOrFail();
        $this->assertSame(50025, $d->amount_satang);
        $this->assertSame('pending', $d->status);
        $this->assertSame('SALVAGER', $d->reward_snapshot['name']);
        Storage::disk('local')->assertExists($d->slip_path);
        $this->assertDatabaseHas('game_comments', ['status' => 'pending', 'display_name' => 'ผู้สนับสนุนไม่เปิดเผยชื่อ']);
        $this->get('/games-support/summary.json')->assertJsonFragment(['slug' => 'breaker', 'raised' => 0]);
    }

    public function test_fractional_satang_and_non_image_slips_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/games-support/breaker/donations', ['amount' => '99.999', 'display_name' => 'Pilot', 'consent' => '1', 'slip' => UploadedFile::fake()->image('slip.png')])->assertSessionHasErrors('amount');
        $this->post('/games-support/breaker/donations', ['amount' => '100', 'display_name' => 'Pilot', 'consent' => '1', 'slip' => UploadedFile::fake()->create('exploit.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('slip');
        $this->assertDatabaseCount('game_donations', 0);
    }

    public function test_same_slip_is_rejected_across_games(): void
    {
        $this->actingAs(User::factory()->create());
        $image = UploadedFile::fake()->image('s.png');
        $bytes = file_get_contents($image->getRealPath());
        foreach (['breaker', 'hive-breach'] as $i => $slug) {
            $response = $this->post('/games-support/' . $slug . '/donations', ['amount' => '100', 'display_name' => 'Pilot', 'consent' => '1', 'slip' => UploadedFile::fake()->createWithContent('same.png', $bytes)]);
            $i === 0 ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('slip');
        }
        $this->assertDatabaseCount('game_donations', 1);
    }

    public function test_approval_requires_recipient_money_and_exact_amount(): void
    {
        $d = $this->donation();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $url = '/admin/game-support/donations/' . $d->id . '/review';
        $this->post($url, $this->approval(['money_received' => '0']))->assertSessionHasErrors('money_received');
        $this->post($url, $this->approval(['recipient_checked' => '0']))->assertSessionHasErrors('recipient_checked');
        $this->post($url, $this->approval(['verified_amount' => '301']))->assertSessionHasErrors('verified_amount');
        $this->assertSame('pending', $d->fresh()->status);
    }

    public function test_approved_totals_count_once_and_anonymous_identity_stays_private(): void
    {
        $d = $this->donation();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $url = '/admin/game-support/donations/' . $d->id . '/review';
        $this->post($url, $this->approval())->assertSessionHasNoErrors();
        $this->post($url, $this->approval())->assertSessionHasErrors('status');
        $this->get('/games-support/summary.json')->assertJsonFragment(['slug' => 'breaker', 'raised' => 300, 'supporters' => 1])->assertDontSee('Private pilot')->assertDontSee('slip_path');
        $this->get('/games-support/breaker')->assertOk()->assertDontSee('Private pilot');
        $this->assertCount(1, $d->fresh()->audit);
    }

    public function test_bank_reference_cannot_be_reused_and_void_removes_total(): void
    {
        $a = $this->donation();
        $b = $this->donation();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->approval(['bank_reference' => 'SCB-123456789']);
        $this->post('/admin/game-support/donations/' . $a->id . '/review', $data)->assertSessionHasNoErrors();
        $this->post('/admin/game-support/donations/' . $b->id . '/review', $data)->assertSessionHasErrors('bank_reference');
        $this->post('/admin/game-support/donations/' . $a->id . '/void', ['note' => 'คืนเงินผ่านธนาคารแล้ว'])->assertSessionHasNoErrors();
        $this->get('/games-support/summary.json')->assertJsonFragment(['slug' => 'breaker', 'raised' => 0]);
        $this->assertSame('revoked', $a->fresh()->reward_status);
        $this->post('/admin/game-support/donations/' . $b->id . '/review', $data)->assertSessionHasErrors('bank_reference');
    }

    public function test_public_donor_opt_in_and_reward_fulfillment(): void
    {
        $d = $this->donation(['publish_name' => true, 'display_name' => 'Public pilot']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/game-support/donations/' . $d->id . '/review', $this->approval())->assertSessionHasNoErrors();
        $this->post('/admin/game-support/donations/' . $d->id . '/reward', ['note' => 'แสดงตราและรายชื่อแล้ว'])->assertSessionHasNoErrors();
        $this->get('/games-support/breaker')->assertOk()->assertSee('Public pilot')->assertSee('SALVAGER')->assertSee('ส่งมอบรางวัลแล้ว');
        $this->assertSame('delivered', $d->fresh()->reward_status);
        $this->post('/admin/game-support/donations/' . $d->id . '/reward', ['note' => 'ซ้ำ'])->assertStatus(409);
    }

    public function test_vote_moves_between_games_and_rating_updates_instead_of_adding(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/games-support/breaker/vote')->assertSessionHasNoErrors();
        $this->post('/games-support/hive-breach/vote')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('game_votes', 1);
        $this->assertSame(GameCampaign::where('slug', 'hive-breach')->value('id'), DB::table('game_votes')->value('game_campaign_id'));
        $this->post('/games-support/breaker/rating', ['stars' => 5])->assertSessionHasNoErrors();
        $this->post('/games-support/breaker/rating', ['stars' => 3])->assertSessionHasNoErrors();
        $this->post('/games-support/breaker/rating', ['stars' => 6])->assertSessionHasErrors('stars');
        $this->assertDatabaseCount('game_ratings', 1);
        $this->assertSame(3, DB::table('game_ratings')->value('stars'));
    }

    public function test_comments_need_moderation_and_are_escaped(): void
    {
        $this->actingAs(User::factory()->create())->post('/games-support/breaker/comments', ['display_name' => 'Writer', 'body' => '<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->get('/games-support/breaker')->assertDontSee('alert(1)');
        $comment = GameComment::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/game-support/comments/' . $comment->id, ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->get('/games-support/breaker')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_paused_campaign_rejects_actions_and_admin_page_renders(): void
    {
        GameCampaign::where('slug', 'breaker')->update(['active' => false]);
        $this->actingAs(User::factory()->create())->post('/games-support/breaker/vote')->assertNotFound();
        $this->get('/games-support/breaker')->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/game-support')->assertOk()->assertSee('ตรวจสลิป');
    }

    public function test_campaign_rewards_are_snapshotted_and_slug_stays_stable(): void
    {
        $d = $this->donation();
        $original = $d->reward_snapshot;
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = ['slug' => 'breaker', 'name' => 'X-NOVA: BREAKER', 'goal' => 150000, 'active' => 1, 'tiers_json' => json_encode([['minimum' => 100, 'name' => 'NEW', 'rewards' => ['New reward']]])];
        $this->post('/admin/game-support/campaigns/breaker', $data)->assertSessionHasNoErrors();
        // MySQL returns JSON objects with its own key order; the content must not change
        $canon = function (array $a): array {
            ksort($a);

            return $a;
        };
        $this->assertSame($canon($original), $canon($d->fresh()->reward_snapshot));
        $this->assertSame('NEW', GameCampaign::where('slug', 'breaker')->first()->tiers[0]['name']);
        $this->post('/admin/game-support/campaigns/breaker', array_replace($data, ['slug' => 'changed']))->assertSessionHasErrors('slug');
    }

    public function test_admin_slip_response_is_private_and_rejection_never_counts(): void
    {
        $d = $this->donation();
        Storage::disk('local')->put($d->slip_path, 'test image bytes');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $r = $this->get('/admin/game-support/donations/' . $d->id . '/slip')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->post('/admin/game-support/donations/' . $d->id . '/review', ['decision' => 'rejected', 'review_note' => 'No matching bank credit'])->assertSessionHasNoErrors();
        $this->assertSame('not_eligible', $d->fresh()->reward_status);
        $this->get('/games-support/summary.json')->assertJsonFragment(['slug' => 'breaker', 'raised' => 0]);
    }
}
