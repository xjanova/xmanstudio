<?php

namespace Tests\Feature;

use App\Models\AvatarPack;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

/**
 * Knowledge packs in the GigGok pack store (kind "knowledge").
 *
 * The rule worth the most tests is the upload screen: the app refuses to
 * install a pack that breaks any of its rules, so a zip that gets onto the
 * shelf without passing them is a customer who pays and cannot install.
 * Contract: docs/pack-store.md and docs/knowledge-packs.md in the app repo.
 */
class KnowledgePackStoreTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        config(['packs.app_product_slug' => 'giggok', 'packs.disk' => 'local']);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ── building packs ────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    protected function manifest(array $overrides = []): array
    {
        return array_merge([
            'id' => 'survival-basic',
            'kind' => 'knowledge',
            'version' => '1.2.0',
            'name' => ['th' => 'เอาตัวรอดในป่า', 'en' => 'Wilderness survival'],
            'category' => 'survival',
            'persona' => ['th' => 'สอนใจเย็น ทีละขั้น', 'en' => 'Calm, one step at a time'],
            'starters' => ['th' => ['หลงป่าต้องทำอะไรก่อน'], 'en' => ['I am lost, what do I do first?']],
            'safety' => ['th' => ['ไม่แน่ใจว่ากินได้ อย่ากิน'], 'en' => ['Not sure it is edible? Do not eat it']],
            'tools' => ['sos'],
            'minAppVersion' => '0.1.0',
        ], $overrides);
    }

    /**
     * A pack the app installs: a manifest and two topics with a source each.
     *
     * @return array<string, mixed>
     */
    protected function validFiles(array $manifest = [], string $prefix = ''): array
    {
        return [
            $prefix . 'manifest.json' => $this->manifest($manifest),
            $prefix . 'content/01-lost.md' => "# หลงป่าต้องทำอะไรก่อน\nหยุดอยู่กับที่ แล้วโทร 1669\nที่มา: สพฉ.\n",
            $prefix . 'content/02-water.md' => "# น้ำในลำธารกินได้ไหม\nต้มให้เดือดก่อนดื่ม\nที่มา: WHO\n",
        ];
    }

    /**
     * Real image bytes, drawn with GD.
     */
    protected function image(string $encoder = 'imagepng', int $width = 2, int $height = 2): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        $encoder($image);

        return (string) ob_get_clean();
    }

    /**
     * A 3x2 WebP, kept as bytes so the test does not need GD built with WebP.
     */
    protected function webp(): string
    {
        return base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoDAAIAAUAmJaQAA3AA/v02aAA=');
    }

    /**
     * @param  array<string, mixed>  $files  name => contents; an array is written as JSON
     */
    protected function zip(array $files, ?callable $tweak = null): UploadedFile
    {
        $base = tempnam(sys_get_temp_dir(), 'kp');
        @unlink($base);
        $path = $base . '.zip';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $body) {
            $zip->addFromString($name, is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : $body);
        }

        if ($tweak) {
            $tweak($zip);
        }

        $zip->close();

        return new UploadedFile($path, 'pack.zip', 'application/zip', null, true);
    }

    /**
     * A second upload of the very same bytes.
     */
    protected function copyOf(UploadedFile $file): UploadedFile
    {
        $path = $file->getRealPath() . '-copy.zip';
        copy($file->getRealPath(), $path);

        return new UploadedFile($path, 'pack.zip', 'application/zip', null, true);
    }

    protected function createPack(array $overrides = []): AvatarPack
    {
        $this->actingAs($this->admin)->post(route('admin.packs.store'), array_merge([
            'pack_id' => 'survival-basic',
            'name_th' => 'เอาตัวรอดในป่าเบื้องต้น',
            'name_en' => 'Wilderness survival basics',
            'summary_th' => 'หลงป่า น้ำดื่ม งูกัด',
            'summary_en' => 'Lost, water, snakebite',
            'kind' => AvatarPack::KIND_KNOWLEDGE,
            // The real form always sends the prerequisite select, hidden or not
            'requires' => '',
            'price' => 0,
            'is_active' => '1',
        ], $overrides))->assertSessionHasNoErrors()->assertRedirect();

        return AvatarPack::firstWhere('pack_id', $overrides['pack_id'] ?? 'survival-basic');
    }

    protected function upload(AvatarPack $pack, UploadedFile $file, array $extra = [])
    {
        return $this->actingAs($this->admin)
            ->post(route('admin.packs.file', $pack), ['file' => $file] + $extra);
    }

    /**
     * A knowledge pack with a valid zip on sale.
     */
    protected function packOnSale(float $price = 0, array $overrides = []): AvatarPack
    {
        $pack = $this->createPack(['price' => $price] + $overrides);
        $this->upload($pack, $this->zip($this->validFiles(['id' => $pack->pack_id])))->assertSessionHasNoErrors();

        return $pack->fresh();
    }

    protected function appLicense(?User $user): LicenseKey
    {
        return LicenseKey::create([
            'product_id' => Product::where('slug', 'giggok')->sole()->id,
            'user_id' => $user?->id,
            'license_key' => 'APP-' . uniqid(),
            'status' => 'active',
        ]);
    }

    // ── the catalogue ─────────────────────────────────────────────────

    /**
     * 🔴 An app from before knowledge packs reads any kind it does not know
     * as a character, lists it among the outfits, and cannot install it -
     * after the customer has paid. It does not send ?with=knowledge.
     */
    public function test_an_app_that_does_not_ask_for_knowledge_never_sees_it(): void
    {
        $this->packOnSale();
        $this->createPack(['pack_id' => 'nana-office', 'kind' => AvatarPack::KIND_OUTFIT, 'price' => 149]);

        $ids = collect($this->getJson('/api/packs')->assertOk()->json('packs'))->pluck('id')->all();

        $this->assertSame(['nana-office'], $ids);
    }

    public function test_the_app_that_asks_for_knowledge_gets_it_with_its_version_and_summary(): void
    {
        $this->packOnSale();
        $this->createPack(['pack_id' => 'nana-office', 'kind' => AvatarPack::KIND_OUTFIT, 'price' => 149]);

        $packs = collect($this->getJson('/api/packs?with=knowledge')->assertOk()->json('packs'))->keyBy('id');

        $this->assertSame(['nana-office', 'survival-basic'], $packs->keys()->sort()->values()->all());

        $item = $packs['survival-basic'];
        $this->assertSame('knowledge', $item['kind']);
        $this->assertSame('1.2.0', $item['version']);
        $this->assertSame(['th' => 'หลงป่า น้ำดื่ม งูกัด', 'en' => 'Lost, water, snakebite'], $item['summary']);
        $this->assertNull($item['requires']);
        $this->assertSame(0, $item['price']);
        $this->assertGreaterThan(0, $item['sizeBytes']);
    }

    public function test_with_is_a_comma_list_and_anything_else_opts_into_nothing(): void
    {
        $this->packOnSale();

        $this->getJson('/api/packs?with=outfits,knowledge')->assertOk()->assertJsonPath('packs.0.id', 'survival-basic');
        $this->getJson('/api/packs?with=knowledges')->assertOk()->assertJsonCount(0, 'packs');

        // ?with[]=knowledge is not a string; it must not 500, and it opts into nothing
        $this->getJson('/api/packs?with[]=knowledge')->assertOk()->assertJsonCount(0, 'packs');
    }

    /**
     * The summary is not knowledge-only - an outfit can say what it looks like.
     */
    public function test_an_outfit_carries_a_summary_and_english_falls_back_to_thai(): void
    {
        $this->createPack([
            'pack_id' => 'nana-office',
            'kind' => AvatarPack::KIND_OUTFIT,
            'summary_th' => 'ชุดสูทสีเทา',
            'summary_en' => '',
        ]);
        $this->createPack(['pack_id' => 'plain', 'kind' => AvatarPack::KIND_OUTFIT, 'summary_th' => '', 'summary_en' => '']);

        $packs = collect($this->getJson('/api/packs')->json('packs'))->keyBy('id');

        $this->assertSame(['th' => 'ชุดสูทสีเทา', 'en' => 'ชุดสูทสีเทา'], $packs['nana-office']['summary']);
        $this->assertNull($packs['plain']['summary']);
        // No file uploaded yet: nothing for the app to compare an installed pack against
        $this->assertNull($packs['nana-office']['version']);
    }

    // ── the admin screens ─────────────────────────────────────────────

    public function test_a_knowledge_pack_is_created_from_the_admin_form(): void
    {
        $pack = $this->createPack(['requires' => 'mind-default']);

        $this->assertSame(AvatarPack::KIND_KNOWLEDGE, $pack->kind);
        $this->assertSame('หลงป่า น้ำดื่ม งูกัด', $pack->summary_th);
        $this->assertSame('Lost, water, snakebite', $pack->summary_en);
        // A prerequisite only means something for an outfit
        $this->assertNull($pack->requires);
        $this->assertSame('giggok-packs', $pack->product->category->slug);
    }

    /**
     * Content cannot be checked by code: a person has to read it, and an
     * investing pack filed under any other category escapes every rule the
     * app adds for trading. The form has to say so where the admin works.
     */
    public function test_the_form_asks_for_a_human_read_and_trading_for_investing(): void
    {
        $this->actingAs($this->admin)->get(route('admin.packs.create'))
            ->assertOk()
            ->assertSee('ต้องมีคนอ่านเนื้อหาก่อนเปิดขาย')
            ->assertSee('"category": "trading"', false);

        $pack = $this->createPack();

        $this->actingAs($this->admin)->get(route('admin.packs.edit', $pack))
            ->assertOk()
            ->assertSee('manifest.json')
            ->assertDontSee('name="version"', false);

        $this->actingAs($this->admin)->get(route('admin.packs.index'))->assertOk()->assertSee('ความรู้');
    }

    public function test_a_knowledge_pack_id_the_app_would_refuse_is_rejected(): void
    {
        foreach (['_state', '.hidden', '-x'] as $id) {
            $this->actingAs($this->admin)->post(route('admin.packs.store'), [
                'pack_id' => $id,
                'name_th' => 'x',
                'kind' => AvatarPack::KIND_KNOWLEDGE,
                'price' => 0,
            ])->assertSessionHasErrors('pack_id');
        }

        $this->assertDatabaseCount('avatar_packs', 0);
    }

    /**
     * A knowledge zip and an outfit zip are different formats; flipping the
     * kind after upload would put a file on sale that cannot install.
     */
    public function test_the_kind_cannot_cross_the_knowledge_line_once_a_file_is_up(): void
    {
        $pack = $this->packOnSale();

        $this->actingAs($this->admin)->put(route('admin.packs.update', $pack), [
            'pack_id' => $pack->pack_id,
            'name_th' => 'x',
            'name_en' => '',
            'kind' => AvatarPack::KIND_OUTFIT,
            'requires' => '',
            'price' => 0,
        ])->assertSessionHasErrors('kind');

        $this->assertSame(AvatarPack::KIND_KNOWLEDGE, $pack->fresh()->kind);

        // The other way round too: an outfit's pack.json zip is not a knowledge pack
        $outfit = $this->createPack(['pack_id' => 'nana-office', 'kind' => AvatarPack::KIND_OUTFIT]);
        $this->upload($outfit, $this->zip(['pack.json' => ['id' => 'nana-office'], 'x.vrm' => 'vrm']))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->put(route('admin.packs.update', $outfit), [
            'pack_id' => 'nana-office',
            'name_th' => 'x',
            'name_en' => '',
            'kind' => AvatarPack::KIND_KNOWLEDGE,
            'requires' => '',
            'price' => 0,
        ])->assertSessionHasErrors('kind');

        // With no file there is nothing to mismatch, so a mistake is fixable
        $draft = $this->createPack(['pack_id' => 'draft']);

        $this->actingAs($this->admin)->put(route('admin.packs.update', $draft), [
            'pack_id' => 'draft',
            'name_th' => 'x',
            'name_en' => '',
            'kind' => AvatarPack::KIND_OUTFIT,
            'requires' => '',
            'price' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(AvatarPack::KIND_OUTFIT, $draft->fresh()->kind);
    }

    // ── uploading: what gets in ───────────────────────────────────────

    /**
     * The version on sale is read from the zip, never typed: the app compares
     * it with the installed pack to offer an update, so it has to be the
     * file's own.
     */
    public function test_a_valid_zip_is_stored_under_the_version_in_its_manifest(): void
    {
        $pack = $this->createPack();

        $this->upload($pack, $this->zip($this->validFiles()), ['version' => '9.9.9'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $version = $pack->product->fresh()->latestVersion();

        $this->assertSame('1.2.0', $version->version);
        $this->assertSame(64, strlen($version->sha256));
        Storage::disk('local')->assertExists($version->storage_path);
        $this->getJson('/api/packs?with=knowledge')->assertJsonPath('packs.0.version', '1.2.0');
    }

    /**
     * Right-click → zip on Windows wraps everything in one folder, and macOS
     * adds its own litter. The app copes with both, so must the upload.
     */
    public function test_a_wrapped_zip_with_os_junk_images_and_a_readme_is_accepted(): void
    {
        $pack = $this->createPack();

        $files = $this->validFiles([], 'survival-basic/') + [
            'survival-basic/images/map.png' => $this->image(),
            'survival-basic/images/fire.JPEG' => $this->image('imagejpeg'),
            'survival-basic/README.md' => 'notes for whoever edits this pack',
            'survival-basic/.DS_Store' => 'junk',
            'survival-basic/Thumbs.db' => 'junk',
            '__MACOSX/survival-basic/._manifest.json' => 'junk',
        ];

        $this->upload($pack, $this->zip($files))->assertSessionHasNoErrors();

        $this->assertSame('1.2.0', $pack->product->fresh()->latestVersion()->version);
    }

    /**
     * The sample pack keeps each language in its own folder. Both are still
     * under content/, so a pack with nothing else in content/ has content.
     */
    public function test_content_in_language_folders_counts_as_content(): void
    {
        $pack = $this->createPack();

        $this->upload($pack, $this->zip([
            'manifest.json' => $this->manifest(),
            'content/th/01-lost.md' => "# หลงป่าต้องทำอะไรก่อน\nหยุดอยู่กับที่ แล้วโทร 1669\n",
            'content/en/01-lost.md' => "# Lost in the woods\nStay where you are and call 1669\n",
        ]))->assertSessionHasNoErrors();

        $this->assertSame('1.2.0', $pack->product->fresh()->latestVersion()->version);
    }

    public function test_a_real_small_png_is_accepted(): void
    {
        $pack = $this->createPack();

        $this->upload($pack, $this->zip($this->validFiles() + ['images/map.png' => $this->image()]))
            ->assertSessionHasNoErrors();

        $this->assertSame('1.2.0', $pack->product->fresh()->latestVersion()->version);
    }

    /**
     * 120 images, one exactly 4096 pixels wide, a WebP, and a PNG behind a
     * .jpg name - the app reads what the bytes are, not what the name says.
     */
    public function test_images_right_at_the_limits_are_accepted(): void
    {
        $pack = $this->createPack();
        $dot = $this->image('imagepng', 1, 1);

        $files = $this->validFiles() + [
            'content/th/wide.jpg' => $this->image('imagejpeg', 4096, 1),
            'images/cover.webp' => $this->webp(),
            'images/photo.jpg' => $dot,
        ];

        for ($i = 3; $i < 120; $i++) {
            $files["images/dot-{$i}.png"] = $dot;
        }

        $this->upload($pack, $this->zip($files))->assertSessionHasNoErrors();

        $this->assertSame('1.2.0', $pack->product->fresh()->latestVersion()->version);
    }

    public function test_the_same_file_again_is_fine_but_a_different_file_needs_a_new_version(): void
    {
        $pack = $this->createPack();
        $first = $this->zip($this->validFiles());

        $this->upload($pack, $first)->assertSessionHasNoErrors();
        $this->upload($pack, $this->copyOf($first))->assertSessionHasNoErrors();

        // The second upload replaced the first file rather than leaving it behind
        $this->assertCount(1, Storage::disk('local')->allFiles());
        Storage::disk('local')->assertExists($pack->product->fresh()->latestVersion()->storage_path);

        $changed = $this->validFiles() + ['content/03-snake.md' => "# โดนงูกัด\nโทร 1669\nที่มา: สพฉ.\n"];

        $this->upload($pack, $this->zip($changed))->assertSessionHasErrors('file');
        $this->assertSame(1, $pack->product->versions()->count());

        $this->upload($pack, $this->zip(['manifest.json' => $this->manifest(['version' => '1.3.0'])] + $changed))
            ->assertSessionHasNoErrors();

        $active = $pack->product->versions()->where('is_active', true)->get();
        $this->assertCount(1, $active);
        $this->assertSame('1.3.0', $active->first()->version);
    }

    // ── uploading: what is refused ────────────────────────────────────

    /**
     * @return array<string, array{0: callable(self): UploadedFile, 1: string}>
     */
    public static function refusedZips(): array
    {
        $thai = fn (int $n) => str_repeat('ก', $n);

        return [
            'manifest id is not the id on sale' => [
                fn (self $t) => $t->zip($t->validFiles(['id' => 'someone-else'])), 'แต่แพ็กนี้ขายในชื่อ',
            ],
            'manifest kind is not knowledge' => [
                fn (self $t) => $t->zip($t->validFiles(['kind' => 'character'])), 'ต้องเป็น "knowledge"',
            ],
            'an outfit zip with pack.json and no manifest' => [
                fn (self $t) => $t->zip(['pack.json' => ['id' => 'survival-basic'], 'x.vrm' => 'vrm']), 'ไม่มี manifest.json',
            ],
            'a script' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content/run.js' => 'alert(1)']), 'ที่แพ็กความรู้ไม่รับ',
            ],
            'an html page' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content/page.html' => '<script></script>']), 'ที่แพ็กความรู้ไม่รับ',
            ],
            'a nested manifest is just another unknown file' => [
                fn (self $t) => $t->zip($t->validFiles() + ['extra/manifest.json' => '{}']), 'ที่แพ็กความรู้ไม่รับ',
            ],
            'a path climbing out with ..' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content/../../evil.md' => 'x']), 'ไม่ปลอดภัย',
            ],
            'an absolute path' => [
                fn (self $t) => $t->zip($t->validFiles() + ['/etc/evil.md' => 'x']), 'ไม่ปลอดภัย',
            ],
            'a drive letter' => [
                fn (self $t) => $t->zip($t->validFiles() + ['C:/evil.md' => 'x']), 'ไม่ปลอดภัย',
            ],
            'a backslash path climbing out' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content\\..\\..\\evil.md' => 'x']), 'ไม่ปลอดภัย',
            ],
            'a symlink' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content/link.md' => '/etc/passwd'], function (ZipArchive $zip) {
                    $zip->setExternalAttributesName('content/link.md', ZipArchive::OPSYS_UNIX, 0120777 << 16);
                }), 'symlink',
            ],
            'a zip over 8 MB' => [
                fn (self $t) => $t->zip($t->validFiles() + ['images/noise.png' => random_bytes(8 * 1024 * 1024 + 4096)], function (ZipArchive $zip) {
                    $zip->setCompressionName('images/noise.png', ZipArchive::CM_STORE);
                }), 'เกินเพดาน 8 MB',
            ],
            'a zip that unpacks past 24 MB' => [
                fn (self $t) => $t->zip($t->validFiles() + ['images/blank.png' => str_repeat("\0", 24 * 1024 * 1024 + 1)]), 'แตกแล้วได้',
            ],
            'a content file over 512 KB' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content/big.md' => str_repeat('a', 512 * 1024 + 1)]), 'ต่อไฟล์',
            ],
            'a text file outside content over 512 KB' => [
                fn (self $t) => $t->zip($t->validFiles() + ['README.txt' => str_repeat('a', 512 * 1024 + 1)]), 'ต่อไฟล์',
            ],
            'a manifest over 64 KB' => [
                fn (self $t) => $t->zip($t->validFiles(['padding' => str_repeat('x', 64 * 1024)])), 'ต่อไฟล์',
            ],
            'content over 400,000 characters' => [
                fn (self $t) => $t->zip($t->validFiles() + [
                    'content/a.md' => "# ก\n" . str_repeat('a', 140000),
                    'content/b.md' => "# ข\n" . str_repeat('b', 140000),
                    'content/c.md' => "# ค\n" . str_repeat('c', 140000),
                ]), 'เกินเพดาน 400,000',
            ],
            'nothing under content' => [
                fn (self $t) => $t->zip(['manifest.json' => $t->manifest(), 'README.md' => 'hello']), 'ไม่มีเนื้อหา',
            ],
            'content that is only headings and blank lines' => [
                fn (self $t) => $t->zip(['manifest.json' => $t->manifest(), 'content/a.md' => "# หัวข้อ\n\n   \n## อีกหัวข้อ\n"]), 'ไม่มีเนื้อหา',
            ],
            'an investing category the app does not know' => [
                fn (self $t) => $t->zip($t->validFiles(['category' => 'investing'])), 'category',
            ],
            'no category at all' => [
                fn (self $t) => $t->zip($t->validFiles(['category' => null])), 'category',
            ],
            'a persona over 300 characters' => [
                fn (self $t) => $t->zip($t->validFiles(['persona' => ['th' => $thai(301), 'en' => 'ok']])), 'persona (th)',
            ],
            'one persona for both languages over 300 characters' => [
                fn (self $t) => $t->zip($t->validFiles(['persona' => $thai(301)])), 'persona',
            ],
            'five safety rules' => [
                fn (self $t) => $t->zip($t->validFiles(['safety' => ['th' => ['1', '2', '3', '4', '5']]])), 'safety (th)',
            ],
            'a safety rule over 160 characters' => [
                fn (self $t) => $t->zip($t->validFiles(['safety' => ['en' => [str_repeat('x', 161)]]])), 'safety (en)',
            ],
            'seven starters' => [
                fn (self $t) => $t->zip($t->validFiles(['starters' => ['1', '2', '3', '4', '5', '6', '7']])), 'starters',
            ],
            'a starter over 120 characters' => [
                fn (self $t) => $t->zip($t->validFiles(['starters' => ['th' => [$thai(121)]]])), 'starters (th)',
            ],
            'a version written as a number' => [
                fn (self $t) => $t->zip($t->validFiles(['version' => 1.0])), '"version"',
            ],
            'no version' => [
                fn (self $t) => $t->zip($t->validFiles(['version' => null])), '"version"',
            ],
            'fake bytes named .png' => [
                fn (self $t) => $t->zip($t->validFiles() + ['images/map.png' => 'not really a png']), 'ไม่ใช่รูป PNG JPEG หรือ WebP',
            ],
            'a script named .png at the root' => [
                fn (self $t) => $t->zip($t->validFiles() + ['cover.png' => '<?php system($_GET["c"]); ?>']), '"cover.png" ไม่ใช่รูป',
            ],
            'a GIF named .png' => [
                fn (self $t) => $t->zip($t->validFiles() + ['images/map.png' => $t->image('imagegif')]), 'ไม่ใช่รูป PNG JPEG หรือ WebP',
            ],
            'an empty .webp' => [
                fn (self $t) => $t->zip($t->validFiles() + ['images/blank.webp' => '']), 'ไม่ใช่รูป PNG JPEG หรือ WebP',
            ],
            'a JPEG 5000 pixels wide' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content/th/wide.jpg' => $t->image('imagejpeg', 5000, 100)]), '5000×100 พิกเซล เกินเพดาน 4096',
            ],
            'an image over 2 MB' => [
                fn (self $t) => $t->zip($t->validFiles() + ['images/big.png' => random_bytes(2 * 1024 * 1024 + 1)]), 'เกินเพดาน 2,048 KB',
            ],
            '121 images' => [
                function (self $t) {
                    $dot = $t->image('imagepng', 1, 1);
                    $files = $t->validFiles();

                    for ($i = 0; $i < 121; $i++) {
                        $files["images/dot-{$i}.png"] = $dot;
                    }

                    return $t->zip($files);
                }, 'มีรูป 121 รูป เกินเพดาน 120',
            ],
            'Thai saved as TIS-620 instead of UTF-8' => [
                fn (self $t) => $t->zip($t->validFiles() + ['content/old.txt' => "\xA1\xD2\xC3\xE0\xB4\xD4\xB9\xBB\xE8\xD2"]), 'UTF-8',
            ],
            'a manifest that is not JSON' => [
                fn (self $t) => $t->zip(['manifest.json' => '{"id": "survival-basic",'] + array_slice($t->validFiles(), 1, null, true)), 'JSON',
            ],
            'not a zip at all' => [
                fn (self $t) => UploadedFile::fake()->createWithContent('pack.zip', 'PK this is not a zip'), 'zip',
            ],
        ];
    }

    /**
     * Each of these the app refuses at install. Refused here, the admin sees
     * why and nothing is stored, so nothing un-installable is ever on sale.
     */
    #[DataProvider('refusedZips')]
    public function test_a_zip_the_app_would_refuse_is_refused_at_upload(callable $build, string $reason): void
    {
        $pack = $this->createPack();

        $response = $this->upload($pack, $build($this))->assertSessionHasErrors('file');

        $this->assertStringContainsString($reason, session('errors')->first('file'));
        $this->assertNull($pack->product->fresh()->latestVersion());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $response->assertRedirect();
    }

    /**
     * The reason has to reach the person who uploaded, next to the button.
     */
    public function test_the_reason_for_a_refusal_is_shown_on_the_admin_page(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)
            ->from(route('admin.packs.edit', $pack))
            ->followingRedirects()
            ->post(route('admin.packs.file', $pack), ['file' => $this->zip($this->validFiles(['category' => 'crypto']))])
            ->assertOk()
            ->assertSee('category &quot;crypto&quot; ใช้ไม่ได้', false)
            ->assertSee('trading');
    }

    // ── downloading ───────────────────────────────────────────────────

    /**
     * A fresh install has no license at all and sends no Authorization
     * header. A free knowledge pack must still come down, and the bytes that
     * come down must be the zip the admin uploaded.
     */
    public function test_a_free_knowledge_pack_downloads_with_no_authorization_header(): void
    {
        $pack = $this->packOnSale(0);
        $version = $pack->product->latestVersion();

        $response = $this->postJson('/api/packs/survival-basic/download')
            ->assertOk()
            ->assertJsonPath('sha256', $version->sha256);

        $file = $this->get($response->json('url'))->assertOk();

        $this->assertSame($version->sha256, hash('sha256', $file->streamedContent()));
    }

    public function test_a_paid_knowledge_pack_needs_a_license_that_owns_it(): void
    {
        $pack = $this->packOnSale(99);
        $user = User::factory()->create();
        $appLicense = $this->appLicense($user);

        $this->postJson('/api/packs/survival-basic/download')->assertStatus(401);

        $this->withToken($appLicense->license_key)
            ->postJson('/api/packs/survival-basic/download')
            ->assertStatus(403);

        LicenseKey::create([
            'product_id' => $pack->product_id,
            'user_id' => $user->id,
            'license_key' => 'KP-' . uniqid(),
            'status' => 'active',
        ]);

        $this->withToken($appLicense->license_key)
            ->getJson('/api/packs/mine')
            ->assertExactJson(['owned' => ['survival-basic']]);

        $this->withToken($appLicense->license_key)
            ->postJson('/api/packs/survival-basic/download')
            ->assertOk()
            ->assertJsonStructure(['url', 'sha256', 'expiresIn']);
    }

    /**
     * Bought outright, like an outfit: a yearly license would take the pack
     * out of the app's owned list a year after the customer paid for it.
     */
    public function test_buying_a_knowledge_pack_gives_a_license_that_never_expires(): void
    {
        $pack = $this->packOnSale(99);
        $user = User::factory()->create();

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0800000000',
            'subtotal' => 99,
            'total' => 99,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $pack->product_id,
            'product_name' => $pack->product->name,
            'price' => 99,
            'quantity' => 1,
            'subtotal' => 99,
        ]);

        app(LicenseService::class)->generateLicensesForOrder($order);

        $license = LicenseKey::where('product_id', $pack->product_id)->sole();

        $this->assertSame('lifetime', $license->license_type);
        $this->assertNull($license->expires_at);
    }
}
