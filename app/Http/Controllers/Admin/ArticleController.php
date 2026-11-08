<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateArticlesJob;
use App\Models\Article;
use App\Models\Setting;
use App\Services\Articles\ArticleImages;
use App\Services\Articles\ArticleWriter;
use App\Support\ArticleHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * /admin/articles — the knowledge base: what the AI writer published, its switches, and a
 * plain editor for fixing or writing an article by hand.
 */
class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $articles = Article::query()
            ->when(in_array($status, [Article::STATUS_DRAFT, Article::STATUS_PUBLISHED, Article::STATUS_ARCHIVED], true), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'published' => Article::published()->count(),
            'draft' => Article::where('status', Article::STATUS_DRAFT)->count(),
            'views' => (int) Article::sum('views'),
            'ratings' => (int) Article::sum('rating_count'),
        ];

        $settings = [
            'auto' => ArticleWriter::autoEnabled(),
            'publish' => ArticleWriter::autoPublish(),
            'per_run' => ArticleWriter::perRun(),
            'images' => (bool) Setting::getValue('articles_images_enabled', true),
            'has_key' => ArticleImages::hasKey(),
        ];

        $lastRun = Cache::get(GenerateArticlesJob::LAST_RUN_KEY);

        return view('admin.articles.index', compact('articles', 'stats', 'settings', 'lastRun', 'status'));
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'per_run' => 'required|integer|min:1|max:5',
        ], [], ['per_run' => 'จำนวนบทความต่อรอบ']);

        Setting::setValue('articles_auto_enabled', $request->boolean('auto') ? '1' : '0', 'boolean', 'articles');
        Setting::setValue('articles_auto_publish', $request->boolean('publish') ? '1' : '0', 'boolean', 'articles');
        Setting::setValue('articles_per_run', (string) $data['per_run'], 'integer', 'articles');
        Setting::setValue('articles_images_enabled', $request->boolean('images') ? '1' : '0', 'boolean', 'articles');

        return back()->with('success', 'บันทึกการตั้งค่าบทความอัตโนมัติแล้ว');
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'count' => 'nullable|integer|min:1|max:3',
            'topic' => 'nullable|string|max:190',
        ], [], ['count' => 'จำนวน', 'topic' => 'หัวข้อ']);

        $running = Cache::get(GenerateArticlesJob::LAST_RUN_KEY);
        if (($running['state'] ?? null) === 'running' && now()->diffInMinutes($running['at'] ?? now()) < 15) {
            return back()->with('error', 'กำลังเขียนบทความรอบก่อนอยู่ กรุณารอให้เสร็จก่อน (ไม่เกิน 15 นาที)');
        }

        Cache::put(GenerateArticlesJob::LAST_RUN_KEY, ['state' => 'running', 'at' => now()->toIso8601String(), 'results' => []], now()->addDay());
        GenerateArticlesJob::dispatch((int) ($data['count'] ?? 1), $data['topic'] ?? null);

        return back()->with('success', 'สั่งเขียนบทความแล้ว ใช้เวลาประมาณ 1–5 นาทีต่อเรื่อง รีเฟรชหน้านี้เพื่อดูผล');
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article(['category' => 'it', 'status' => Article::STATUS_DRAFT])]);
    }

    public function store(Request $request)
    {
        $article = new Article;
        $this->fill($article, $request);
        $article->source = 'manual';
        $article->save();

        return redirect()->route('admin.articles.edit', $article->id)->with('success', 'สร้างบทความแล้ว');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $this->fill($article, $request);
        $article->save();

        return back()->with('success', 'บันทึกบทความแล้ว');
    }

    public function destroy(Article $article)
    {
        if ($article->cover_image && ! str_starts_with($article->cover_image, 'http')) {
            Storage::disk('public')->delete($article->cover_image);
        }
        // The pictures drawn for it (ArticleImages) live in their own folder.
        Storage::disk('public')->deleteDirectory('articles/' . Str::slug($article->slug));
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'ลบบทความ "' . $article->title . '" แล้ว');
    }

    private function fill(Article $article, Request $request): void
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('articles', 'slug')->ignore($article->id)],
            'excerpt' => 'nullable|string|max:300',
            'category' => ['required', Rule::in(array_keys(Article::CATEGORIES))],
            'tags' => 'nullable|string|max:300',
            'focus_keyword' => 'nullable|string|max:120',
            'body_markdown' => 'required|string|min:50',
            'status' => ['required', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED, Article::STATUS_ARCHIVED])],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'slug.regex' => 'ลิงก์ (slug) ใช้ได้เฉพาะ a-z ตัวเลข และขีด -',
        ], [
            'title' => 'ชื่อบทความ', 'excerpt' => 'คำอธิบาย', 'category' => 'หมวด', 'body_markdown' => 'เนื้อหา', 'status' => 'สถานะ', 'cover' => 'ภาพปก',
        ]);

        $html = ArticleHtml::fromMarkdown($data['body_markdown']);
        $words = ArticleHtml::wordCount($html);

        $article->fill([
            'title' => $data['title'],
            'slug' => $data['slug'] ?: ($article->slug ?: ArticleWriter::uniqueSlug($data['title'])),
            'excerpt' => $data['excerpt'] ?: Str::limit(ArticleHtml::text($html), 150),
            'category' => $data['category'],
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? ''))))),
            'focus_keyword' => $data['focus_keyword'] ?? null,
            'body_markdown' => $data['body_markdown'],
            'body' => $html,
            'word_count' => $words,
            'reading_minutes' => ArticleHtml::readingMinutes($words),
            'status' => $data['status'],
        ]);

        if ($data['status'] === Article::STATUS_PUBLISHED && ! $article->published_at) {
            $article->published_at = now();
        }

        if ($request->hasFile('cover')) {
            $ext = $request->file('cover')->extension();
            $path = $request->file('cover')->storeAs('articles', Str::random(24) . '.' . $ext, 'public');
            if ($article->cover_image && ! str_starts_with($article->cover_image, 'http')) {
                Storage::disk('public')->delete($article->cover_image);
            }
            $article->cover_image = $path;
        }
    }
}
