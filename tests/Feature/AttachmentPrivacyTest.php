<?php

namespace Tests\Feature;

use App\Models\ProjectOrder;
use App\Models\ProjectProgress;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\Attachments;
use App\Support\Auth\Totp;
use App\Support\Auth\TwoFactor;
use App\Support\PrivateFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Files on support tickets and project updates. Customers attach bank slips, ID cards and
 * screenshots of their accounts; these used to be written to the public disk, which the web server
 * hands out at /storage/ticket-attachments/… to anyone holding the address. Now they are kept on the
 * private disk and opened only by the customer they belong to or by an admin.
 */
class AttachmentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(PrivateFiles::DISK);
        Storage::fake(PrivateFiles::LEGACY_DISK);
        Http::preventStrayRequests();
        Mail::fake();
        Cache::flush();
        $this->withoutVite();
    }

    // ── new files go to the private disk ─────────────────────────────

    public function test_a_new_ticket_keeps_its_files_off_the_web_root(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('customer.support.store'), [
            'subject' => 'โอนเงินแล้วแต่ยังไม่ได้ license',
            'category' => SupportTicket::CATEGORY_BILLING,
            'priority' => SupportTicket::PRIORITY_HIGH,
            'message' => 'แนบสลิปและบัตรประชาชนมาให้ครับ',
            'attachments' => [UploadedFile::fake()->image('slip.png', 24, 24), UploadedFile::fake()->create('บัตรประชาชน.pdf', 20, 'application/pdf')],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $ticket = SupportTicket::sole();
        $this->assertCount(2, $ticket->attachments);
        $this->assertSame('บัตรประชาชน.pdf', $ticket->attachments[1]['name']);
        foreach ($ticket->attachments as $attachment) {
            $this->assertMatchesRegularExpression('~^ticket-attachments/\d{4}/\d{2}/[A-Za-z0-9]{40}\.(png|pdf)$~', $attachment['path']);
            Storage::disk('local')->assertExists($attachment['path']);
            $this->assertPublicAddressServesNothing($attachment['path']);
        }
        $this->assertSame([], Storage::disk('public')->allFiles(), 'nothing may land under the web root');

        $this->get(route('customer.support.show', $ticket))->assertOk()
            ->assertSee(route('attachments.ticket', [$ticket, 0]), false)
            ->assertSee(route('attachments.ticket', [$ticket, 1]), false)
            ->assertDontSee('storage/ticket-attachments', false);
        $this->assertServedPrivately($this->get(route('attachments.ticket', [$ticket, 0])), 'image/png');
    }

    public function test_a_ticket_or_reply_without_files_still_goes_through(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('customer.support.store'), [
            'subject' => 'สอบถามการต่ออายุ', 'category' => SupportTicket::CATEGORY_GENERAL,
            'priority' => SupportTicket::PRIORITY_LOW, 'message' => 'ต่ออายุล่วงหน้าได้ไหมครับ',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $ticket = SupportTicket::sole();

        $this->post(route('customer.support.reply', $ticket), ['message' => 'ขอบคุณครับ'])->assertSessionHas('success');

        $this->assertSame([], $ticket->fresh()->attachments);
        $this->assertSame([], $ticket->replies()->sole()->attachments);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_customer_reply_keeps_its_files_off_the_web_root(): void
    {
        $customer = $this->customer();
        $ticket = $this->ticket($customer);

        $this->actingAs($customer)->post(route('customer.support.reply', $ticket), [
            'message' => 'สลิปรอบสองครับ',
            'attachments' => [UploadedFile::fake()->image('slip-2.jpg', 24, 24)],
        ])->assertSessionHas('success');

        $reply = TicketReply::sole();
        Storage::disk('local')->assertExists($reply->attachments[0]['path']);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->get(route('customer.support.show', $ticket))->assertOk()
            ->assertSee(route('attachments.ticket-reply', [$reply, 0]), false)
            ->assertDontSee('storage/ticket-attachments', false);
        $this->assertServedPrivately($this->get(route('attachments.ticket-reply', [$reply, 0])), 'image/jpeg');
    }

    public function test_an_admin_note_keeps_its_files_off_the_web_root_and_away_from_the_customer(): void
    {
        $customer = $this->customer();
        $ticket = $this->ticket($customer);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.support.reply', $ticket), [
            'message' => 'ตรวจกับธนาคารแล้ว ยอดเข้าจริง',
            'is_internal' => 1,
            'attachments' => [UploadedFile::fake()->image('statement.png', 24, 24)],
        ])->assertSessionHas('success');

        $note = TicketReply::sole();
        $this->assertTrue($note->is_internal);
        Storage::disk('local')->assertExists($note->attachments[0]['path']);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $url = route('attachments.ticket-reply', [$note, 0]);
        $this->get(route('admin.support.show', $ticket))->assertOk()
            ->assertSee($url, false)
            ->assertDontSee('storage/ticket-attachments', false);
        $this->assertServedPrivately($this->get($url), 'image/png');

        $this->actingAs($customer)->get(route('customer.support.show', $ticket))->assertOk()->assertDontSee($url, false);
        $this->get($url)->assertNotFound();
    }

    public function test_a_project_update_keeps_its_files_off_the_web_root(): void
    {
        $customer = $this->customer();
        $project = $this->project($customer);

        $this->actingAs($this->admin())->post(route('admin.projects.progress.store', $project), [
            'title' => 'ส่งมอบงานออกแบบรอบแรก', 'description' => 'แบบหน้าแรกและหน้าสินค้า', 'type' => 'delivery',
            'is_public' => 1, 'notify_customer' => 0,
            'attachments' => [UploadedFile::fake()->image('mockup.png', 24, 24)],
        ])->assertSessionHas('success');

        $progress = ProjectProgress::sole();
        $path = $progress->attachments[0]['path'];
        $this->assertMatchesRegularExpression('~^project-attachments/' . $project->id . '/[A-Za-z0-9]{40}\.png$~', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertPublicAddressServesNothing($path);

        $url = route('attachments.project-progress', [$progress, 0]);
        $this->get(route('admin.projects.show', $project))->assertOk()->assertSee($url, false);
        $this->actingAs($customer)->get(route('customer.projects.show', $project))->assertOk()
            ->assertSee($url, false)
            ->assertSee('mockup.png')
            ->assertDontSee('storage/project-attachments', false);
        $this->assertServedPrivately($this->get($url), 'image/png');
    }

    // ── who may open one ─────────────────────────────────────────────

    public function test_only_the_ticket_owner_and_an_admin_open_a_ticket_file(): void
    {
        $owner = $this->customer();
        $ticket = $this->ticket($owner, [$this->entry($this->privateFile('ticket-attachments/2026/10/slip.png'))]);
        $url = route('attachments.ticket', [$ticket, 0]);

        $this->get($url)->assertNotFound();
        $this->actingAs($this->customer())->get($url)->assertNotFound();

        $mine = $this->actingAs($owner)->get($url);
        $this->assertServedPrivately($mine, 'image/png');
        $this->assertSame(Storage::disk('local')->get('ticket-attachments/2026/10/slip.png'), $mine->streamedContent());

        $this->assertServedPrivately($this->actingAs($this->admin())->get($url), 'image/png');
    }

    public function test_a_reply_file_opens_for_the_ticket_owner_but_an_internal_note_only_for_an_admin(): void
    {
        $owner = $this->customer();
        $ticket = $this->ticket($owner);
        $staff = $this->admin();
        $reply = $ticket->addReply($staff, 'ใบเสร็จตามแนบครับ', [$this->entry($this->privateFile('ticket-attachments/2026/10/receipt.png'))]);
        $note = $ticket->addReply($staff, 'บัญชีลูกค้าตามแนบ', [$this->entry($this->privateFile('ticket-attachments/2026/10/note.png'))], true);
        $replyUrl = route('attachments.ticket-reply', [$reply, 0]);
        $noteUrl = route('attachments.ticket-reply', [$note, 0]);

        $this->get($replyUrl)->assertNotFound();
        $this->actingAs($this->customer())->get($replyUrl)->assertNotFound();
        $this->assertServedPrivately($this->actingAs($owner)->get($replyUrl), 'image/png');
        $this->actingAs($owner)->get($noteUrl)->assertNotFound();

        $this->assertServedPrivately($this->actingAs($this->admin())->get($replyUrl), 'image/png');
        $this->assertServedPrivately($this->actingAs($this->admin())->get($noteUrl), 'image/png');
    }

    public function test_a_project_file_opens_for_its_customer_only_on_an_update_shown_to_them(): void
    {
        $owner = $this->customer();
        $project = $this->project($owner);
        $shown = $this->progress($project, [$this->entry($this->privateFile("project-attachments/{$project->id}/mockup.png"))]);
        $hidden = $this->progress($project, [$this->entry($this->privateFile("project-attachments/{$project->id}/costs.png"))], false);
        $shownUrl = route('attachments.project-progress', [$shown, 0]);
        $hiddenUrl = route('attachments.project-progress', [$hidden, 0]);

        $this->get($shownUrl)->assertNotFound();
        $this->actingAs($this->customer())->get($shownUrl)->assertNotFound();
        $this->assertServedPrivately($this->actingAs($owner)->get($shownUrl), 'image/png');
        $this->actingAs($owner)->get($hiddenUrl)->assertNotFound();

        $this->assertServedPrivately($this->actingAs($this->admin())->get($shownUrl), 'image/png');
        $this->assertServedPrivately($this->actingAs($this->admin())->get($hiddenUrl), 'image/png');
    }

    public function test_an_admin_opens_files_only_after_the_second_sign_in_step(): void
    {
        config(['security.two_factor.required_for_admins' => true]);
        $ticket = $this->ticket($this->customer(), [$this->entry($this->privateFile('ticket-attachments/2026/10/slip.png'))]);
        $progress = $this->progress($this->project($this->customer()), [$this->entry($this->privateFile('project-attachments/1/mockup.png'))]);
        $urls = [route('attachments.ticket', [$ticket, 0]), route('attachments.project-progress', [$progress, 0])];

        // Not enrolled while the panel requires it: the panel itself would send this admin to set it up
        foreach ($urls as $url) {
            $this->actingAs($this->admin())->get($url)->assertNotFound();
        }

        $admin = $this->admin();
        $secret = Totp::generateSecret();
        [, $hashes] = TwoFactor::newRecoveryCodes();
        $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => $hashes, 'two_factor_confirmed_at' => now()])->save();

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertNotFound();
        }
        $this->post(route('two-factor.verify'), ['code' => Totp::at($secret, Totp::stepAt(time()))]);
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_a_file_that_is_not_there_answers_like_a_stranger(): void
    {
        $owner = $this->customer();
        $ticket = $this->ticket($owner, [$this->entry($this->privateFile('ticket-attachments/2026/10/slip.png')), $this->entry('ticket-attachments/2026/10/gone.png')]);
        $this->actingAs($owner);

        $this->get(route('attachments.ticket', [$ticket, 1]))->assertNotFound();
        $this->get(route('attachments.ticket', [$ticket, 2]))->assertNotFound();
        $this->get('/attachments/tickets/' . $ticket->id . '/-1')->assertNotFound();
        $this->get('/attachments/tickets/' . $ticket->id . '/9999')->assertNotFound();
        $this->get('/attachments/tickets/' . ($ticket->id + 100) . '/0')->assertNotFound();
        $this->get(route('attachments.ticket', [$this->ticket($owner), 0]))->assertNotFound();
    }

    // ── what a row may point at ──────────────────────────────────────

    /** @return array<string, array{0: ?string, 1: string, 2: ?string}> */
    public static function storedValues(): array
    {
        return [
            'a ticket file' => ['ticket-attachments/2026/10/a.png', Attachments::TICKETS, 'ticket-attachments/2026/10/a.png'],
            'a project file' => ['project-attachments/12/b.pdf', Attachments::PROJECTS, 'project-attachments/12/b.pdf'],
            'the public address' => ['/storage/ticket-attachments/2026/10/a.png', Attachments::TICKETS, 'ticket-attachments/2026/10/a.png'],
            'a full URL' => ['https://xman4289.com/storage/project-attachments/12/b.pdf', Attachments::PROJECTS, 'project-attachments/12/b.pdf'],
            'nothing' => [null, Attachments::TICKETS, null],
            'the folder itself' => ['ticket-attachments/', Attachments::TICKETS, null],
            'climbing out' => ['ticket-attachments/../kyc/front.png', Attachments::TICKETS, null],
            'climbing out deeper' => ['ticket-attachments/2026/10/../../../payment-slips/a.png', Attachments::TICKETS, null],
            'the other folder' => ['project-attachments/12/b.pdf', Attachments::TICKETS, null],
            'a payment slip' => ['payment-slips/a.png', Attachments::TICKETS, null],
            'a hidden file' => ['ticket-attachments/.htaccess', Attachments::TICKETS, null],
            'a backslash' => ['ticket-attachments\\2026\\a.png', Attachments::TICKETS, null],
            'a look-alike folder' => ['ticket-attachments-old/a.png', Attachments::TICKETS, null],
        ];
    }

    /** @dataProvider storedValues */
    public function test_a_stored_value_reads_as_a_path_inside_its_folder_or_not_at_all(?string $stored, string $directory, ?string $expected): void
    {
        $this->assertSame($expected, PrivateFiles::normalize($stored, $directory));
    }

    public function test_a_row_cannot_point_the_attachment_route_at_another_private_file(): void
    {
        $owner = $this->customer();
        Storage::disk('local')->put('kyc/front.png', $this->png());
        $this->privateFile('payment-slips/slip.png');
        $this->privateFile('project-attachments/1/mockup.png');
        $this->privateFile('ticket-attachments/2026/10/slip.png');

        $this->actingAs($owner);
        foreach (['kyc/front.png', 'ticket-attachments/../kyc/front.png', '/storage/../kyc/front.png', 'ticket-attachments/2026/10/../../../kyc/front.png',
            'payment-slips/slip.png', 'project-attachments/1/mockup.png'] as $stored) {
            $ticket = $this->ticket($owner, [$this->entry($stored)]);
            $this->get(route('attachments.ticket', [$ticket, 0]))->assertNotFound();
        }

        // Nor a project update at a ticket's file, even for an admin
        $progress = $this->progress($this->project($owner), [$this->entry('ticket-attachments/2026/10/slip.png')]);
        $this->actingAs($this->admin())->get(route('attachments.project-progress', [$progress, 0]))->assertNotFound();
    }

    public function test_a_file_the_move_has_not_reached_yet_still_opens(): void
    {
        $owner = $this->customer();
        Storage::disk('public')->put('ticket-attachments/2025/12/old.png', $this->png());
        Storage::disk('public')->put('project-attachments/7/old.png', $this->png());
        $this->actingAs($owner);

        foreach (['ticket-attachments/2025/12/old.png', '/storage/ticket-attachments/2025/12/old.png', 'https://xman4289.com/storage/ticket-attachments/2025/12/old.png'] as $stored) {
            $ticket = $this->ticket($owner, [$this->entry($stored)]);
            $this->assertServedPrivately($this->get(route('attachments.ticket', [$ticket, 0])), 'image/png');
        }

        $progress = $this->progress($this->project($owner), [['path' => 'project-attachments/7/old.png', 'name' => 'old.png', 'size' => 10]]);
        $this->assertServedPrivately($this->get(route('attachments.project-progress', [$progress, 0])), 'image/png');
    }

    public function test_a_listed_file_without_a_name_still_shows_and_opens(): void
    {
        $owner = $this->customer();
        $ticket = $this->ticket($owner, [['path' => $this->privateFile('ticket-attachments/2026/10/slip.png')]]);

        $this->actingAs($owner)->get(route('customer.support.show', $ticket))->assertOk()->assertSee('ไฟล์แนบ 1');
        $response = $this->get(route('attachments.ticket', [$ticket, 0]));

        $this->assertServedPrivately($response, 'image/png');
        $this->assertStringContainsString('filename=attachment-1.png', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_a_file_that_is_not_a_raster_image_is_only_offered_as_a_download_under_its_own_name(): void
    {
        $owner = $this->customer();
        Storage::disk('local')->put('ticket-attachments/2026/10/abc.pdf', "%PDF-1.4\n%%EOF\n");
        // An SVG or HTML page left from before: opened in a tab it would run script as this site
        Storage::disk('public')->put('ticket-attachments/2025/12/old.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $ticket = $this->ticket($owner, [
            $this->entry('ticket-attachments/2026/10/abc.pdf', 'สลิป โอนเงิน.pdf'),
            $this->entry('ticket-attachments/2025/12/old.svg', 'logo.svg'),
        ]);
        $this->actingAs($owner);

        $pdf = $this->get(route('attachments.ticket', [$ticket, 0]));
        $this->assertServedPrivately($pdf, 'application/octet-stream');
        $disposition = (string) $pdf->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment', $disposition);
        $this->assertStringContainsString("filename*=utf-8''" . rawurlencode('สลิป-โอนเงิน.pdf'), $disposition);

        $svg = $this->get(route('attachments.ticket', [$ticket, 1]));
        $this->assertServedPrivately($svg, 'application/octet-stream');
        $this->assertStringStartsWith('attachment', (string) $svg->headers->get('Content-Disposition'));
    }

    // ── moving the files already on the public disk ──────────────────

    public function test_the_move_takes_every_public_attachment_and_nothing_else(): void
    {
        Storage::disk('public')->put('ticket-attachments/2026/01/a.png', 'file a');
        Storage::disk('public')->put('project-attachments/3/b.pdf', 'file b');
        Storage::disk('public')->put('avatars/someone.png', 'an avatar');
        $ticket = $this->ticket($this->customer(), [$this->entry('ticket-attachments/2026/01/a.png')]);
        Log::spy();

        $this->artisan('attachments:privatize')
            ->expectsOutputToContain('moved project-attachments/3/b.pdf')
            ->expectsOutputToContain('Moved 2 attachment(s) to the private disk and rewrote 0 row(s); 0 problem(s).')
            ->assertSuccessful();

        $this->assertSame('file a', Storage::disk('local')->get('ticket-attachments/2026/01/a.png'));
        $this->assertSame('file b', Storage::disk('local')->get('project-attachments/3/b.pdf'));
        $this->assertSame(['avatars/someone.png'], Storage::disk('public')->allFiles(), 'other public files stay');
        $this->assertEqualsCanonicalizing(['ticket-attachments/2026/01/a.png', 'project-attachments/3/b.pdf'], Storage::disk('local')->allFiles(), 'no temporary copies left');
        $this->assertSame('ticket-attachments/2026/01/a.png', $ticket->fresh()->attachments[0]['path'], 'the row already lists the right path');

        $this->artisan('attachments:privatize')
            ->expectsOutputToContain('Moved 0 attachment(s) to the private disk and rewrote 0 row(s); 0 problem(s).')
            ->assertSuccessful();

        // Logged once, with the old addresses a CDN may still hold; a run with nothing to do stays quiet
        Log::shouldHaveReceived('log')->once()->withArgs(fn (string $level, string $message, array $context) => $level === 'info'
            && $context['moved'] === 2 && $context['paths'] === ['ticket-attachments/2026/01/a.png', 'project-attachments/3/b.pdf']);
    }

    public function test_the_move_finishes_an_interrupted_copy_and_never_settles_a_clash(): void
    {
        Storage::disk('public')->put('ticket-attachments/2026/01/same.png', 'same bytes');
        Storage::disk('local')->put('ticket-attachments/2026/01/same.png', 'same bytes');
        Storage::disk('public')->put('project-attachments/3/clash.pdf', 'public bytes');
        Storage::disk('local')->put('project-attachments/3/clash.pdf', 'private bytes');

        $this->artisan('attachments:privatize')
            ->expectsOutputToContain('project-attachments/3/clash.pdf: the private disk holds a different file under this name; both left alone.')
            ->assertFailed();

        Storage::disk('public')->assertMissing('ticket-attachments/2026/01/same.png');
        $this->assertSame('same bytes', Storage::disk('local')->get('ticket-attachments/2026/01/same.png'));
        $this->assertSame('public bytes', Storage::disk('public')->get('project-attachments/3/clash.pdf'));
        $this->assertSame('private bytes', Storage::disk('local')->get('project-attachments/3/clash.pdf'));
    }

    public function test_the_move_rewrites_rows_that_listed_the_public_address(): void
    {
        $owner = $this->customer();
        $ticket = $this->ticket($owner, [$this->entry('ticket-attachments/2026/01/ok.png'), $this->entry('/storage/ticket-attachments/2026/01/a.png')]);
        $reply = $ticket->addReply($owner, 'ส่งเพิ่มครับ', [$this->entry('https://xman4289.com/storage/ticket-attachments/2026/01/b.png', 'ใบเสร็จ.png')]);
        $progress = $this->progress($this->project($owner), [['path' => 'storage/project-attachments/3/c.pdf', 'name' => 'c.pdf', 'size' => 10]]);
        // MySQL may reorder the keys of a JSON object, hence assertEquals below

        $this->artisan('attachments:privatize')
            ->expectsOutputToContain('rewrote 3 row(s); 0 problem(s).')
            ->assertSuccessful();

        $this->assertSame(['ticket-attachments/2026/01/ok.png', 'ticket-attachments/2026/01/a.png'], array_column($ticket->fresh()->attachments, 'path'));
        $this->assertEquals([$this->entry('ticket-attachments/2026/01/b.png', 'ใบเสร็จ.png')], $reply->fresh()->attachments, 'only the path changes');
        $this->assertEquals([['path' => 'project-attachments/3/c.pdf', 'name' => 'c.pdf', 'size' => 10]], $progress->fresh()->attachments);
    }

    public function test_a_row_that_lists_no_attachment_path_is_reported_and_left_alone(): void
    {
        $ticket = $this->ticket($this->customer(), [$this->entry('/storage/ticket-attachments/2026/01/a.png'), $this->entry('C:\\Users\\someone\\slip.png')]);
        $progress = $this->progress($this->project($this->customer()), [$this->entry('ticket-attachments/2026/01/a.png')]);

        $this->artisan('attachments:privatize')
            ->expectsOutputToContain("support_tickets #{$ticket->id}: attachment 1 is not a path in ticket-attachments/; left as it is.")
            ->expectsOutputToContain("project_progress #{$progress->id}: attachment 0 is not a path in project-attachments/; left as it is.")
            ->assertFailed();

        $this->assertSame(['ticket-attachments/2026/01/a.png', 'C:\\Users\\someone\\slip.png'], array_column($ticket->fresh()->attachments, 'path'), 'the good entry is still fixed');
        $this->assertSame('ticket-attachments/2026/01/a.png', $progress->fresh()->attachments[0]['path']);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        Storage::disk('public')->put('ticket-attachments/2026/01/a.png', 'file a');
        $ticket = $this->ticket($this->customer(), [$this->entry('/storage/ticket-attachments/2026/01/a.png')]);

        $this->artisan('attachments:privatize', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run: would move 1 attachment(s) to the private disk and rewrite 1 row(s); 0 problem(s).')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('ticket-attachments/2026/01/a.png');
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame('/storage/ticket-attachments/2026/01/a.png', $ticket->fresh()->attachments[0]['path']);
    }

    public function test_the_deploy_migration_moves_the_attachments(): void
    {
        Storage::disk('public')->put('ticket-attachments/2026/01/a.png', 'file a');
        Storage::disk('public')->put('project-attachments/3/b.pdf', 'file b');

        (require database_path('migrations/2026_10_22_120000_move_ticket_and_project_attachments_to_the_private_disk.php'))->up();

        $this->assertSame('file a', Storage::disk('local')->get('ticket-attachments/2026/01/a.png'));
        $this->assertSame('file b', Storage::disk('local')->get('project-attachments/3/b.pdf'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function customer(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function ticket(User $owner, array $attachments = []): SupportTicket
    {
        return SupportTicket::create([
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'subject' => 'โอนเงินแล้วแต่ยังไม่ได้ license',
            'message' => 'แนบสลิปมาให้ครับ',
            'category' => SupportTicket::CATEGORY_BILLING,
            'priority' => SupportTicket::PRIORITY_MEDIUM,
            'status' => SupportTicket::STATUS_OPEN,
            'attachments' => $attachments,
        ]);
    }

    private function project(User $owner): ProjectOrder
    {
        return ProjectOrder::create(['user_id' => $owner->id, 'project_name' => 'เว็บร้านค้าออนไลน์', 'project_type' => 'web']);
    }

    private function progress(ProjectOrder $project, array $attachments, bool $public = true): ProjectProgress
    {
        return $project->progress()->create([
            'title' => 'ส่งมอบงานรอบแรก', 'description' => 'ไฟล์ออกแบบตามแนบ', 'type' => 'delivery',
            'is_public' => $public, 'attachments' => $attachments,
        ]);
    }

    /** @return array{name: string, path: string, size: int, type: string} */
    private function entry(string $path, string $name = 'slip.png'): array
    {
        return ['name' => $name, 'path' => $path, 'size' => 100, 'type' => 'image/png'];
    }

    private function privateFile(string $path): string
    {
        Storage::disk('local')->put($path, $this->png());

        return $path;
    }

    private function png(): string
    {
        return UploadedFile::fake()->image('slip.png', 24, 24)->getContent();
    }

    private function assertServedPrivately(TestResponse $response, string $type): void
    {
        $response->assertOk()
            ->assertHeader('Content-Type', $type)
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cache = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cache);
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('sandbox', (string) $response->headers->get('Content-Security-Policy'));
    }

    /** The address the web server used to hand out; Laravel's own /storage route wants a signature. */
    private function assertPublicAddressServesNothing(string $path): void
    {
        $this->assertContains($this->get('/storage/' . $path)->getStatusCode(), [403, 404]);
    }
}
