<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AppBugReportController;
use App\Models\BugReport;
use App\Models\BugReportAttachment;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\BugReports\SecretScrubber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * "แจ้งปัญหา" from the desktop apps — POST /api/v1/product/{slug}/bug-reports — and the admin pages
 * that review those reports.
 */
class AppBugReportApiTest extends TestCase
{
    use RefreshDatabase;

    private const CLUADEX = 'cluadex-ai-coding-assistant';

    private const MACHINE = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    private Product $cluadex;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('local');
        $this->withoutVite();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->cluadex = Product::firstOrCreate(['slug' => self::CLUADEX], [
            'category_id' => $category->id, 'name' => 'CluadeX', 'description' => 'x', 'price' => 0,
            'requires_license' => true, 'is_active' => true,
        ]);
    }

    private function url(string $slug = self::CLUADEX): string
    {
        return "/api/v1/product/{$slug}/bug-reports";
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'client_report_id' => 'f3b0c2de-7a1e-4c55-9d2f-0123456789ab',
            'machine_id' => self::MACHINE,
            'message' => 'กดส่งข้อความแล้วแอปค้าง ต้องปิดเปิดใหม่',
            'category' => 'bug',
            'app_version' => '3.0.277',
            'os_version' => 'Windows 11 10.0.26300 x64',
            'diagnostics' => [
                'app' => ['version' => '3.0.277', 'install' => 'installed'],
                'system' => ['os' => 'Windows 11', 'ram_gb' => 32],
                'recent_errors' => [['time' => '2026-11-09T10:00:00Z', 'source' => 'Chat', 'message' => 'TaskCanceledException']],
            ],
            'log_excerpt' => "[10:00:00] INFO  Chat  send\n[10:00:01] ERROR Chat  timeout",
            'included' => ['app', 'system', 'recent_errors', 'log'],
        ], $over);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private static function png(): string
    {
        $img = imagecreatetruecolor(4, 3);
        ob_start();
        imagepng($img);

        return base64_encode((string) ob_get_clean());
    }

    // ── store ───────────────────────────────────────────────────────────

    public function test_a_report_is_stored_with_a_ticket_and_only_admins_can_read_it(): void
    {
        $res = $this->postJson($this->url(), $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('error_code', null)
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.duplicate', false);

        $ticket = $res->json('data.ticket');
        $this->assertMatchesRegularExpression('/^BR-\d{6}-[A-HJ-NP-Z2-9]{5}$/', $ticket);

        $report = BugReport::where('ticket', $ticket)->firstOrFail();
        $this->assertSame(BugReport::CHANNEL_APP, $report->channel);
        $this->assertSame($this->cluadex->id, $report->product_id);
        $this->assertSame('CluadeX', $report->product_name);
        $this->assertSame('กดส่งข้อความแล้วแอปค้าง ต้องปิดเปิดใหม่', $report->description);
        $this->assertSame('กดส่งข้อความแล้วแอปค้าง ต้องปิดเปิดใหม่', $report->title);
        $this->assertSame(32, $report->additional_info['system']['ram_gb']);
        $this->assertStringContainsString('ERROR Chat  timeout', $report->log_excerpt);
        $this->assertSame(self::MACHINE, $report->device_id);
        $this->assertNull($report->user_id);

        // never on the old public list
        $this->getJson('/api/v1/bug-reports')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/bug-reports/' . $report->id)->assertNotFound();
        $this->getJson('/api/v1/bug-reports/stats')->assertOk()->assertJsonPath('data.total', 0);
    }

    public function test_the_same_report_sent_twice_keeps_one_row_and_one_ticket(): void
    {
        $first = $this->postJson($this->url(), $this->payload())->assertCreated()->json('data.ticket');

        $this->postJson($this->url(), $this->payload(['message' => 'ส่งซ้ำจากคิว']))
            ->assertOk()
            ->assertJsonPath('data.ticket', $first)
            ->assertJsonPath('data.duplicate', true);

        $this->assertSame(1, BugReport::count());
        $this->assertSame('กดส่งข้อความแล้วแอปค้าง ต้องปิดเปิดใหม่', BugReport::first()->description);

        // a new report from the same machine is a new ticket
        $second = $this->postJson($this->url(), $this->payload(['client_report_id' => 'second-report-0001']))
            ->assertCreated()->json('data.ticket');
        $this->assertNotSame($first, $second);
    }

    public function test_a_key_of_this_product_links_the_report_to_its_account(): void
    {
        $owner = User::factory()->create();
        LicenseKey::create([
            'product_id' => $this->cluadex->id, 'user_id' => $owner->id, 'license_key' => 'ABCD-EFGH-JKLM-NPQR',
            'status' => 'active', 'license_type' => 'yearly', 'max_activations' => 1, 'activations' => 1,
        ]);

        $this->postJson($this->url(), $this->payload(['license_key' => 'abcd-efgh-jklm-npqr', 'contact_email' => 'me@example.com']))
            ->assertCreated();

        $report = BugReport::firstOrFail();
        $this->assertSame($owner->id, $report->user_id);
        $this->assertNotNull($report->license_key_id);
        $this->assertSame('ABCD…NPQR', $report->metadata['license_masked']);
        $this->assertSame('me@example.com', $report->user_email);
    }

    public function test_a_key_bought_on_the_accounts_order_links_through_the_order(): void
    {
        $buyer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $buyer->id, 'customer_name' => $buyer->name, 'customer_email' => $buyer->email,
            'customer_phone' => '0800000000', 'subtotal' => 399, 'total' => 399, 'status' => 'completed', 'payment_status' => 'paid',
        ]);
        LicenseKey::create([
            'product_id' => $this->cluadex->id, 'order_id' => $order->id, 'license_key' => 'WXYZ-2345-6789-ABCD',
            'status' => 'active', 'license_type' => 'lifetime', 'max_activations' => 1, 'activations' => 0,
        ]);

        $this->postJson($this->url(), $this->payload(['license_key' => 'WXYZ-2345-6789-ABCD']))->assertCreated();

        $this->assertSame($buyer->id, BugReport::firstOrFail()->user_id);
    }

    public function test_a_key_of_another_product_or_a_typed_email_links_nothing(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $category = Category::firstOrFail();
        $other = Product::create([
            'category_id' => $category->id, 'slug' => 'other-app', 'name' => 'Other', 'description' => 'x', 'price' => 0,
            'requires_license' => true, 'is_active' => true,
        ]);
        LicenseKey::create([
            'product_id' => $other->id, 'user_id' => $owner->id, 'license_key' => 'OTHR-AAAA-BBBB-CCCC',
            'status' => 'active', 'license_type' => 'yearly', 'max_activations' => 1, 'activations' => 0,
        ]);

        $this->postJson($this->url(), $this->payload([
            'license_key' => 'OTHR-AAAA-BBBB-CCCC', 'contact_email' => 'owner@example.com',
        ]))->assertCreated();

        $report = BugReport::firstOrFail();
        $this->assertNull($report->user_id);
        $this->assertNull($report->license_key_id);
        $this->assertSame('owner@example.com', $report->user_email);
    }

    public function test_secrets_that_slipped_past_the_app_are_masked_before_storing(): void
    {
        $this->postJson($this->url(), $this->payload([
            'message' => 'ใช้คีย์ sk-ant-api03-abcdefghijklmnopqrstuvwxyz0123 แล้วขึ้น 401',
            'log_excerpt' => "Authorization: Bearer abcdefghijklmnopqrstuvwxyz\napi_key=AIzaSyA1234567890abcdefghijklmnopqrstu\nlicense FREE-ABCD-EFGH-IJKL",
            'diagnostics' => ['settings' => ['openai' => 'sk-proj-ABCDEFGHIJKLMNOPQRSTUVWX', 'theme' => 'dark']],
        ]))->assertCreated();

        $report = BugReport::firstOrFail();
        $this->assertStringNotContainsString('sk-ant-api03', $report->description);
        $this->assertStringContainsString(SecretScrubber::MASK, $report->description);
        $this->assertStringContainsString('401', $report->description);
        $this->assertStringNotContainsString('abcdefghijklmnopqrstuvwxyz', $report->log_excerpt);
        $this->assertStringNotContainsString('AIzaSy', $report->log_excerpt);
        $this->assertStringNotContainsString('FREE-ABCD', $report->log_excerpt);
        $this->assertStringContainsString('Bearer ' . SecretScrubber::MASK, $report->log_excerpt);
        $this->assertSame(SecretScrubber::MASK, $report->additional_info['settings']['openai']);
        $this->assertSame('dark', $report->additional_info['settings']['theme']);
    }

    public function test_scrubber_leaves_tickets_versions_and_guids_alone(): void
    {
        $text = 'BR-261109-K7Q4M v3.0.277 0f8fad5b-d9cb-469f-a165-70867728950e 10.0.26300';
        $this->assertSame($text, SecretScrubber::text($text));
    }

    public function test_a_screenshot_is_kept_privately_and_shown_to_admins_only(): void
    {
        $ticket = $this->postJson($this->url(), $this->payload(['screenshot' => 'data:image/png;base64,' . self::png()]))
            ->assertCreated()->json('data.ticket');

        $report = BugReport::where('ticket', $ticket)->firstOrFail();
        $shot = $report->attachments()->firstOrFail();
        $this->assertSame('png', $shot->file_type);
        Storage::disk('local')->assertExists($shot->file_path);
        $this->assertTrue($report->metadata['has_screenshot']);

        $route = route('admin.bug-reports.attachment', [$report, $shot]);
        $this->get($route)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($route)->assertForbidden();
        $this->actingAs($this->admin())->get($route)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        // an attachment of another report is not reachable through this one
        $other = BugReport::create(['product_name' => 'x', 'title' => 'x', 'description' => 'x']);
        $this->actingAs($this->admin())
            ->get(route('admin.bug-reports.attachment', [$other, $shot]))
            ->assertNotFound();
    }

    public function test_a_screenshot_that_is_not_a_png_or_jpeg_is_refused(): void
    {
        $this->postJson($this->url(), $this->payload(['screenshot' => base64_encode('<svg onload=alert(1)>')]))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_SCREENSHOT');

        $this->postJson($this->url(), $this->payload(['screenshot' => 'not base64 at all!!']))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_SCREENSHOT');

        $this->assertSame(0, BugReport::count());
    }

    public function test_missing_or_empty_words_and_a_bad_category_are_validation_failed(): void
    {
        $this->postJson($this->url(), $this->payload(['message' => null]))
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');
        $this->postJson($this->url(), $this->payload(['message' => "   \n  "]))
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');
        $this->postJson($this->url(), $this->payload(['category' => 'misclassification']))
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');
        $this->postJson($this->url(), $this->payload(['client_report_id' => 'bad id with spaces']))
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');
        $this->postJson($this->url(), $this->payload(['message' => str_repeat('ก', 5001)]))
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');

        $this->assertSame(0, BugReport::count());
    }

    public function test_an_unknown_product_is_product_not_found(): void
    {
        $this->postJson($this->url('no-such-app'), $this->payload())
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PRODUCT_NOT_FOUND');
    }

    public function test_an_oversized_body_is_refused_before_it_is_stored(): void
    {
        $this->postJson($this->url(), $this->payload([
            'log_excerpt' => str_repeat('x', AppBugReportController::MAX_BODY_BYTES + 10),
        ]))->assertStatus(413)->assertJsonPath('error_code', 'PAYLOAD_TOO_LARGE');

        $this->postJson($this->url(), $this->payload([
            'diagnostics' => ['blob' => str_repeat('y', 300 * 1024)],
        ]))->assertStatus(413)->assertJsonPath('error_code', 'PAYLOAD_TOO_LARGE');

        $this->assertSame(0, BugReport::count());
    }

    public function test_ten_a_minute_per_address_then_rate_limit_exceeded(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson($this->url(), $this->payload(['client_report_id' => "burst-report-{$i}"]))->assertCreated();
        }

        $this->postJson($this->url(), $this->payload(['client_report_id' => 'burst-report-x']))
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'RATE_LIMIT_EXCEEDED');

        RateLimiter::clear('app-bug-reports');
    }

    // ── admin ───────────────────────────────────────────────────────────

    public function test_admin_sees_the_report_and_moves_its_status_with_a_note(): void
    {
        $ticket = $this->postJson($this->url(), $this->payload())->assertCreated()->json('data.ticket');
        $report = BugReport::where('ticket', $ticket)->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.bug-reports.index', ['channel' => 'app']))
            ->assertOk()
            ->assertSee($ticket);

        $this->actingAs($admin)->get(route('admin.bug-reports.index', ['search' => $ticket]))
            ->assertOk()
            ->assertSee($ticket);

        $this->actingAs($admin)->get(route('admin.bug-reports.show', $report))
            ->assertOk()
            ->assertSee('รายงานจากแอป')
            ->assertSee('กดส่งข้อความแล้วแอปค้าง')
            ->assertSee('TaskCanceledException')
            ->assertSee('ERROR Chat  timeout');

        $this->actingAs($admin)
            ->post(route('admin.bug-reports.status', $report), ['status' => 'confirmed', 'note' => 'เจอแล้ว เกิดตอน timeout'])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame('confirmed', $report->status);
        $this->assertTrue($report->is_analyzed);
        $this->assertSame($admin->id, $report->analyzed_by);
        $comment = $report->comments()->firstOrFail();
        $this->assertTrue($comment->is_internal);
        $this->assertStringContainsString('ใหม่ → ยืนยันว่าเป็นปัญหา', $comment->comment);
        $this->assertStringContainsString('เจอแล้ว เกิดตอน timeout', $comment->comment);

        $this->actingAs($admin)->post(route('admin.bug-reports.status', $report), ['status' => 'fixed'])->assertRedirect();
        $this->assertTrue($report->fresh()->is_fixed);

        $this->actingAs($admin)
            ->post(route('admin.bug-reports.status', $report), ['status' => 'hacked'])
            ->assertSessionHasErrors('status');
    }

    public function test_customers_cannot_change_a_reports_status(): void
    {
        $report = BugReport::create(['product_name' => 'x', 'title' => 'x', 'description' => 'x']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.bug-reports.status', $report), ['status' => 'closed'])
            ->assertForbidden();

        $this->assertSame('new', $report->fresh()->status);
    }

    public function test_deleting_a_report_removes_its_screenshot_file(): void
    {
        $this->postJson($this->url(), $this->payload(['screenshot' => self::png()]))->assertCreated();
        $report = BugReport::firstOrFail();
        $path = $report->attachments()->firstOrFail()->file_path;

        $this->actingAs($this->admin())->delete(route('admin.bug-reports.destroy', $report))->assertRedirect();

        Storage::disk('local')->assertMissing($path);
        $this->assertSame(0, BugReportAttachment::count());
    }
}
