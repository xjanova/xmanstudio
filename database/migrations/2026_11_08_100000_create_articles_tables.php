<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The knowledge base (/blog): IT articles written from what Thai people are searching for
 * (Google Trends) and from what we sell, so Google sees a site that keeps teaching, and a
 * reader who learns something meets the product that solves it.
 *
 * Status and source are plain strings, not enums: MySQL strict mode rejects a value the
 * enum never listed while SQLite lets it through, and that gap has already cost a 500 here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('title', 200);
            $table->string('excerpt', 320)->nullable();           // meta description + card text
            $table->longText('body');                               // sanitised HTML
            $table->string('category', 40)->default('it');          // Article::CATEGORIES
            $table->json('tags')->nullable();
            $table->string('focus_keyword', 120)->nullable();
            $table->string('cover_image', 255)->nullable();         // path under the public disk
            $table->string('status', 20)->default('draft');         // draft | published | archived
            $table->timestamp('published_at')->nullable();
            $table->string('source', 20)->default('manual');        // manual | trend | catalogue
            $table->string('source_topic', 200)->nullable();        // the trend / product it came from
            $table->json('related')->nullable();                    // [{type, slug, name, url}] products/services it sells
            $table->string('ai_model', 80)->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('reading_minutes')->default(1);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('rating_sum')->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            // Posting to the company Facebook page (the FB page-poster feature owns these two).
            $table->string('facebook_post_id', 100)->nullable();
            $table->timestamp('facebook_posted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category');
        });

        Schema::create('article_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // One vote per reader: the member id when signed in, else a keyed hash of IP + browser.
            $table->string('voter_hash', 64);
            $table->unsignedTinyInteger('stars');
            $table->timestamps();

            $table->unique(['article_id', 'voter_hash']);
        });

        // Topics already written, so a trend that stays hot for a week is not written seven times.
        Schema::create('article_topics', function (Blueprint $table) {
            $table->id();
            $table->string('topic_key', 191)->unique();             // normalised topic
            $table->string('topic', 200);
            $table->string('source', 20);                           // trend | catalogue
            $table->string('decision', 20);                         // written | skipped | failed
            $table->string('reason', 255)->nullable();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_topics');
        Schema::dropIfExists('article_ratings');
        Schema::dropIfExists('articles');
    }
};
