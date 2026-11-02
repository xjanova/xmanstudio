<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvatarPack;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\KnowledgePackException;
use App\Services\KnowledgePackValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Avatar packs for the GigGok app.
 *
 * A pack is a product, so price, orders and licensing are the machinery that
 * already exists. This screen exists because building one means touching a
 * product, a pack record and a version, and an admin adding an outfit should
 * not have to know that.
 */
class AvatarPackController extends Controller
{
    /**
     * Slug of the category packs are filed under. Created on first use.
     *
     * Taken from Category::APP_ONLY_SLUGS rather than written out again: that
     * list is what keeps packs off the public website, and a second copy of
     * the string here would let the two drift apart silently - packs would
     * quietly reappear in the catalogue with nothing failing to show it.
     */
    protected const CATEGORY_SLUG = Category::APP_ONLY_SLUGS[0];

    public function index()
    {
        $packs = AvatarPack::with(['product.versions'])
            ->join('products', 'products.id', '=', 'avatar_packs.product_id')
            ->orderBy('products.name')
            ->select('avatar_packs.*')
            ->paginate(30);

        return view('admin.packs.index', compact('packs'));
    }

    public function create()
    {
        return view('admin.packs.form', [
            'pack' => new AvatarPack(['kind' => AvatarPack::KIND_CHARACTER, 'is_active' => true]),
            'characters' => $this->characterOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePack($request);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => $data['name_th'],
            'slug' => $this->uniqueSlug($data['pack_id']),
            'description' => $data['description'] ?? $data['name_th'],
            'price' => $data['price'],
            'stock' => 0,
            'requires_license' => true,
            'is_active' => $data['is_active'],
        ]);

        $pack = AvatarPack::create([
            'product_id' => $product->id,
            'pack_id' => $data['pack_id'],
            'kind' => $data['kind'],
            'requires' => $data['requires'],
            'name_en' => $data['name_en'],
            'summary_th' => $data['summary_th'],
            'summary_en' => $data['summary_en'],
            'is_active' => $data['is_active'],
        ]);

        if ($request->hasFile('preview')) {
            $pack->update(['preview_path' => $this->storePreview($request, $pack)]);
        }

        return redirect()
            ->route('admin.packs.edit', $pack)
            ->with('success', ($pack->isKnowledge() ? 'เพิ่มแพ็กความรู้แล้ว' : 'เพิ่มชุดแล้ว') . ' — ขั้นต่อไปคืออัปโหลดไฟล์ .zip');
    }

    public function edit(AvatarPack $pack)
    {
        $pack->load('product.versions');

        return view('admin.packs.form', [
            'pack' => $pack,
            'characters' => $this->characterOptions($pack->id),
        ]);
    }

    public function update(Request $request, AvatarPack $pack)
    {
        $data = $this->validatePack($request, $pack);

        $pack->product->update([
            'name' => $data['name_th'],
            'description' => $data['description'] ?? $data['name_th'],
            'price' => $data['price'],
            'is_active' => $data['is_active'],
        ]);

        $pack->update([
            'pack_id' => $data['pack_id'],
            'kind' => $data['kind'],
            'requires' => $data['requires'],
            'name_en' => $data['name_en'],
            'summary_th' => $data['summary_th'],
            'summary_en' => $data['summary_en'],
            'is_active' => $data['is_active'],
        ]);

        if ($request->hasFile('preview')) {
            $pack->update(['preview_path' => $this->storePreview($request, $pack)]);
        }

        return back()->with('success', 'บันทึกแล้ว');
    }

