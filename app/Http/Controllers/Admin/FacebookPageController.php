<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use App\Services\ArticleFacebookPoster;
use App\Services\FacebookPageException;
use App\Services\FacebookPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Settings for the company Facebook page (XmanEnterprise) that new knowledge-base articles are
 * shared to, plus the "post this article now" action the article admin can link to:
 *
 *   <form method="POST" action="{{ route('admin.articles.facebook', $article) }}">@csrf
 *       <input type="hidden" name="force" value="1">   {{-- only to post a second time --}}
 *   </form>
 *
 * Tokens are write-only here: the screen shows whether one is stored, never its value.
 */
class FacebookPageController extends Controller
{
    public function __construct(private readonly FacebookPageService $facebook) {}

    public function index(): View
    {
        $recent = collect();
        try {
            if (Schema::hasTable('articles')) {
                $recent = Article::published()->latest('published_at')->limit(10)
                    ->get(['id', 'slug', 'title', 'status', 'published_at', 'facebook_post_id', 'facebook_posted_at']);
            }
        } catch (\Throwable) {
        }

        return view('admin.facebook-page.index', [
            'pageId' => $this->facebook->pageId(),
            'pageName' => $this->facebook->pageName(),
            'appId' => $this->facebook->appId(),
            'hasToken' => $this->facebook->hasPageToken(),
            'hasAppSecret' => $this->facebook->hasAppSecret(),
            'autopost' => $this->facebook->autopostEnabled(),
            'expiresAt' => $this->facebook->tokenExpiresAt(),
            'connectedAt' => Setting::getValue(FacebookPageService::KEY_CONNECTED_AT),
            'recent' => $recent,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'page_id' => ['nullable', 'regex:/^\d{5,30}$/'],
            'app_id' => ['nullable', 'regex:/^\d{5,30}$/'],
            'app_secret' => ['nullable', 'string', 'max:100'],
            'token' => ['nullable', 'string', 'max:2000'],
            'autopost' => ['nullable', 'boolean'],
        ], [
            'page_id.regex' => 'Page ID ต้องเป็นตัวเลขล้วน (ดูได้จากหน้าเพจ > เกี่ยวกับ > ความโปร่งใสของเพจ)',
            'app_id.regex' => 'App ID ต้องเป็นตัวเลขล้วน',
        ]);

        $group = FacebookPageService::SETTINGS_GROUP;
        $pageId = trim((string) ($data['page_id'] ?? ''));
        $token = trim((string) ($data['token'] ?? ''));

        // A token belongs to one page: switching the page without a new token would leave the
        // old page's token posting to... nothing (Graph refuses), silently.
        if ($pageId !== '' && $pageId !== $this->facebook->pageId() && $this->facebook->hasPageToken() && $token === '') {
            return back()->withInput($request->except(['token', 'app_secret']))
                ->with('error', 'เปลี่ยน Page ID แล้วต้องวาง token ของเพจใหม่ด้วย — token เดิมเป็นของเพจ ' . ($this->facebook->pageName() ?: $this->facebook->pageId()));
        }

        Setting::setValue(FacebookPageService::KEY_AUTOPOST, $request->boolean('autopost'), 'boolean', $group);
        if (filled($data['app_id'] ?? null)) {
            Setting::setValue(FacebookPageService::KEY_APP_ID, $data['app_id'], 'string', $group);
        }
        if (filled($data['app_secret'] ?? null)) {
            Setting::setValue(FacebookPageService::KEY_APP_SECRET, trim($data['app_secret']), 'string', $group);
        }
        if ($pageId !== '' && $token === '') {
            Setting::setValue(FacebookPageService::KEY_PAGE_ID, $pageId, 'string', $group);
        }

        if ($token === '') {
            return redirect()->route('admin.facebook-page.index')->with('success', 'บันทึกการตั้งค่าแล้ว');
        }

        try {
            $result = $this->facebook->connect($token, $pageId !== '' ? $pageId : null);
        } catch (FacebookPageException $e) {
            return back()->withInput($request->except(['token', 'app_secret']))
                ->with('error', 'เชื่อมเพจไม่สำเร็จ: ' . $e->getMessage());
        }

        $redirect = redirect()->route('admin.facebook-page.index')->with('success', 'เชื่อมเพจ "' . $result['page_name'] . '" แล้ว ('
            . ($result['kind'] === 'user' ? 'แลกจาก User Token' : 'ใช้ Page Token ที่วางมา') . ', '
            . ($result['expires_at'] ? 'token หมดอายุ ' . $result['expires_at']->timezone('Asia/Bangkok')->format('d/m/Y H:i') : 'token ไม่มีวันหมดอายุ') . ')');

        return $result['warnings'] === [] ? $redirect : $redirect->with('error', implode(' · ', $result['warnings']));
    }

    public function test(): RedirectResponse
    {
        try {
            $page = $this->facebook->check();
        } catch (FacebookPageException $e) {
            return back()->with('error', 'ทดสอบไม่ผ่าน: ' . $e->getMessage());
        }

        return back()->with('success', 'เชื่อมต่อเพจ "' . $page['page_name'] . '" ได้ปกติ'
            . ($page['followers'] !== null ? ' · ผู้ติดตาม ' . number_format($page['followers']) . ' คน' : '')
            . ($page['inspected']
                ? ' · token ' . ($page['expires_at'] ? 'หมดอายุ ' . $page['expires_at']->timezone('Asia/Bangkok')->format('d/m/Y H:i') : 'ไม่มีวันหมดอายุ') . ' · สิทธิ์โพสต์ครบ'
                : ' · (ใส่ App ID / App Secret เพื่อตรวจอายุและสิทธิ์ของ token ด้วย)'));
    }

    public function forget(): RedirectResponse
    {
        $this->facebook->forgetToken();

        return back()->with('success', 'ลบ Page Token แล้ว — บทความใหม่จะไม่ถูกโพสต์ลงเพจจนกว่าจะเชื่อมใหม่');
    }

    /** POST /admin/articles/{article}/facebook — share one article now (force=1 to post again). */
    public function postArticle(Request $request, Article $article, ArticleFacebookPoster $poster): RedirectResponse
    {
        try {
            $result = $poster->post($article, $request->boolean('force'));
        } catch (FacebookPageException $e) {
            return back()->with('error', 'โพสต์ลง Facebook ไม่สำเร็จ: ' . $e->getMessage());
        }

        return back()->with('success', ($result['status'] === 'already'
            ? 'บทความนี้เคยโพสต์ลงเพจแล้ว: '
            : 'โพสต์ลงเพจแล้ว: ') . $result['url']);
    }
}
