<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Alerts\Redact;
use Closure;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * The XMAN Studio company page on Facebook (XmanEnterprise), through the Graph API.
 *
 * It uses the same Meta app as the fortune pages in Thaiprompt-Affiliate (XmanApp): a person who
 * manages the page grants that app pages_manage_posts once, the user token is traded for a
 * long-lived one, and the page token read from /me/accounts with it never expires. Only the page
 * token is kept here (Setting, encrypted) — never the user token.
 *
 * Tokens travel in the Authorization header, never the URL: an exception message quotes the URL,
 * and messages end up in logs and Telegram alerts. Every failure becomes a FacebookPageException
 * whose message is Thai and says what to do about it.
 */
class FacebookPageService
{
    public const GRAPH_VERSION = 'v22.0';

    public const GRAPH_URL = 'https://graph.facebook.com/';

    public const SETTINGS_GROUP = 'facebook';

    public const KEY_PAGE_ID = 'facebook_page_id';

    public const KEY_PAGE_NAME = 'facebook_page_name';

    public const KEY_PAGE_TOKEN = 'facebook_page_access_token';

    public const KEY_APP_ID = 'facebook_app_id';

    public const KEY_APP_SECRET = 'facebook_app_secret';

    public const KEY_AUTOPOST = 'facebook_autopost_articles';

    public const KEY_CONNECTED_AT = 'facebook_page_connected_at';

    /** Empty when the page token never expires (the normal case). */
    public const KEY_TOKEN_EXPIRES_AT = 'facebook_page_token_expires_at';

    /** A link post needs both: manage_posts writes, read_engagement is required alongside it. */
    public const REQUIRED_SCOPES = ['pages_manage_posts', 'pages_read_engagement'];

    /** Where an admin fixes the connection — every "reconnect" message points here. */
    public const SETTINGS_SCREEN = 'หน้าแอดมิน "ตั้งค่า > Facebook เพจ"';

    // ------------------------------------------------------------------------------ settings

    public function pageId(): string
    {
        return trim((string) Setting::getValue(self::KEY_PAGE_ID, ''));
    }

    public function pageName(): string
    {
        return trim((string) Setting::getValue(self::KEY_PAGE_NAME, ''));
    }

    public function appId(): string
    {
        return trim((string) Setting::getValue(self::KEY_APP_ID, ''));
    }

    public function hasPageToken(): bool
    {
        return $this->pageToken() !== '';
    }

    public function hasAppSecret(): bool
    {
        return $this->appSecret() !== '';
    }

    /** Page id and page token are both set — posting can be attempted. */
    public function isConfigured(): bool
    {
        return $this->pageId() !== '' && $this->hasPageToken();
    }

    /** The admin switch for sharing new articles automatically. On unless someone turned it off. */
    public function autopostEnabled(): bool
    {
        return (bool) Setting::getValue(self::KEY_AUTOPOST, true);
    }

    public function tokenExpiresAt(): ?Carbon
    {
        $value = (string) Setting::getValue(self::KEY_TOKEN_EXPIRES_AT, '');

        return $value === '' ? null : Carbon::parse($value);
    }

    public function forgetToken(): void
    {
        Setting::setValue(self::KEY_PAGE_TOKEN, '', 'string', self::SETTINGS_GROUP);
        Setting::setValue(self::KEY_TOKEN_EXPIRES_AT, '', 'string', self::SETTINGS_GROUP);
    }

    /** "106036940932060_123" → the post on facebook.com. */
    public static function postUrl(string $postId): string
    {
        return 'https://www.facebook.com/' . $postId;
    }

    private function pageToken(): string
    {
        return trim((string) Setting::getValue(self::KEY_PAGE_TOKEN, ''));
    }

    private function appSecret(): string
    {
        return trim((string) Setting::getValue(self::KEY_APP_SECRET, ''));
    }

    // ------------------------------------------------------------------------------ posting

    /**
     * A link post on the page: the message, and Facebook draws the card from the link's og: tags.
     *
     * @return string the post id ("{page}_{post}")
     *
     * @throws FacebookPageException
     */
    public function postLink(string $message, string $link): string
    {
        $this->requireConfigured();

        $response = $this->call(fn () => Http::asForm()->timeout(30)
            ->withToken($this->pageToken())
            ->post($this->url($this->pageId() . '/feed'), ['message' => $message, 'link' => $link]));

        $id = (string) ($response->json('id') ?? '');
        if ($id === '') {
            throw new FacebookPageException('Facebook ตอบว่าสำเร็จแต่ไม่ส่งรหัสโพสต์กลับมา — เปิดเพจดูว่าโพสต์ขึ้นหรือยังก่อนสั่งโพสต์ซ้ำ');
        }

        return $id;
    }