    /**
     * Take the .zip an admin uploaded and turn it into the pack's file.
     *
     * The id inside the zip is checked against the id sold here before
     * anything is stored. They are two different things that must agree:
     * the app installs by the id in pack.json and reports ownership by the
     * id in the catalogue. A mismatch is invisible to everyone until a
     * customer pays and the app quietly refuses to see what it downloaded.
     *
     * A knowledge pack is a different format - manifest.json, not pack.json -
     * and goes through every check the app makes before installing one, so
     * nothing the app would refuse can go on sale. Its version is read from
     * the manifest rather than typed in, so the catalogue cannot disagree
     * with the file: the app compares the two to offer an update.
     */
    public function uploadFile(Request $request, AvatarPack $pack)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:307200',
            'version' => 'nullable|string|max:50',
        ]);

        $upload = $request->file('file');
        $sha256 = hash_file('sha256', $upload->getRealPath());

        if ($pack->isKnowledge()) {
            try {
                $manifest = app(KnowledgePackValidator::class)->validate($upload->getRealPath(), $pack->pack_id);
            } catch (KnowledgePackException $e) {
                throw ValidationException::withMessages(['file' => $e->getMessage()]);
            }

            $version = $manifest['version'];

            // The app offers an update only when the version changes. A different file under a
            // version already sold would reach new buyers and never reach anyone who installed it.
            $sameVersion = $pack->product->versions()
                ->where('version', $version)
                ->whereNotNull('storage_path')
                ->first();

            if ($sameVersion && $sameVersion->sha256 !== $sha256) {
                throw ValidationException::withMessages([
                    'file' => sprintf(
                        'เวอร์ชัน %s มีไฟล์อื่นอยู่แล้ว — ถ้าอัปโหลดทับ แอปที่ติดตั้งรุ่นนี้ไปแล้วจะไม่เห็นปุ่มอัปเดต ให้เพิ่มเลข version ใน manifest.json ก่อน',
                        $version
                    ),
                ]);
            }
        } else {
            $insideId = $this->packIdInsideZip($upload->getRealPath());

            if ($insideId === null) {
                throw ValidationException::withMessages([
                    'file' => 'ในไฟล์ zip ไม่มี pack.json — แอปจะแตกไฟล์ไม่ออก',
                ]);
            }

            if ($insideId !== $pack->pack_id) {
                throw ValidationException::withMessages([
                    'file' => sprintf(
                        'id ในไฟล์คือ "%s" แต่ชุดนี้ขายในชื่อ "%s" — แอปจะโหลดมาแล้วจำไม่ได้ว่าซื้อแล้ว',
                        $insideId,
                        $pack->pack_id
                    ),
                ]);
            }

            $version = $request->input('version') ?: $this->nextVersion($pack->product);
        }

        $disk = Storage::disk(config('packs.disk', 'local'));
        $path = 'packs/' . $pack->pack_id . '/' . Str::uuid() . '.zip';
        $disk->put($path, file_get_contents($upload->getRealPath()));

        // Only one version of a pack is ever offered - the app has no version
        // picker - so the previous one is retired rather than left active.
        $pack->product->versions()->update(['is_active' => false]);

        // An upload under a version that already has a file replaces that file;
        // the old one would otherwise sit on disk with nothing pointing at it.
        $replaced = $pack->product->versions()->where('version', $version)->value('storage_path');

        ProductVersion::updateOrCreate(
            ['product_id' => $pack->product_id, 'version' => $version],
            [
                'download_filename' => $pack->pack_id . '.zip',
                'storage_path' => $path,
                'file_size' => $upload->getSize(),
                'sha256' => $sha256,
                'is_active' => true,
                'synced_at' => now(),
            ]
        );

        if ($replaced && $replaced !== $path) {
            $disk->delete($replaced);
        }

        return back()->with('success', $pack->isKnowledge()
            ? sprintf('อัปโหลดแล้ว เวอร์ชัน %s (จาก manifest.json) · หมวด %s', $version, $manifest['category'])
            : 'อัปโหลดแล้ว เวอร์ชัน ' . $version);
    }

    public function toggle(AvatarPack $pack)
    {
        $pack->update(['is_active' => ! $pack->is_active]);

        return back()->with('success', $pack->is_active ? 'เปิดขายแล้ว' : 'ปิดขายแล้ว');
    }

    /**
     * Remove the pack, its product and its files.
     *
     * Refuses once anyone has bought it: the license rows point at the
     * product, and deleting it would leave paying customers holding a
     * license for something that no longer exists.
     */
    public function destroy(AvatarPack $pack)
    {
        if ($pack->product->licenseKeys()->exists()) {
            return back()->with('error', 'ลบไม่ได้ — มีคนซื้อชุดนี้ไปแล้ว ปิดขายแทนได้');
        }

        $disk = Storage::disk(config('packs.disk', 'local'));

        foreach ($pack->product->versions as $version) {
            if ($version->storage_path) {
                $disk->delete($version->storage_path);
            }
        }

        $product = $pack->product;
        $pack->delete();
        $product->delete();

        return redirect()->route('admin.packs.index')->with('success', 'ลบชุดแล้ว');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePack(Request $request, ?AvatarPack $pack = null): array
    {
        $data = $request->validate([
            // Same charset the app enforces when it turns an id into a folder
            // name. Anything else is a pack the app will refuse to install.
            'pack_id' => [
                'required',
                'string',
                'max:48',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('avatar_packs', 'pack_id')->ignore($pack?->id),
            ],
            'name_th' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'summary_th' => 'nullable|string|max:255',
            'summary_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'kind' => ['required', Rule::in(AvatarPack::KINDS)],
            'requires' => 'nullable|string|max:48',
            'price' => 'required|numeric|min:0',
            'preview' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'pack_id.regex' => 'รหัสชุดใช้ได้เฉพาะ a-z 0-9 จุด ขีดล่าง และขีดกลาง',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['requires'] = $data['kind'] === AvatarPack::KIND_OUTFIT
            ? ($data['requires'] ?: null)
            : null;

        if ($data['requires'] !== null && $data['requires'] === $data['pack_id']) {
            throw ValidationException::withMessages([
                'requires' => 'ชุดต้องการตัวเองไม่ได้',
            ]);
        }

        $knowledge = $data['kind'] === AvatarPack::KIND_KNOWLEDGE;

        // The app refuses a knowledge pack id starting with "_" (its own file names) or "."
        if ($knowledge && ! preg_match('/^[a-z0-9]/', $data['pack_id'])) {
            throw ValidationException::withMessages([
                'pack_id' => 'รหัสแพ็กความรู้ต้องขึ้นต้นด้วย a-z หรือ 0-9 — แอปไม่รับรหัสที่ขึ้นต้นด้วย . หรือ _',
            ]);
        }

        // A knowledge zip and an outfit zip are different formats. Switching across that line with
        // a file already uploaded puts a file on sale that the app cannot install as the new kind.
        if ($pack && $pack->isKnowledge() !== $knowledge
            && $pack->product?->versions()->whereNotNull('storage_path')->exists()) {
            throw ValidationException::withMessages([
                'kind' => 'เปลี่ยนชนิดระหว่างแพ็กความรู้กับชุดไม่ได้เมื่ออัปโหลดไฟล์แล้ว — ไฟล์เดิมเป็นคนละรูปแบบ ลูกค้าจะจ่ายเงินแล้วติดตั้งไม่ได้ ให้สร้างรายการใหม่แทน',
            ]);
        }

        foreach (['summary_th', 'summary_en'] as $field) {
            $summary = trim((string) ($data[$field] ?? ''));
            $data[$field] = $summary === '' ? null : $summary;
        }

        return $data;
    }

    /**
     * Read the id the zip declares, or null if it declares none.
     */
    protected function packIdInsideZip(string $path): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return null;
        }

        // Packing a folder on Windows wraps everything one level deep; the
        // app unwraps that, so accept it here too rather than rejecting a
        // zip that will work perfectly well.
        $json = $zip->getFromName('pack.json');

        if ($json === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^[^/]+/pack\.json$#', $name)) {
                    $json = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if (! is_string($json)) {
            return null;
        }

        $decoded = json_decode($json, true);
        $id = is_array($decoded) ? ($decoded['id'] ?? null) : null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    protected function category(): Category
    {
        return Category::firstOrCreate(
            ['slug' => self::CATEGORY_SLUG],
            [
                'name' => 'ชุดตัวมายด์ (GigGok)',
                'description' => 'ชุดแต่งตัว ตัวละคร และของประดับเวทีสำหรับแอป GigGok',
                'is_active' => true,
            ]
        );
    }

    protected function uniqueSlug(string $packId): string
    {
        $base = Str::slug('pack-' . $packId);
        $slug = $base;
        $n = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    protected function nextVersion(Product $product): string
    {
        $count = $product->versions()->count();

        return '1.' . $count . '.0';
    }

    protected function storePreview(Request $request, AvatarPack $pack): string
    {
        // This lands in the web root, so the extension comes from the file's content
        // (checked against the rule above), never from the name it was uploaded with.
        $extension = $request->file('preview')->guessExtension();
        abort_unless(in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true), 422);

        // pack_id may contain dots; one extension per file name, so they go.
        $name = 'pack-' . preg_replace('/[^a-z0-9_-]/i', '-', $pack->pack_id) . '-' . Str::random(6) . '.' . $extension;

        $request->file('preview')->move(public_path('uploads/packs'), $name);

        return 'uploads/packs/' . $name;
    }

    /**
     * Characters an outfit can hang off.
     *
     * @return Collection<int, AvatarPack>
     */
    protected function characterOptions(?int $exceptId = null)
    {
        return AvatarPack::with('product')
            ->where('kind', AvatarPack::KIND_CHARACTER)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get();
    }
}
