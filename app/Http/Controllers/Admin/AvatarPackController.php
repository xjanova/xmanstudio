<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvatarPack;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVersion;
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
            'is_active' => $data['is_active'],
        ]);

        if ($request->hasFile('preview')) {
            $pack->update(['preview_path' => $this->storePreview($request, $pack)]);
        }

        return redirect()
            ->route('admin.packs.edit', $pack)
            ->with('success', 'เพิ่มชุดแล้ว — ขั้นต่อไปคืออัปโหลดไฟล์ .zip');
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
     */
    public function uploadFile(Request $request, AvatarPack $pack)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:307200',
            'version' => 'nullable|string|max:50',
        ]);

        $upload = $request->file('file');
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

        $disk = Storage::disk(config('packs.disk', 'local'));
        $path = 'packs/' . $pack->pack_id . '/' . Str::uuid() . '.zip';
        $disk->put($path, file_get_contents($upload->getRealPath()));

        $version = $request->input('version') ?: $this->nextVersion($pack->product);

        // Only one version of a pack is ever offered - the app has no version
        // picker - so the previous one is retired rather than left active.
        $pack->product->versions()->update(['is_active' => false]);

        ProductVersion::updateOrCreate(
            ['product_id' => $pack->product_id, 'version' => $version],
            [
                'download_filename' => $pack->pack_id . '.zip',
                'storage_path' => $path,
                'file_size' => $upload->getSize(),
                'sha256' => hash_file('sha256', $upload->getRealPath()),
                'is_active' => true,
                'synced_at' => now(),
            ]
        );

        return back()->with('success', 'อัปโหลดแล้ว เวอร์ชัน ' . $version);
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
            'description' => 'nullable|string',
            'kind' => ['required', Rule::in(AvatarPack::KINDS)],
            'requires' => 'nullable|string|max:48',
            'price' => 'required|numeric|min:0',
            'preview' => 'nullable|image|max:4096',
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
        $name = 'pack-' . $pack->pack_id . '-' . Str::random(6) . '.'
            . $request->file('preview')->getClientOriginalExtension();

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
