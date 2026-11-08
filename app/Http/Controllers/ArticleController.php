<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleRating;
use App\Support\ArticleHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * The knowledge base at /blog — what Google reads to judge whether this site teaches
 * anything, and where a reader who learned something meets what we sell (docs/ARTICLES.md).
 */
class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        if (! is_string($category) || ! array_key_exists($category, Article::CATEGORIES)) {
            $category = null;
        }

        $articles = Article::published()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        // Category tabs only for categories that have something in them.
        $counts = Article::published()
            ->select('category', DB::raw('count(*) as n'))
            ->groupBy('category')
            ->pluck('n', 'category');

        return view('articles.index', compact('articles', 'category', 'counts'));
    }

    public function show(Request $request, string $slug)
    {
        $article = Article::where('slug', $slug)->firstOrFail();

        // A draft is visible to admins (preview), never to anyone else.
        if (! $article->isLive() && ! $request->user()?->isAdmin()) {
            abort(404);
        }

        // One view per reader per session: a refresh is not a reader.
        $seenKey = 'article_seen.' . $article->id;
        if ($article->isLive() && $request->hasSession() && ! $request->session()->has($seenKey)) {
            $request->session()->put($seenKey, true);
            Article::whereKey($article->id)->increment('views');
        }

        $related = Article::published()
            ->whereKeyNot($article->id)
            ->where('category', $article->category)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        if ($related->count() < 3) {
            $related = $related->concat(
                Article::published()
                    ->whereKeyNot($article->id)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->orderByDesc('published_at')
                    ->limit(3 - $related->count())
                    ->get()
            );
        }

        $myStars = ArticleRating::where('article_id', $article->id)
            ->where('voter_hash', $this->voterHash($request))
            ->value('stars');

        $headings = ArticleHtml::headings($article->body);

        return view('articles.show', compact('article', 'related', 'myStars', 'headings'));
    }

    /**
     * A reader gives the article 1–5 stars. One vote per reader (member id, or a keyed hash of
     * IP + browser for guests); voting again changes the vote, it does not add another.
     */
    public function rate(Request $request, string $slug): JsonResponse
    {
        $article = Article::published()->where('slug', $slug)->first();
        if (! $article) {
            return response()->json(['success' => false, 'message' => 'ไม่พบบทความนี้ หรือบทความยังไม่เผยแพร่'], 404);
        }

        $stars = (int) $request->input('stars');
        if ($stars < 1 || $stars > 5) {
            return response()->json(['success' => false, 'message' => 'กรุณาเลือกดาว 1 ถึง 5 ดวง'], 422);
        }

        $hash = $this->voterHash($request);

        DB::transaction(function () use ($article, $request, $hash, $stars) {
            ArticleRating::updateOrCreate(
                ['article_id' => $article->id, 'voter_hash' => $hash],
                ['stars' => $stars, 'user_id' => $request->user()?->id],
            );

            // Recount from the votes themselves: two votes arriving together cannot drift the total.
            $agg = ArticleRating::where('article_id', $article->id)
                ->selectRaw('count(*) as n, coalesce(sum(stars), 0) as s')
                ->first();

            Article::whereKey($article->id)->update([
                'rating_count' => (int) $agg->n,
                'rating_sum' => (int) $agg->s,
            ]);
        });

        $article->refresh();

        return response()->json([
            'success' => true,
            'message' => 'ขอบคุณที่ให้คะแนนค่ะ',
            'average' => $article->ratingAverage(),
            'count' => $article->rating_count,
            'mine' => $stars,
        ]);
    }

    /** RSS 2.0 of the latest 30 articles — feed readers, and Facebook/LINE auto-posters. */
    public function feed(): Response
    {
        $articles = Article::published()->orderByDesc('published_at')->limit(30)->get();

        return response()
            ->view('articles.feed', compact('articles'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    private function voterHash(Request $request): string
    {
        if ($request->user()) {
            return hash('sha256', 'user:' . $request->user()->id);
        }

        return hash_hmac('sha256', $request->ip() . '|' . substr((string) $request->userAgent(), 0, 300), (string) config('app.key'));
    }
}
