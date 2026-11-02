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

    /**
     * Text for Mind to search on the device: manifest.json + content/*.md,
     * never code. Its zip is a different format from the other kinds, which
     * all carry a pack.json - see KnowledgePackValidator.
     */
    public const KIND_KNOWLEDGE = 'knowledge';

    public const KINDS = [
        self::KIND_CHARACTER,
        self::KIND_OUTFIT,
        self::KIND_PROP,
        self::KIND_KNOWLEDGE,
    ];

    protected $fillable = [
        'product_id',
        'pack_id',
        'kind',
        'requires',
        'name_en',
        'summary_th',
        'summary_en',
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

    public function isKnowledge(): bool
    {
        return $this->kind === self::KIND_KNOWLEDGE;
    }

    /**
     * The shape the app expects from GET /api/packs.
     *
     * Size, hash and version come from the version row, not from here: they
     * describe the file, and the file can be replaced without the listing
     * changing. For a knowledge pack that version string was read out of the
     * zip's own manifest.json at upload, so the catalogue cannot disagree
     * with the file - the app compares the two to offer an update.
     */
    public function toCatalogueArray(): array
    {
        $product = $this->product;
        $version = $product?->latestVersion();
        $file = $version?->storage_path ? $version : null;
        $summaryTh = trim((string) $this->summary_th);
        $summaryEn = trim((string) $this->summary_en);

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
            // null = no file on sale yet, so nothing to compare an installed pack against
            'version' => $file?->version,
            'summary' => $summaryTh === '' && $summaryEn === '' ? null : [
                'th' => $summaryTh !== '' ? $summaryTh : $summaryEn,
                'en' => $summaryEn !== '' ? $summaryEn : $summaryTh,
            ],
        ];
    }
}
