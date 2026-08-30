<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pack-specific detail for a product sold to the GigGok app.
 *
 * Everything a pack shares with any other product - price, license, orders,
 * versions, download logging - lives on the product. Only the parts the app
 * needs and a normal product has no place for are here.
 */
class AvatarPack extends Model
{
    use HasFactory;

    public const KIND_CHARACTER = 'character';

    public const KIND_OUTFIT = 'outfit';

    public const KIND_PROP = 'prop';

    public const KINDS = [
        self::KIND_CHARACTER,
        self::KIND_OUTFIT,
        self::KIND_PROP,
    ];

    protected $fillable = [
        'product_id',
        'pack_id',
        'kind',
        'requires',
        'name_en',
        'preview_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * The shape the app expects from GET /api/packs.
     *
     * Size and hash come from the version, not from here: they describe the
     * file, and the file can be replaced without the listing changing.
     */
    public function toCatalogueArray(): array
    {
        $product = $this->product;
        $version = $product?->latestVersion();

        return [
            'id' => $this->pack_id,
            'name' => [
                'th' => $product?->name ?? $this->pack_id,
                'en' => $this->name_en ?: ($product?->name ?? $this->pack_id),
            ],
            'kind' => $this->kind,
            'price' => (float) ($product?->price ?? 0),
            'currency' => 'THB',
            'sizeBytes' => (int) ($version?->file_size ?? 0),
            'preview' => $this->preview_path ? asset($this->preview_path) : null,
            'requires' => $this->requires,
        ];
    }
}
