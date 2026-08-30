<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avatar packs for the GigGok app (outfits, characters, stage props).
     *
     * A pack IS a product: price, ownership and licensing all come from the
     * existing product/license tables, so admins add and price packs with the
     * screens they already use. This table only holds what a pack has and a
     * regular product does not - the id the app matches against pack.json,
     * what kind of pack it is, and what it needs installed first.
     */
    public function up(): void
    {
        Schema::create('avatar_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Must equal the "id" inside the zip's pack.json. The app keys its
            // installed packs by this, not by product id, so a mismatch means
            // the app can never tell it already owns what it just downloaded.
            $table->string('pack_id', 48);

            // character = a whole new secretary, outfit = clothes for an
            // existing one, prop = a 3D object for the stage.
            $table->string('kind', 20)->default('character');

            // pack_id of the character this outfit belongs to. Null for packs
            // that stand alone.
            $table->string('requires', 48)->nullable();

            // Products carry a single Thai name; the app asks for both.
            $table->string('name_en')->nullable();

            $table->string('preview_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('product_id');
            $table->unique('pack_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_packs');
    }
};
