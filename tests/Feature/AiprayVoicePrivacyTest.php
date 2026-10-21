<?php

namespace Tests\Feature;

use App\Models\AiprayAudioSample;
use App\Models\AiprayChant;
use App\Models\AiprayContributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Voice donated through the Aipray app is personal data (PDPA). The app
 * promises three things these tests hold the server to: a retried upload is
 * stored once, only the phone that uploaded can erase, and erasing really
 * removes the audio files, not just the rows.
 *
 * Client contract: docs/audio-upload-contract.md in the Aipray repo.
 */
class AiprayVoicePrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'Qx7bG0hZ3kR9sT2vW5yA8cD1eF4gH6jK9mN2pQ5rS8u';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function upload(array $overrides = [])
    {
        return $this->postJson('/api/aipray/audio/upload', array_merge([
            'chant_id' => 'itipiso',
            'line_index' => 1,
            'audio_data' => base64_encode('fake-m4a-bytes'),
            'duration_ms' => 1800,
            'format' => 'm4a',
            'device_info' => 'android_test',
        ], $overrides));
    }

    private function contributorFields(string $id, string $secret = self::SECRET): array
    {
        return [
            'client_sample_id' => (string) Str::uuid(),
            'contributor_id' => $id,
            'contributor_key_hash' => hash('sha256', $secret),
            'consent_version' => '2026-09-12',
        ];
    }

    // --- upload ---------------------------------------------------------

    public function test_an_upload_from_an_old_app_without_the_new_fields_is_still_accepted(): void
    {
        $this->upload()->assertCreated();

        $sample = AiprayAudioSample::sole();
        $this->assertNull($sample->contributor_id);
        $this->assertNull($sample->client_sample_id);
        $this->assertSame('unlabeled', $sample->status);
        Storage::disk('local')->assertExists($sample->file_path);
        Storage::disk('public')->assertMissing($sample->file_path);
    }

    public function test_an_upload_from_1_2_5_stores_the_contributor_and_consent(): void
    {
        $contributor = (string) Str::uuid();
        $fields = $this->contributorFields($contributor);

        $this->upload($fields)->assertCreated();

        $sample = AiprayAudioSample::sole();
        $this->assertSame($fields['client_sample_id'], $sample->client_sample_id);
        $this->assertSame($contributor, $sample->contributor_id);
        $this->assertSame('2026-09-12', $sample->consent_version);
        $this->assertSame(hash('sha256', self::SECRET), AiprayContributor::sole()->key_hash);
    }

    public function test_an_upload_is_labelled_with_the_text_of_its_chant_line(): void
    {
        AiprayChant::create([
            'chant_id' => 'itipiso',
            'title_th' => 'อิติปิโส',
            'lines' => ['อิติปิ โส ภะคะวา', 'อะระหัง สัมมาสัมพุทโธ'],
        ]);

        $this->upload(['line_index' => 1])->assertCreated();

        $sample = AiprayAudioSample::sole();
        $this->assertSame('auto_labeled', $sample->status);
        $this->assertSame('อะระหัง สัมมาสัมพุทโธ', $sample->transcript);
        $this->assertSame(1, AiprayAudioSample::trainable()->count());
    }

    public function test_a_retried_upload_returns_the_same_id_and_is_stored_once(): void
    {
        $fields = $this->contributorFields((string) Str::uuid());

        $first = $this->upload($fields)->assertCreated()->json('id');
        $this->upload($fields)->assertOk()->assertJson(['id' => $first, 'duplicate' => true]);

        $this->assertSame(1, AiprayAudioSample::count());
        $this->assertCount(1, Storage::disk('local')->allFiles('aipray_audio'));
    }

    public function test_a_contributor_id_with_a_different_key_hash_is_rejected_with_409(): void
    {
        $contributor = (string) Str::uuid();
        $this->upload($this->contributorFields($contributor))->assertCreated();

        $this->upload($this->contributorFields($contributor, 'another-phone-another-secret-0000000000000'))
            ->assertStatus(409);

        $this->assertSame(1, AiprayAudioSample::count());
    }

    public function test_format_and_chant_id_that_would_escape_the_file_name_are_rejected(): void
    {
        $this->upload(['format' => 'php'])->assertStatus(422);
        $this->upload(['chant_id' => '../../etc'])->assertStatus(422);

        $this->assertSame(0, AiprayAudioSample::count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    // --- erase ----------------------------------------------------------

    public function test_erase_deletes_every_sample_of_that_phone_and_its_files(): void
    {
        $mine = (string) Str::uuid();
        $theirs = (string) Str::uuid();
        $this->upload($this->contributorFields($mine))->assertCreated();
        $this->upload($this->contributorFields($mine))->assertCreated();
        $this->upload($this->contributorFields($theirs, 'someone-else-entirely-00000000000000000000'))->assertCreated();
        $myFiles = AiprayAudioSample::where('contributor_id', $mine)->pluck('file_path');

        $this->postJson('/api/aipray/contributors/erase', ['contributor_id' => $mine, 'secret' => self::SECRET])
            ->assertOk()->assertExactJson(['deleted' => 2]);

        $this->assertSame(0, AiprayAudioSample::where('contributor_id', $mine)->count());
        foreach ($myFiles as $path) {
            Storage::disk('local')->assertMissing($path);
        }
        $this->assertSame(1, AiprayAudioSample::where('contributor_id', $theirs)->count());
        $this->assertNull(AiprayContributor::where('contributor_id', $mine)->first());

        // The app may repeat the request; it must stay harmless.
        $this->postJson('/api/aipray/contributors/erase', ['contributor_id' => $mine, 'secret' => self::SECRET])
            ->assertOk()->assertExactJson(['deleted' => 0]);
    }

    public function test_erase_with_the_wrong_secret_is_forbidden_and_deletes_nothing(): void
    {
        $contributor = (string) Str::uuid();
        $this->upload($this->contributorFields($contributor))->assertCreated();

        $this->postJson('/api/aipray/contributors/erase', [
            'contributor_id' => $contributor,
            'secret' => 'guessing-guessing-guessing-guessing-00000',
        ])->assertForbidden();

        $this->assertSame(1, AiprayAudioSample::count());
    }

    public function test_erasing_an_unknown_contributor_answers_deleted_zero_not_404(): void
    {
        // The app retries 404 forever, so an unknown id must not look like a missing route.
        $this->postJson('/api/aipray/contributors/erase', [
            'contributor_id' => (string) Str::uuid(),
            'secret' => self::SECRET,
        ])->assertOk()->assertExactJson(['deleted' => 0]);
    }

    public function test_erase_is_rate_limited(): void
    {
        $body = ['contributor_id' => (string) Str::uuid(), 'secret' => self::SECRET];
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/aipray/contributors/erase', $body)->assertOk();
        }

        $this->postJson('/api/aipray/contributors/erase', $body)
            ->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMIT_EXCEEDED');

        // Another phone behind the same address (CGNAT) can still erase.
        $this->postJson('/api/aipray/contributors/erase', ['contributor_id' => (string) Str::uuid(), 'secret' => self::SECRET])
            ->assertOk();
    }

    public function test_erase_still_works_after_the_address_used_up_the_general_api_limit(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/aipray/stats');
        }
        $this->getJson('/api/aipray/stats')->assertStatus(429);

        $this->postJson('/api/aipray/contributors/erase', ['contributor_id' => (string) Str::uuid(), 'secret' => self::SECRET])
            ->assertOk();
    }

    // --- admin ----------------------------------------------------------

    public function test_admin_delete_removes_the_real_file_on_either_disk(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->upload()->assertCreated();
        $appSample = AiprayAudioSample::sole();
        // An admin recording from before 2026-09, still on the public disk.
        Storage::disk('public')->put('aipray_audio/old_admin.wav', 'x');
        $oldSample = AiprayAudioSample::create([
            'filename' => 'old_admin.wav', 'file_path' => 'aipray_audio/old_admin.wav',
            'format' => 'wav', 'status' => 'labeled',
        ]);

        $this->actingAs($admin)->delete(route('admin.aipray.dataset.destroy', $appSample))->assertRedirect();
        $this->actingAs($admin)->delete(route('admin.aipray.dataset.destroy', $oldSample))->assertRedirect();

        Storage::disk('local')->assertMissing($appSample->file_path);
        Storage::disk('public')->assertMissing('aipray_audio/old_admin.wav');
        $this->assertSame(0, AiprayAudioSample::count());
    }

    public function test_admins_hear_samples_through_an_authenticated_route_only(): void
    {
        $this->upload()->assertCreated();
        $sample = AiprayAudioSample::sole();
        $url = route('admin.aipray.dataset.audio', $sample);

        $this->get($url)->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'user']))->get($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mp4');
    }

    public function test_admin_can_save_every_status_including_auto_labeled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->upload()->assertCreated();
        $sample = AiprayAudioSample::sole();

        foreach (AiprayAudioSample::STATUSES as $status) {
            $this->actingAs($admin)
                ->put(route('admin.aipray.dataset.update', $sample), ['status' => $status, 'transcript' => 'ข้อความ'])
                ->assertSessionHasNoErrors();
            $this->assertSame($status, $sample->fresh()->status);
        }
    }

    // --- retention, labelling, privacy page -----------------------------

    public function test_retention_deletes_samples_older_than_the_promised_period_with_their_files(): void
    {
        config(['services.aipray.audio_retention_days' => 1095]);
        $contributor = (string) Str::uuid();
        $this->upload($this->contributorFields($contributor))->assertCreated();
        $old = AiprayAudioSample::sole();
        $old->forceFill(['created_at' => now()->subDays(1096)])->save();
        $this->upload()->assertCreated();

        $this->artisan('aipray:prune-audio')->assertSuccessful();

        $this->assertNull($old->fresh());
        Storage::disk('local')->assertMissing($old->file_path);
        $this->assertSame(1, AiprayAudioSample::count());
        $this->assertSame(0, AiprayContributor::count());
    }

    public function test_retention_keeps_samples_inside_the_period_on_either_disk(): void
    {
        config(['services.aipray.audio_retention_days' => 1095]);
        $this->upload()->assertCreated();
        $recent = AiprayAudioSample::sole();
        $recent->forceFill(['created_at' => now()->subDays(1094)])->save();
        // An admin recording from before 2026-09, still on the public disk.
        Storage::disk('public')->put('aipray_audio/old_admin.wav', 'x');
        AiprayAudioSample::create([
            'filename' => 'old_admin.wav', 'file_path' => 'aipray_audio/old_admin.wav',
            'format' => 'wav', 'status' => 'labeled',
        ]);

        $this->artisan('aipray:prune-audio')->assertSuccessful();

        $this->assertSame(2, AiprayAudioSample::count());
        Storage::disk('local')->assertExists($recent->file_path);
        Storage::disk('public')->assertExists('aipray_audio/old_admin.wav');
    }

    public function test_retention_refuses_to_run_with_a_broken_setting(): void
    {
        config(['services.aipray.audio_retention_days' => 0]); // e.g. AIPRAY_AUDIO_RETENTION_DAYS= left empty
        $this->upload()->assertCreated();

        $this->artisan('aipray:prune-audio')->assertFailed();

        $this->assertSame(1, AiprayAudioSample::count());
    }

    public function test_chants_import_and_backfill_label_earlier_uploads(): void
    {
        $this->upload(['chant_id' => 'namassakara', 'line_index' => 0])->assertCreated();
        $this->assertSame('unlabeled', AiprayAudioSample::sole()->status);

        $this->artisan('aipray:import-chants')->assertSuccessful();
        $this->artisan('aipray:auto-label')->assertSuccessful();

        $sample = AiprayAudioSample::sole();
        $this->assertSame('auto_labeled', $sample->status);
        $this->assertNotEmpty($sample->transcript);
        $this->assertSame(21, AiprayChant::count());
    }

    public function test_the_privacy_page_linked_from_the_app_exists_and_is_marked_as_a_draft(): void
    {
        config(['services.aipray.privacy_policy_reviewed' => false]);

        $this->get('/apps/aipray/privacy')
            ->assertOk()
            ->assertSee('นโยบายความเป็นส่วนตัว')
            ->assertSee('ฉบับร่าง')
            ->assertSee('ไม่เกิน 3 ปี');
    }
}
