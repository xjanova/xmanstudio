<?php

namespace App\Models;

use App\Support\ReleaseNotes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'version',
        'github_release_id',
        'github_release_url',
        'download_url',
        'download_filename',
        'storage_path',
        'file_size',
        'sha256',
        'assets',
        'changelog',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'github_release_id' => 'integer',
        'file_size' => 'integer',
        'assets' => 'array',
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * changelog ในรูปที่ลูกค้าเห็นได้ ทุกที่ที่อ่าน — หน้าสินค้า หน้าโหลด update/check และ API รายการเวอร์ชัน
     *
     * ตอน sync ก็ล้างลิงก์ GitHub ไว้แล้ว (GithubReleaseService) แต่เวอร์ชันเก่าที่ไม่ถูก sync ซ้ำ
     * (API รายการเวอร์ชันคืนย้อนหลังถึง 10 ตัว) และข้อความที่ admin พิมพ์เอง ไม่เคยผ่านตรงนั้น
     * ห้ามให้ลูกค้ารู้ repo (กฎเจ้าของ 2026-09-24) — จึงกรองอีกชั้นตอนอ่าน ค่าที่สะอาดแล้วผ่านซ้ำก็เท่าเดิม
     */
    protected function changelog(): Attribute
    {
        return Attribute::get(fn (?string $value) => ReleaseNotes::forCustomers($value, ReleaseNotes::studioAccounts()));
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    /**
     * ไฟล์หนึ่งใน release ของ GitHub ที่เวอร์ชันนี้มาจาก ตามชื่อ (ไม่สนตัวพิมพ์)
     *
     * null = release ไม่มีไฟล์นี้ หรือเวอร์ชันถูก sync ไว้ก่อนเริ่มเก็บรายการไฟล์ (assets = null — read-through เติมให้)
     *
     * @return ?array{name: string, size: ?int, sha256: ?string, download_url: ?string, api_url: ?string}
     */
    public function releaseAsset(string $name): ?array
    {
        foreach ($this->assets ?? [] as $asset) {
            if (is_array($asset) && strcasecmp((string) ($asset['name'] ?? ''), $name) === 0) {
                return $asset;
            }
        }

        return null;
    }

    /**
     * เวอร์ชันเดียวกันนี้ แต่ไฟล์เป็น $asset แทนไฟล์หลัก — ให้ ReleaseDownloadStreamer ส่งไฟล์อื่นของ release เดียวกัน
     * (ตัวติดตั้ง Windows ของ LocalVPN คู่กับ APK) ด้วยกฎเดียวกันทุกข้อ: token, ลิงก์สำรอง, HEAD, ขนาด
     *
     * ไม่ใช่แถวใน DB — exists = false แต่ใช้ id เดิมเพื่อนับยอดโหลดเข้าเวอร์ชันนี้ save() หลุดมาก็ชน primary key
     * ไม่มีทางเขียนข้อมูลตัวติดตั้งทับ APK
     *
     * @param  array{name: string, size?: ?int, sha256?: ?string, download_url?: ?string, api_url?: ?string}  $asset
     */
    public function withReleaseAsset(array $asset): self
    {
        return (new static)->forceFill([
            'id' => $this->id,
            'product_id' => $this->product_id,
            'version' => $this->version,
            'github_release_url' => $asset['api_url'] ?? null,
            'download_url' => $asset['download_url'] ?? null,
            'download_filename' => $asset['name'],
            'file_size' => $asset['size'] ?? null,
            'sha256' => $asset['sha256'] ?? null,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;

        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
