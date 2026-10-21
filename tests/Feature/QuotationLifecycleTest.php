<?php

namespace Tests\Feature;

use App\Mail\QuotationReminderMail;
use App\Models\ProjectInvoice;
use App\Models\ProjectOrder;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\User;
use App\Services\QuotationAcceptance;
use App\Support\Quotation\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * What happens to a quotation AFTER it is sent: chased, revised, accepted,
 * invoiced — and the statuses it is allowed to hold while that happens.
 *
 * The status test is the one that matters most. markAsDeclined() wrote
 * 'declined', a value the MySQL enum has never accepted, and SQLite stored it
 * without complaint — so the whole suite was green while the live "ไม่รับข้อเสนอ"
 * button would have thrown. Every status this code can write is now asserted
 * against the column's own list.
 */
class QuotationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * A quotation straight to the database — these tests are about what happens
     * after sending, not about the builder.
     */
    private function quotation(array $overrides = []): Quotation
    {
        return Quotation::create(array_merge([
            'quote_number' => 'QT-' . now()->format('Ymd') . '-TEST',
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'somchai@example.co.th',
            'customer_phone' => '081-234-5678',
            'service_type' => 'web',
            'service_name' => 'เว็บไซต์องค์กร',
            'service_options' => [['name' => 'หน้าแรก', 'price' => 100000]],
            'additional_options' => [['name' => 'ดูแลรายเดือน', 'price' => 20000]],
            'subtotal' => 120000,
            'vat' => 8400,
            'grand_total' => 128400,
            'status' => 'sent',
            'sent_at' => now()->subDays(10),
            'valid_until' => now()->addDays(20),
            'vat_mode' => 'exclusive',
            'vat_rate' => 7,
            'amount_before_vat' => 120000,
            'public_token' => Quotation::newPublicToken(),
            'version' => 1,
        ], $overrides));
    }

    // ───────────────────────────────────────── statuses the column accepts

    public function test_every_status_the_code_writes_is_one_the_column_accepts(): void
    {
        $quotation = $this->quotation();

        $quotation->markAsSent();
        $this->assertContains($quotation->fresh()->status, Quotation::STATUSES);

        $quotation->markAsAccepted();
        $this->assertContains($quotation->fresh()->status, Quotation::STATUSES);

        $quotation->markAsPaid();
        $this->assertContains($quotation->fresh()->status, Quotation::STATUSES);

        $other = $this->quotation(['quote_number' => 'QT-20260919-DECL']);
        $other->markAsDeclined('แพงเกินงบครับ');

        // 'declined' is NOT in the enum. This is the bug that shipped.
        $this->assertContains($other->fresh()->status, Quotation::STATUSES);
        $this->assertSame('rejected', $other->fresh()->status);
        $this->assertNotNull($other->fresh()->declined_at, 'the answer needs its own timestamp');
        $this->assertStringContainsString('แพงเกิน', $other->fresh()->customer_notes);
    }

    public function test_the_customer_can_actually_decline_from_the_document_page(): void
    {
        $quotation = $this->quotation();

        $this->post('/quote/d/' . $quotation->public_token . '/respond', [
            'action' => 'decline',
            'message' => 'เลื่อนโครงการออกไปก่อน',
        ])->assertSessionHas('quote_success');

        $fresh = $quotation->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertContains($fresh->status, Quotation::STATUSES);
        $this->assertNotNull($fresh->declined_at);
    }

    public function test_the_admin_status_dropdown_only_accepts_known_statuses(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.update-status', $quotation), ['status' => 'declined'])
            ->assertSessionHasErrors('status');

        $this->assertSame('sent', $quotation->fresh()->status);
    }

    // ───────────────────────────────────────── revisions

    public function test_a_revision_keeps_the_number_and_bumps_the_version(): void
    {
        $original = $this->quotation();
        $revision = $original->createRevision('ลูกค้าขอตัดงานออกแบบออก');

        $this->assertSame($original->quote_number, $revision->quote_number, 'the job keeps its number');
        $this->assertSame(2, $revision->version);
        $this->assertSame('draft', $revision->status);
        $this->assertNull($revision->sent_at);
        $this->assertNotSame($original->public_token, $revision->public_token, 'a new document needs a new link');
        $this->assertSame($original->id, $revision->revision_of);
        $this->assertSame('ลูกค้าขอตัดงานออกแบบออก', $revision->revision_note);

        $this->assertNotNull($original->fresh()->superseded_at);
        $this->assertSame('QT-' . now()->format('Ymd') . '-TEST Rev.2', $revision->displayNumber());
        $this->assertSame('QT-' . now()->format('Ymd') . '-TEST', $original->displayNumber(), 'v1 prints as a plain number');
    }

    public function test_a_third_version_counts_from_the_highest_not_from_its_parent(): void
    {
        $original = $this->quotation();
        $second = $original->createRevision();
        $third = $second->createRevision();

        $this->assertSame(3, $third->version);
        $this->assertSame($original->id, $third->revision_of, 'the whole chain points at the original');
        $this->assertCount(3, $third->versions()->get());
    }

    public function test_a_superseded_quotation_can_no_longer_be_accepted(): void
    {
        $original = $this->quotation();
        $original->createRevision();

        $this->post('/quote/d/' . $original->public_token . '/respond', ['action' => 'accept'])
            ->assertSessionHas('quote_error');

        $this->assertNotSame('accepted', $original->fresh()->status);
        $this->assertFalse($original->fresh()->awaitingResponse());
    }

    public function test_the_old_link_still_opens_and_says_a_new_version_exists(): void
    {
        $original = $this->quotation();
        $original->createRevision();

        $this->get('/quote/d/' . $original->public_token)
            ->assertOk()
            ->assertSee('มีฉบับแก้ไขใหม่แทนแล้ว', false);
    }

    public function test_an_accepted_quotation_cannot_be_revised(): void
    {
        $quotation = $this->quotation(['status' => 'accepted', 'accepted_at' => now()]);

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.revise', $quotation))
            ->assertSessionHas('error');

        $this->assertSame(1, Quotation::where('quote_number', $quotation->quote_number)->count());
    }

    public function test_the_admin_can_revise_and_lands_on_the_new_version(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.revise', $quotation), ['revision_note' => 'ปรับราคาใหม่'])
            ->assertSessionHas('success');

        $revision = Quotation::where('quote_number', $quotation->quote_number)->where('version', 2)->first();
        $this->assertNotNull($revision, 'two versions share one number');
        $this->assertSame('ปรับราคาใหม่', $revision->revision_note);
    }

    // ───────────────────────────────────────── follow-up

    public function test_a_quiet_quotation_about_to_expire_is_due_a_reminder(): void
    {
        $this->quotation(['valid_until' => now()->addDays(2)]);

        $this->assertCount(1, Quotation::needsFollowUp(3)->get());
    }

    public function test_quotations_that_must_not_be_chased_are_left_alone(): void
    {
        // Already reminded.
        $this->quotation(['quote_number' => 'QT-A', 'valid_until' => now()->addDay(), 'follow_up_sent_at' => now()]);
        // Answered.
        $this->quotation(['quote_number' => 'QT-B', 'valid_until' => now()->addDay(), 'status' => 'accepted']);
        // Never sent.
        $this->quotation(['quote_number' => 'QT-C', 'valid_until' => now()->addDay(), 'status' => 'draft', 'sent_at' => null]);
        // Already expired — a "3 days left" e-mail a week late is worse than none.
        $this->quotation(['quote_number' => 'QT-D', 'valid_until' => now()->subDays(2)]);
        // Superseded by a newer version.
        $superseded = $this->quotation(['quote_number' => 'QT-E', 'valid_until' => now()->addDay()]);
        $superseded->update(['superseded_at' => now()]);
        // Still far from expiring.
        $this->quotation(['quote_number' => 'QT-F', 'valid_until' => now()->addDays(20)]);

        $this->assertCount(0, Quotation::needsFollowUp(3)->get());
    }

    public function test_the_follow_up_command_sends_one_reminder_and_only_one(): void
    {
        Mail::fake();
        $quotation = $this->quotation(['valid_until' => now()->addDays(2)]);

        $this->artisan('quotations:follow-up')->assertSuccessful();

        Mail::assertSent(QuotationReminderMail::class, 1);
        Mail::assertSent(QuotationReminderMail::class, fn ($mail) => $mail->hasTo('somchai@example.co.th'));
        $this->assertNotNull($quotation->fresh()->follow_up_sent_at);

        // Second run the same day must be silent.
        $this->artisan('quotations:follow-up')->assertSuccessful();
        Mail::assertSent(QuotationReminderMail::class, 1);
    }

    public function test_a_dry_run_sends_nothing_and_leaves_the_lock_open(): void
    {
        Mail::fake();
        $quotation = $this->quotation(['valid_until' => now()->addDays(2)]);

        $this->artisan('quotations:follow-up --dry-run')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($quotation->fresh()->follow_up_sent_at, 'a dry run must not consume the one reminder');
    }

    public function test_an_old_quotation_without_a_link_gets_one_before_being_chased(): void
    {
        Mail::fake();
        $quotation = $this->quotation(['valid_until' => now()->addDays(2)]);
        $quotation->update(['public_token' => null]);

        $this->artisan('quotations:follow-up')->assertSuccessful();

        $this->assertNotNull($quotation->fresh()->public_token, 'a reminder with no link is a dead end');
        Mail::assertSent(QuotationReminderMail::class, fn ($mail) => $mail->publicUrl !== null);
    }

    public function test_a_failed_reminder_stays_owed(): void
    {
        // A mailer that throws must not burn the one reminder the customer gets.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $quotation = $this->quotation(['valid_until' => now()->addDays(2)]);

        $this->artisan('quotations:follow-up')->assertSuccessful();

        $this->assertNull($quotation->fresh()->follow_up_sent_at);
    }

    public function test_the_admin_list_can_show_what_needs_chasing(): void
    {
        $this->quotation(['sent_at' => now()->subDays(12)]);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.list', ['follow_up' => 1]))
            ->assertOk()
            ->assertSee('เงียบมาแล้ว', false)
            ->assertSee('12 วัน', false);
    }

    // ───────────────────────────────────────── accept opens the job

    public function test_accepting_from_the_document_creates_the_project_and_the_instalments(): void
    {
        $quotation = $this->quotation();

        $this->post('/quote/d/' . $quotation->public_token . '/respond', ['action' => 'accept'])
            ->assertSessionHas('quote_success');

        $project = ProjectOrder::where('quotation_id', $quotation->id)->first();
        $this->assertNotNull($project, 'the customer pressing accept is what opens the job');
        $this->assertSame('เว็บไซต์องค์กร', $project->project_name);
        $this->assertCount(2, $project->features, 'each thing they picked becomes a feature');

        $invoices = $project->invoices;
        $this->assertCount(3, $invoices, 'the default plan is three instalments');

        // The instalments must add up to the quotation exactly — a satang short
        // means an invoice nobody can reconcile.
        $this->assertSame(
            round((float) $quotation->grand_total, 2),
            round((float) $invoices->sum('amount'), 2)
        );

        $first = $invoices->firstWhere('installment_no', 1);
        $this->assertSame(ProjectInvoice::ISSUED, $first->status);
        $this->assertNotNull($first->due_date);
        $this->assertNotNull($first->issued_at);

        $rest = $invoices->where('installment_no', '>', 1);
        foreach ($rest as $later) {
            $this->assertSame(ProjectInvoice::SCHEDULED, $later->status, 'billing everything on day one is wrong');
            $this->assertNull($later->due_date);
        }
    }

    public function test_accepting_twice_does_not_produce_a_second_set_of_paperwork(): void
    {
        $quotation = $this->quotation();
        $acceptance = app(QuotationAcceptance::class);

        $acceptance->projectFor($quotation);
        $acceptance->projectFor($quotation);

        $this->assertSame(1, ProjectOrder::where('quotation_id', $quotation->id)->count());
        $this->assertSame(3, ProjectInvoice::where('quotation_id', $quotation->id)->count());
    }

    public function test_the_admin_accepting_produces_the_same_paperwork(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.update-status', $quotation), ['status' => 'accepted'])
            ->assertSessionHas('success');

        $project = ProjectOrder::where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertCount(3, $project->invoices);
        $this->assertSame(ProjectInvoice::ISSUED, $project->invoices->firstWhere('installment_no', 1)->status);
    }

    public function test_a_project_created_before_instalments_existed_gets_them_backfilled(): void
    {
        $quotation = $this->quotation();
        $project = ProjectOrder::create([
            'quotation_id' => $quotation->id,
            'project_name' => 'งานเก่า',
            'project_type' => 'web',
            'total_price' => $quotation->grand_total,
        ]);

        app(QuotationAcceptance::class)->projectFor($quotation);

        $this->assertCount(3, $project->fresh()->invoices);
    }

    // ───────────────────────────────────────── invoices

    public function test_marking_instalments_paid_moves_the_project_money(): void
    {
        $quotation = $this->quotation();
        $acceptance = app(QuotationAcceptance::class);
        $project = $acceptance->projectFor($quotation);

        $acceptance->syncPayment($project);
        $this->assertSame('unpaid', $project->fresh()->payment_status);

        $project->invoices->firstWhere('installment_no', 1)->markAsPaid('โอนผ่าน SCB');
        $acceptance->syncPayment($project->fresh());
        $this->assertSame('partial', $project->fresh()->payment_status);

        foreach ($project->fresh()->invoices as $invoice) {
            $invoice->markAsPaid();
        }
        $acceptance->syncPayment($project->fresh());

        $fresh = $project->fresh();
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertSame(round((float) $quotation->grand_total, 2), round((float) $fresh->paid_amount, 2));
    }

    public function test_a_voided_instalment_does_not_keep_a_job_looking_unpaid(): void
    {
        $quotation = $this->quotation();
        $acceptance = app(QuotationAcceptance::class);
        $project = $acceptance->projectFor($quotation);

        $invoices = $project->invoices;
        $invoices->firstWhere('installment_no', 1)->markAsPaid();
        $invoices->firstWhere('installment_no', 2)->markAsPaid();
        $invoices->firstWhere('installment_no', 3)->update(['status' => ProjectInvoice::VOID]);

        $acceptance->syncPayment($project->fresh());

        $this->assertSame('paid', $project->fresh()->payment_status);
    }

    public function test_paying_is_idempotent(): void
    {
        $quotation = $this->quotation();
        $invoice = app(QuotationAcceptance::class)->projectFor($quotation)->invoices->first();

        $invoice->markAsPaid('ครั้งแรก');
        $paidAt = $invoice->fresh()->paid_at;

        $invoice->fresh()->markAsPaid('กดซ้ำ');

        $this->assertEquals($paidAt, $invoice->fresh()->paid_at, 'a second click must not move the payment date');
        $this->assertSame('ครั้งแรก', $invoice->fresh()->paid_note);
    }

    public function test_issuing_twice_does_not_push_the_due_date_out(): void
    {
        $quotation = $this->quotation();
        $invoice = app(QuotationAcceptance::class)->projectFor($quotation)
            ->invoices->firstWhere('installment_no', 2);

        $invoice->issue(7);
        $due = $invoice->fresh()->due_date;

        $this->travel(3)->days();
        $invoice->fresh()->issue(7);

        $this->assertEquals($due, $invoice->fresh()->due_date);
    }

    public function test_an_overdue_instalment_is_only_one_that_was_actually_billed(): void
    {
        $quotation = $this->quotation();
        $invoices = app(QuotationAcceptance::class)->projectFor($quotation)->invoices;

        $scheduled = $invoices->firstWhere('installment_no', 2);
        $this->assertFalse($scheduled->isOverdue(), 'nobody has asked for this one yet');

        $issued = $invoices->firstWhere('installment_no', 1);
        $issued->update(['due_date' => now()->subDays(2)]);
        $this->assertTrue($issued->fresh()->isOverdue());

        $issued->markAsPaid();
        $this->assertFalse($issued->fresh()->isOverdue(), 'paid is not overdue');
    }

    public function test_the_customer_can_open_and_print_an_invoice_from_its_link(): void
    {
        $quotation = $this->quotation();
        $invoice = app(QuotationAcceptance::class)->projectFor($quotation)->invoices->first();

        $this->get(route('invoice.show', $invoice->public_token))
            ->assertOk()
            ->assertSee($invoice->invoice_number, false)
            ->assertSee('noindex', false);

        $pdf = $this->get(route('invoice.download', $invoice->public_token));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_an_invoice_link_cannot_be_guessed(): void
    {
        $this->get('/invoice/not-a-token')->assertNotFound();
        $this->get('/invoice/' . str_repeat('a', 64))->assertNotFound();
    }

    public function test_the_invoice_token_never_rides_along_in_json(): void
    {
        $quotation = $this->quotation();
        $invoice = app(QuotationAcceptance::class)->projectFor($quotation)->invoices->first();

        $this->assertArrayNotHasKey('public_token', $invoice->toArray());
    }

    public function test_the_admin_can_move_an_instalment_along(): void
    {
        $quotation = $this->quotation();
        $project = app(QuotationAcceptance::class)->projectFor($quotation);
        $second = $project->invoices->firstWhere('installment_no', 2);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.invoices.status', $second), ['status' => 'issued'])
            ->assertSessionHas('success');
        $this->assertSame(ProjectInvoice::ISSUED, $second->fresh()->status);
        $this->assertNotNull($second->fresh()->due_date);

        $this->actingAs($admin)
            ->patch(route('admin.invoices.status', $second), ['status' => 'paid', 'paid_note' => 'โอนแล้ว'])
            ->assertSessionHas('success');
        $this->assertTrue($second->fresh()->isPaid());

        // The project's money follows without anyone typing it in.
        $this->assertSame('partial', $project->fresh()->payment_status);
    }

    public function test_the_admin_invoice_pdf_downloads(): void
    {
        $quotation = $this->quotation();
        $invoice = app(QuotationAcceptance::class)->projectFor($quotation)->invoices->first();

        $response = $this->actingAs($this->admin())->get(route('admin.invoices.pdf', $invoice));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_only_an_admin_can_touch_an_invoice(): void
    {
        $quotation = $this->quotation();
        $invoice = app(QuotationAcceptance::class)->projectFor($quotation)->invoices->first();
        $customer = User::factory()->create();

        // A signed-in customer must not be able to mark their own bill paid, nor
        // pull the admin copy of a document by walking ids.
        $this->actingAs($customer)
            ->patch(route('admin.invoices.status', $invoice), ['status' => 'paid'])
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertForbidden();

        $this->assertSame(ProjectInvoice::ISSUED, $invoice->fresh()->status);
    }

    public function test_a_customer_cannot_read_another_customers_project_invoices(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = app(QuotationAcceptance::class)->projectFor($this->quotation(['user_id' => $owner->id]));

        $this->actingAs($stranger)
            ->get(route('customer.projects.show', $project))
            ->assertForbidden();
    }

    public function test_the_customer_sees_billed_instalments_on_their_project_page(): void
    {
        $user = User::factory()->create();
        $quotation = $this->quotation(['user_id' => $user->id]);
        $project = app(QuotationAcceptance::class)->projectFor($quotation);

        $this->actingAs($user)
            ->get(route('customer.projects.show', $project))
            ->assertOk()
            ->assertSee('งวดการชำระเงิน', false)
            ->assertSee($project->invoices->firstWhere('installment_no', 1)->invoice_number, false)
            // A scheduled instalment is not a bill yet — showing it as one worries
            // people about money they have not been asked for.
            ->assertDontSee($project->invoices->firstWhere('installment_no', 3)->invoice_number, false);
    }

    // ───────────────────────────────────────── the numbers behind it all

    public function test_the_instalment_plan_defaults_to_fifty_twentyfive_twentyfive(): void
    {
        $split = Pricing::instalments(100000);

        $this->assertCount(3, $split);
        $this->assertSame(50000.0, $split[0]['amount']);
        $this->assertSame(25000.0, $split[1]['amount']);
        $this->assertSame(25000.0, $split[2]['amount']);
    }

    public function test_the_last_instalment_absorbs_the_rounding(): void
    {
        Setting::setValue('quote_payment_split', [
            ['percent' => 33.33, 'label' => 'a'],
            ['percent' => 33.33, 'label' => 'b'],
            ['percent' => 33.34, 'label' => 'c'],
        ], 'json');

        $split = Pricing::instalments(100000.01);

        $this->assertSame(
            100000.01,
            round(array_sum(array_column($split, 'amount')), 2),
            'the instalments must add up to the total, to the satang'
        );
    }

    public function test_a_plan_that_does_not_add_up_is_refused_in_favour_of_the_default(): void
    {
        Setting::setValue('quote_payment_split', [
            ['percent' => 50, 'label' => 'ครึ่งแรก'],
            ['percent' => 30, 'label' => 'ครึ่งหลัง'],
        ], 'json');

        // 80% would either short-invoice the job or leave 20% uncollected.
        $this->assertCount(3, Pricing::paymentSplit());
        $this->assertSame(50.0, Pricing::paymentSplit()[0]['percent']);
    }

    public function test_discount_bands_come_from_settings(): void
    {
        $this->assertSame(0.0, Pricing::discountPercentFor(100000));
        $this->assertSame(5.0, Pricing::discountPercentFor(200000));
        $this->assertSame(15.0, Pricing::discountPercentFor(2000000));

        Setting::setValue('quote_discount_tiers', [
            ['from' => 50000, 'percent' => 3],
            ['from' => 100000, 'percent' => 8],
        ], 'json');

        $this->assertSame(3.0, Pricing::discountPercentFor(60000));
        $this->assertSame(8.0, Pricing::discountPercentFor(150000));
        $this->assertSame(8.0, Pricing::discountPercentFor(9000000), 'no band above the highest');
    }

    public function test_nonsense_settings_fall_back_instead_of_breaking_a_public_page(): void
    {
        Setting::setValue('quote_discount_tiers', [['from' => 1000, 'percent' => 250]], 'json');
        $this->assertSame(Pricing::DEFAULT_TIERS, Pricing::discountTiers());

        Setting::setValue('quote_rush_percent', 'ห้าสิบ', 'string');
        $this->assertSame(25.0, Pricing::rushPercent());

        Setting::setValue('quote_valid_days', 9999, 'integer');
        $this->assertSame(30, Pricing::validDays());
    }

    public function test_turning_the_rush_fee_off_is_a_setting_not_a_deploy(): void
    {
        Setting::setValue('quote_rush_percent', 0, 'string');

        $this->assertSame(0.0, Pricing::rushPercent());
    }

    public function test_the_admin_can_save_the_selling_numbers(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.quotations.pricing.update'), [
                'rush_percent' => 15,
                'valid_days' => 45,
                'due_days' => 14,
                'tiers' => [
                    ['from' => 300000, 'percent' => 7],
                    ['from' => '', 'percent' => ''],
                ],
                'split' => [
                    ['percent' => 40, 'label' => 'มัดจำ'],
                    ['percent' => 60, 'label' => 'ส่งมอบ'],
                ],
            ])
            ->assertRedirect(route('admin.quotations.pricing'))
            ->assertSessionHas('success');

        $this->assertSame(15.0, Pricing::rushPercent());
        $this->assertSame(45, Pricing::validDays());
        $this->assertSame(14, Pricing::dueDays());
        $this->assertCount(1, Pricing::discountTiers(), 'the blank row is how a band is removed');
        $this->assertSame(7.0, Pricing::discountPercentFor(300000));
        $this->assertCount(2, Pricing::paymentSplit());
    }

    public function test_a_payment_plan_that_misses_a_hundred_percent_is_rejected_with_a_reason(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.quotations.pricing.update'), [
                'rush_percent' => 25,
                'valid_days' => 30,
                'due_days' => 7,
                'split' => [['percent' => 60, 'label' => 'a'], ['percent' => 30, 'label' => 'b']],
            ])
            ->assertSessionHasErrors('split');

        // Nothing saved: a half-applied plan is worse than the old one.
        $this->assertCount(3, Pricing::paymentSplit());
    }

    public function test_the_pricing_page_shows_what_is_in_force(): void
    {
        Setting::setValue('quote_rush_percent', 12.5, 'string');

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.pricing'))
            ->assertOk()
            ->assertSee('เงื่อนไขราคา', false)
            ->assertSee('12.5', false);
    }

    public function test_the_settings_reach_a_quotation_built_on_the_page(): void
    {
        Mail::fake();
        Setting::setValue('quote_valid_days', 60, 'integer');
        Setting::setValue('quote_rush_percent', 10, 'string');

        $this->postJson('/quote/submit', [
            'customer_name' => 'สมหญิง',
            'customer_email' => 'somying@example.co.th',
            'customer_phone' => '089-000-0000',
            'service_type' => 'web',
            'service_options' => ['web_landing'],
            'timeline' => 'urgent',
            'action_type' => 'quotation',
        ])->assertOk();

        $quotation = Quotation::latest('id')->firstOrFail();

        $this->assertSame(60, (int) now()->startOfDay()->diffInDays($quotation->valid_until->startOfDay()));
        $this->assertEqualsWithDelta(
            round(((float) $quotation->subtotal - (float) $quotation->discount) * 0.10, 2),
            (float) $quotation->rush_fee,
            0.01,
            'the rush fee must follow the setting, not a constant'
        );
    }

    // ───────────────────────────────────────── the tax id on the document

    public function test_the_tax_id_line_appears_only_once_it_is_set(): void
    {
        $quotation = $this->quotation();

        $this->get('/quote/d/' . $quotation->public_token)
            ->assertOk()
            ->assertDontSee('เลขประจำตัวผู้เสียภาษี', false);

        Setting::setValue('company_tax_id', '0-1055-12345-67-8', 'string');

        $this->get('/quote/d/' . $quotation->public_token)
            ->assertOk()
            ->assertSee('0-1055-12345-67-8', false);
    }

    public function test_a_tax_id_that_is_not_thirteen_digits_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.contact-settings.update'), ['company_tax_id' => '123456'])
            ->assertSessionHasErrors('company_tax_id');

        $this->actingAs($admin)
            ->put(route('admin.contact-settings.update'), ['company_tax_id' => '0-1055-12345-67-8'])
            ->assertSessionDoesntHaveErrors('company_tax_id');

        $this->assertSame('0-1055-12345-67-8', Setting::getValue('company_tax_id'));
    }

    public function test_leaving_the_tax_id_empty_is_allowed(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.contact-settings.update'), ['company_tax_id' => ''])
            ->assertSessionDoesntHaveErrors('company_tax_id');
    }
}