    /**
     * Ask Facebook to read the page's og: tags again right now, so the card shows the real cover
     * and title — not a stale scrape from before the article went live. Best effort: a failure
     * here only means an older preview, never a reason not to post.
     */
    public function refreshLinkPreview(string $link): void
    {
        try {
            Http::asForm()->timeout(15)->withToken($this->pageToken())
                ->post($this->url(''), ['id' => $link, 'scrape' => 'true']);
        } catch (Throwable) {
        }
    }

    // ------------------------------------------------------------------------------ connecting

    /**
     * Store the page token for the company page from whatever token the admin has:
     *  - a Page token for that page → kept as it is;
     *  - a User token (Graph API Explorer, app XmanApp) → traded for a long-lived one when the app
     *    id/secret are set, then the page's own token is read from /me/accounts — a page token read
     *    with a long-lived user token never expires.
     *
     * @return array{page_id: string, page_name: string, kind: string, expires_at: ?Carbon, warnings: list<string>}
     *
     * @throws FacebookPageException
     */
    public function connect(string $token, ?string $pageId = null): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new FacebookPageException('ยังไม่ได้วาง token');
        }

        $pageId = trim((string) ($pageId ?: $this->pageId()));
        $warnings = [];
        $me = $this->call(fn () => Http::timeout(15)->withToken($token)
            ->get($this->url('me'), ['fields' => 'id,name']))->json();

        if ($pageId !== '' && (string) ($me['id'] ?? '') === $pageId) {
            $kind = 'page';
            $pageToken = $token;
            $pageName = (string) ($me['name'] ?? '');
        } else {
            $accounts = $this->pagesOf($token);

            if ($accounts === null) {
                // Not a user token that can list pages. Either a page token for a page we were
                // not told about yet (take it), or a token for something else entirely.
                if ($pageId !== '') {
                    throw new FacebookPageException('token นี้เป็นของ "' . ($me['name'] ?? '?') . '" (' . ($me['id'] ?? '?') . ') ไม่ใช่เพจ ' . $pageId
                        . ' — ใช้ Page Token ของเพจ ' . $pageId . ' หรือ User Token ที่มีสิทธิ์ pages_show_list');
                }
                $kind = 'page';
                $pageToken = $token;
                $pageId = (string) ($me['id'] ?? '');
                $pageName = (string) ($me['name'] ?? '');
            } else {
                $kind = 'user';
                $longLived = $this->exchangeForLongLived($token, $warnings);
                if ($longLived !== null) {
                    $accounts = $this->pagesOf($longLived) ?? $accounts;
                }

                $page = $this->pickPage($accounts, $pageId, (string) ($me['name'] ?? ''));
                $pageId = (string) $page['id'];
                $pageName = (string) ($page['name'] ?? '');
                $pageToken = (string) ($page['access_token'] ?? '');
            }
        }

        $expiresAt = null;
        $info = $this->inspect($pageToken);
        if ($info === null) {
            $warnings[] = 'ยังตรวจอายุและสิทธิ์ของ token ไม่ได้ เพราะยังไม่ได้ใส่ App ID / App Secret';
        } else {
            $this->requireScopes($info['scopes']);
            $expiresAt = $info['expires_at'];
            if ($expiresAt !== null) {
                $warnings[] = 'Page token นี้จะหมดอายุ ' . $expiresAt->timezone('Asia/Bangkok')->format('d/m/Y H:i')
                    . ' — ใส่ App ID / App Secret แล้ววาง User Token ใหม่ ระบบจะแลกเป็นตัวที่ไม่มีวันหมดอายุให้';
            }
        }

        Setting::setValue(self::KEY_PAGE_ID, $pageId, 'string', self::SETTINGS_GROUP);
        Setting::setValue(self::KEY_PAGE_NAME, $pageName, 'string', self::SETTINGS_GROUP);
        Setting::setValue(self::KEY_PAGE_TOKEN, $pageToken, 'string', self::SETTINGS_GROUP);
        Setting::setValue(self::KEY_TOKEN_EXPIRES_AT, $expiresAt?->toIso8601String() ?? '', 'string', self::SETTINGS_GROUP);
        Setting::setValue(self::KEY_CONNECTED_AT, now()->toIso8601String(), 'string', self::SETTINGS_GROUP);

        return ['page_id' => $pageId, 'page_name' => $pageName, 'kind' => $kind, 'expires_at' => $expiresAt, 'warnings' => $warnings];
    }

    /**
     * Is the stored token still able to post? Reads the page with it, and — when the app secret
     * is known — its lifetime and permissions.
     *
     * @return array{page_id: string, page_name: string, followers: ?int, expires_at: ?Carbon, inspected: bool}
     *
     * @throws FacebookPageException
     */
    public function check(): array
    {
        $this->requireConfigured();

        $page = $this->call(fn () => Http::timeout(15)->withToken($this->pageToken())
            ->get($this->url($this->pageId()), ['fields' => 'id,name,followers_count']))->json();

        $name = (string) ($page['name'] ?? '');
        if ($name !== '' && $name !== $this->pageName()) {
            Setting::setValue(self::KEY_PAGE_NAME, $name, 'string', self::SETTINGS_GROUP);
        }

        $info = $this->inspect($this->pageToken());
        if ($info !== null) {
            $this->requireScopes($info['scopes']);
        }

        return [
            'page_id' => (string) ($page['id'] ?? $this->pageId()),
            'page_name' => $name,
            'followers' => isset($page['followers_count']) ? (int) $page['followers_count'] : null,
            'expires_at' => $info['expires_at'] ?? null,
            'inspected' => $info !== null,
        ];
    }

    /** @return list<array{id: string, name?: string, access_token?: string, tasks?: list<string>}>|null null when the token cannot list pages */
    private function pagesOf(string $userToken): ?array
    {
        try {
            $response = $this->call(fn () => Http::timeout(20)->withToken($userToken)
                ->get($this->url('me/accounts'), ['fields' => 'id,name,access_token,tasks', 'limit' => 100]));
        } catch (FacebookPageException $e) {
            if ($e->retryable) {
                throw $e;
            }

            return null;
        }

        return array_values((array) ($response->json('data') ?? []));
    }

    /**
     * @param  list<array<string,mixed>>  $pages
     * @return array<string,mixed>
     */
    private function pickPage(array $pages, string $pageId, string $who): array
    {
        $names = implode(', ', array_map(fn ($p) => ($p['name'] ?? '?') . ' (' . ($p['id'] ?? '?') . ')', array_slice($pages, 0, 10)));

        if ($pageId === '') {
            if (count($pages) !== 1) {
                throw new FacebookPageException('บัญชีนี้ดูแล ' . count($pages) . ' เพจ — ใส่ Page ID ของเพจบริษัทก่อน'
                    . ($names !== '' ? ' (เพจที่เห็น: ' . $names . ')' : ''));
            }
            $pageId = (string) $pages[0]['id'];
        }

        foreach ($pages as $page) {
            if ((string) ($page['id'] ?? '') !== $pageId) {
                continue;
            }
            if (isset($page['tasks']) && ! in_array('CREATE_CONTENT', (array) $page['tasks'], true)) {
                throw new FacebookPageException('บัญชี "' . $who . '" ดูแลเพจ ' . ($page['name'] ?? $pageId)
                    . ' แต่ไม่มีสิทธิ์สร้างโพสต์ — ให้ผู้ดูแลเพจตั้งบทบาทเป็น "สิทธิ์เข้าถึงทั้งหมด" หรือ "สร้างคอนเทนต์" ใน Meta Business Suite ก่อน', needsReconnect: true);
            }
            if (empty($page['access_token'])) {
                throw new FacebookPageException('Facebook ไม่ส่ง token ของเพจ ' . ($page['name'] ?? $pageId) . ' มาให้ — token ต้องมีสิทธิ์ pages_show_list และ pages_manage_posts', needsReconnect: true);
            }

            return $page;
        }

        throw new FacebookPageException('บัญชี "' . $who . '" ไม่เห็นเพจ ' . $pageId
            . ' — ตอนกดอนุญาตแอป XmanApp ต้องติ๊กเลือกเพจนี้ด้วย'
            . ($names !== '' ? ' (เพจที่เห็น: ' . $names . ')' : ''), needsReconnect: true);
    }

    /**
     * Short-lived user token (about an hour, as Graph API Explorer hands it out) → 60-day token.
     * Not fatal when it fails: the page token still works, it just will not be the permanent kind.
     *
     * @param  list<string>  $warnings
     */
    private function exchangeForLongLived(string $userToken, array &$warnings): ?string
    {
        if ($this->appId() === '' || ! $this->hasAppSecret()) {
            $warnings[] = 'ยังไม่ได้ใส่ App ID / App Secret จึงแลก User Token เป็นแบบอายุยาวไม่ได้ — Page Token ที่ได้อาจหมดอายุภายในไม่กี่ชั่วโมง';

            return null;
        }

        try {
            $token = (string) ($this->call(fn () => Http::timeout(15)->get($this->url('oauth/access_token'), [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->appId(),
                'client_secret' => $this->appSecret(),
                'fb_exchange_token' => $userToken,
            ]))->json('access_token') ?? '');
        } catch (FacebookPageException $e) {
            $warnings[] = 'แลก User Token เป็นแบบอายุยาวไม่สำเร็จ: ' . $e->getMessage();

            return null;
        }

        return $token !== '' ? $token : null;
    }

    /**
     * Lifetime and permissions of a token, from /debug_token. Needs the app id and secret; null
     * without them or when Facebook will not say.
     *
     * @return array{type: string, expires_at: ?Carbon, scopes: list<string>}|null
     */
    private function inspect(string $token): ?array
    {
        if ($this->appId() === '' || ! $this->hasAppSecret()) {
            return null;
        }

        try {
            $data = (array) ($this->call(fn () => Http::timeout(10)
                ->withToken($this->appId() . '|' . $this->appSecret())
                ->get($this->url('debug_token'), ['input_token' => $token]))->json('data') ?? []);
        } catch (FacebookPageException) {
            return null;
        }

        if (($data['is_valid'] ?? true) === false) {
            $error = (array) ($data['error'] ?? []);
            throw self::explain(['code' => 190, 'error_subcode' => $error['subcode'] ?? null, 'message' => $error['message'] ?? 'token ใช้ไม่ได้'], 400);
        }

        // Graph uses 0 for "never expires" — reading it as a date would say 1970, i.e. expired.
        $expires = (int) ($data['expires_at'] ?? 0);

        return [
            'type' => (string) ($data['type'] ?? ''),
            'expires_at' => $expires > 0 ? Carbon::createFromTimestamp($expires) : null,
            'scopes' => array_values(array_filter((array) ($data['scopes'] ?? []))),
        ];
    }

    /** @param list<string> $scopes */
    private function requireScopes(array $scopes): void
    {
        if ($scopes === []) {
            return; // Facebook did not list them — do not invent a problem.
        }

        $missing = array_values(array_diff(self::REQUIRED_SCOPES, $scopes));
        if ($missing !== []) {
            throw new FacebookPageException('token ขาดสิทธิ์ ' . implode(', ', $missing)
                . ' — สร้าง User Token ใหม่ใน Graph API Explorer (แอป XmanApp) ติ๊กสิทธิ์ให้ครบ แล้ววางใน ' . self::SETTINGS_SCREEN, needsReconnect: true);
        }
    }

    // ------------------------------------------------------------------------------ plumbing

    private function requireConfigured(): void
    {
        if ($this->pageId() === '') {
            throw new FacebookPageException('ยังไม่ได้ตั้ง Page ID ของเพจบริษัท — ตั้งค่าที่ ' . self::SETTINGS_SCREEN, needsReconnect: true);
        }
        if (! $this->hasPageToken()) {
            throw new FacebookPageException('ยังไม่ได้เชื่อมเพจ Facebook (ไม่มี Page Token) — เชื่อมที่ ' . self::SETTINGS_SCREEN, needsReconnect: true);
        }
    }

    private function url(string $path): string
    {
        return self::GRAPH_URL . self::GRAPH_VERSION . '/' . ltrim($path, '/');
    }

    /** Run one Graph request; a failure of any kind comes out as a FacebookPageException. */
    private function call(Closure $request): Response
    {
        try {
            /** @var Response $response */
            $response = $request();
        } catch (Throwable $e) {
            throw new FacebookPageException('เชื่อมต่อ Facebook ไม่ได้ (เครือข่ายของเซิร์ฟเวอร์หรือ Facebook มีปัญหา) — ระบบจะลองใหม่เอง: '
                . self::clean($e->getMessage()), retryable: true);
        }

        $body = $response->json();
        if ($response->successful() && ! (is_array($body) && isset($body['error']))) {
            return $response;
        }

        throw self::explain(is_array($body) ? (array) ($body['error'] ?? []) : [], $response->status());
    }

    /**
     * Turn a Graph error into something an admin can act on, in Thai, keeping Facebook's own words
     * (and the code) at the end for whoever has to dig further.
     *
     * @param  array<string,mixed>  $error
     */
    public static function explain(array $error, int $status): FacebookPageException
    {
        $code = (int) ($error['code'] ?? 0);
        $sub = (int) ($error['error_subcode'] ?? 0);
        $said = trim((string) ($error['error_user_msg'] ?? $error['message'] ?? ''));
        $tail = $said !== ''
            ? ' (Facebook: ' . Str::limit(self::clean($said), 200) . ($code ? ' · code ' . $code . ($sub ? '/' . $sub : '') : '') . ')'
            : ' (HTTP ' . $status . ($code ? ' · code ' . $code : '') . ')';
        $reconnect = ' — เชื่อมเพจใหม่ที่ ' . self::SETTINGS_SCREEN;

        if ($code === 190 || $code === 102) {
            $why = match ($sub) {
                463, 467 => 'Page Token หมดอายุแล้ว',
                460 => 'Page Token ใช้ไม่ได้แล้ว เพราะเจ้าของบัญชี Facebook ที่ออก token เปลี่ยนรหัสผ่าน',
                458 => 'บัญชี Facebook ที่ออก token ถอนการอนุญาตแอป XmanApp ไปแล้ว',
                459, 464 => 'บัญชี Facebook ที่ออก token ถูกล็อกหรือต้องยืนยันตัวตน — เข้า facebook.com ด้วยบัญชีนั้นก่อน',
                default => 'Page Token ใช้ไม่ได้แล้ว (หมดอายุ ถูกเพิกถอน หรือวางมาไม่ครบ)',
            };

            return new FacebookPageException($why . $reconnect . $tail, graphCode: $code, needsReconnect: true);
        }

        if (in_array($code, [3, 10], true) || ($code >= 200 && $code <= 299)) {
            return new FacebookPageException('token นี้ไม่มีสิทธิ์โพสต์ลงเพจ — ต้องมีสิทธิ์ pages_manage_posts กับ pages_read_engagement'
                . ' และบัญชีที่ออก token ต้องเป็นผู้ดูแลเพจที่สร้างโพสต์ได้' . $reconnect . $tail, graphCode: $code, needsReconnect: true);
        }

        if (in_array($code, [4, 17, 32, 613, 80001], true) || $status === 429) {
            return new FacebookPageException('Facebook จำกัดจำนวนคำขอชั่วคราว (rate limit) — ระบบจะลองโพสต์ใหม่เองภายหลัง' . $tail, retryable: true, graphCode: $code ?: null);
        }

        if ($code === 368) {
            return new FacebookPageException('Facebook ระงับการโพสต์ของเพจชั่วคราว (มองว่าเป็นสแปมหรือขัดมาตรฐานชุมชน) — เปิดเพจใน facebook.com ดูการแจ้งเตือนคุณภาพเพจ' . $tail, graphCode: $code);
        }

        if ($code === 506) {
            return new FacebookPageException('Facebook ไม่ยอมโพสต์ข้อความซ้ำกับโพสต์ล่าสุดของเพจ — บทความนี้น่าจะถูกโพสต์ไปแล้ว ตรวจหน้าเพจก่อน' . $tail, graphCode: $code);
        }

        if (in_array($code, [1, 2], true) || $status >= 500) {
            return new FacebookPageException('ระบบของ Facebook ขัดข้องชั่วคราว — ระบบจะลองใหม่เอง' . $tail, retryable: true, graphCode: $code ?: null);
        }

        if ($code === 100) {
            return new FacebookPageException('Facebook ไม่รับคำขอ — ตรวจว่า Page ID ถูกต้อง และลิงก์บทความเปิดได้จากภายนอก' . $tail, graphCode: $code);
        }

        return new FacebookPageException('Facebook ปฏิเสธคำขอ' . $tail, graphCode: $code ?: null);
    }

    /** No secret we hold, and no query string (where tokens would sit), survives into a message. */
    private static function clean(string $message): string
    {
        $message = preg_replace('~(https?://[^\s?]+)\?\S*~', '$1?…', $message) ?? $message;

        try {
            return Redact::text($message);
        } catch (Throwable) {
            return $message;
        }
    }
}
