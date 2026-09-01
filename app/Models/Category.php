<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /**
     * Categories whose products are sold inside an app, never on the website.
     *
     * Avatar packs reuse the products table so they inherit the cart, coupons,
     * payment methods and license issuing. The side effect is that they also
     * inherited the public catalogue, where a GigGok outfit sat next to the
     * enterprise software and meant nothing to a website visitor.
     *
     * This hides them from BROWSING only. A pack's own product page stays
     * reachable, because that is the page the app sends a buyer to and it is
     * where paying actually happens - see PackShopController.
     */
    public const APP_ONLY_SLUGS = ['giggok-packs'];

    /**
     * Categories a website visitor is allowed to browse.
     */
    public function scopeOnWebsite($query)
    {
        return $query->whereNotIn('slug', self::APP_ONLY_SLUGS);
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
