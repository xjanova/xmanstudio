<?php

namespace App\Services\AiChat;

use App\Support\HomeContent;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Throwable;

/**
 * Every page the site has, read from the router itself.
 *
 * The assistant used to carry a hand-written list of links, and the site moved
 * on without it: it still sent people to /autotradex (no such page) and to a
 * /customer/... account area that had become /my-account. A page added, renamed
 * or removed is now known the moment the new code is deployed, because the
 * list is the route table.
 *
 * Public pages get their names from SiteIndex (each page's own <title>); the
 * account area cannot be read as a guest, so its pages are named below, and a
 * member page nobody named yet is still listed by its address.
 */
class SiteMap
{
    /** Addresses that are machinery, files or redirects rather than pages a person reads. */
    private const NOT_A_PAGE = '#^(api|admin|_|livewire|sanctum|storage|stripe|webhooks?|telegram|setup|auth'
        . '|two-factor|two-factor-challenge|verify-email|confirm-password|reset-password|og-image)(/|$)'
        . '|download|\.(txt|xml|png|ico|json|js|css|webmanifest)$|/search$|(^|/)buy$|bonus-preview#';

    /** The account area, in the words its own menu uses. */
    private const MEMBER_LABELS = [
        'customer.dashboard' => 'แดชบอร์ดบัญชีของฉัน',
        'customer.licenses' => 'License ของฉัน',
        'customer.subscriptions' => 'แพ็กเกจเช่าของฉัน (Subscription)',
        'customer.orders' => 'คำสั่งซื้อของฉัน',
        'customer.invoices' => 'ใบแจ้งหนี้ / ใบเสร็จของฉัน',
        'customer.downloads' => 'ศูนย์ดาวน์โหลด (โปรแกรมที่ซื้อแล้ว)',
        'customer.support.index' => 'ตั๋วแจ้งปัญหา / ซัพพอร์ต',
        'customer.support.create' => 'เปิดตั๋วแจ้งปัญหาใหม่',
        'customer.affiliate.dashboard' => 'Affiliate — แนะนำเพื่อนรับค่าคอมมิชชั่น',
        'customer.affiliate.commissions' => 'ค่าคอมมิชชั่น Affiliate',
        'customer.affiliate.downline' => 'สายงาน Affiliate',
        'customer.domains.index' => 'โดเมนของฉัน',
        'customer.vps.index' => 'VPS ของฉัน',
        'customer.projects' => 'โปรเจกต์ที่จ้างทำ',
        'customer.tping.workflows.index' => 'Tping Workflows ของฉัน',
        'customer.tping.data-profiles.index' => 'Tping Data Profiles ของฉัน',
        'user.wallet.index' => 'กระเป๋าเงิน (Wallet)',
        'user.wallet.topup' => 'เติมเงินเข้า Wallet',
        'user.wallet.transactions' => 'ประวัติธุรกรรม Wallet',
        'profile.edit' => 'ตั้งค่าโปรไฟล์ / รหัสผ่าน / การเข้าสู่ระบบ',
        'orders.index' => 'รายการคำสั่งซื้อ',
        'orders.checkout' => 'ชำระเงินสินค้าในตะกร้า (Checkout)',
        'rental.status' => 'สถานะการเช่าใช้บริการ',
        'rental.invoices' => 'ใบแจ้งหนี้ค่าเช่า',
        'kyc.index' => 'ยืนยันตัวตน (KYC)',
        'gpuxmine.index' => 'GPUxMINE — แชร์การ์ดจอรับรายได้',
        'dashboard' => 'แดชบอร์ด',
    ];

    /** What a public page is called before SiteIndex has read it (right after a deploy). */
    private const PUBLIC_LABELS = [
        'home' => 'หน้าแรก',
        'login' => 'เข้าสู่ระบบ',
        'register' => 'สมัครสมาชิก',
        'password.request' => 'ลืมรหัสผ่าน',
        'cart.index' => 'ตะกร้าสินค้า',
        'products.index' => 'สินค้า / ซอฟต์แวร์ทั้งหมด',
        'about' => 'เกี่ยวกับเรา',
        'portfolio' => 'ผลงาน',
        'team' => 'ทีมงาน',
        'terms' => 'ข้อกำหนดการใช้งาน',
        'privacy' => 'นโยบายความเป็นส่วนตัว',
        'quote.track' => 'ติดตามสถานะใบเสนอราคา',
        'tracking' => 'ติดตามสถานะโปรเจกต์',
        'changelog' => 'บันทึกการอัปเดตเว็บไซต์',
    ];

    /** @var array<string, string>|null */
    private ?array $crawlable = null;

    /**
     * Public pages that take no parameters: path => route name.
     *
     * @return array<string, string>
     */
    public function crawlable(): array
    {
        if ($this->crawlable !== null) {
            return $this->crawlable;
        }

        $pages = [];
        foreach ($this->pageRoutes() as $route) {
            if (! $this->needsLogin($route) && ! $this->isAdmin($route) && ! $this->isThrottled($route)) {
                $pages[$this->path($route)] = (string) $route->getName();
            }
        }
        ksort($pages);

        return $this->crawlable = $pages;
    }

    /**
     * The account area: pages a member reaches after signing in.
     *
     * @return array<int, array{path: string, label: string}>
     */
    public function memberPages(): array
    {
        // Named ones first, in the order the account menu shows them.
        $order = array_flip(array_keys(self::MEMBER_LABELS));
        $pages = [];

        foreach ($this->pageRoutes() as $route) {
            if ($this->needsLogin($route) && ! $this->isAdmin($route)) {
                $name = (string) $route->getName();
                $pages[$this->path($route)] = [
                    'path' => $this->path($route),
                    'label' => self::MEMBER_LABELS[$name] ?? '',
                    'rank' => $order[$name] ?? PHP_INT_MAX,
                ];
            }
        }

        usort($pages, fn (array $a, array $b) => [$a['rank'], $a['path']] <=> [$b['rank'], $b['path']]);

        return array_map(fn (array $page) => ['path' => $page['path'], 'label' => $page['label']], $pages);
    }

    /** @return array<int, string> paths of the admin panel's pages */
    public function adminPaths(): array
    {
        $paths = [];
        foreach ($this->pageRoutes(adminToo: true) as $route) {
            if ($this->isAdmin($route)) {
                $paths[] = $this->path($route);
            }
        }
        sort($paths);

        return array_values(array_unique($paths));
    }

    /** What the account-area page with this route name is called, if it has a name. */
    public function memberLabel(?string $routeName): ?string
    {
        return $routeName !== null ? (self::MEMBER_LABELS[$routeName] ?? null) : null;
    }

    /** What a public page is called when SiteIndex has not read it yet. */
    public function fallbackLabel(string $path, string $routeName): string
    {
        if (isset(self::PUBLIC_LABELS[$routeName])) {
            return self::PUBLIC_LABELS[$routeName];
        }

        foreach ($this->menuLabels() as $menuPath => $label) {
            if ($menuPath === $path) {
                return $label;
            }
        }

        return Str::of($routeName)->replace(['.index', '.show', '.detail'], '')->replace(['.', '-', '_'], ' ')->trim()->toString();
    }

    /**
     * The site's main menu, from the same list the home pages draw it from.
     *
     * @return array<string, string> path => Thai label
     */
    public function menuLabels(): array
    {
        try {
            $labels = [];
            foreach (HomeContent::menu() as $item) {
                $path = $this->localPath((string) ($item['href'] ?? ''));
                if ($path !== null) {
                    $labels[$path] = (string) $item['th'];
                }
            }

            return $labels;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * The sister sites in the main menu (they live on other domains).
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function sisterSites(): array
    {
        try {
            $sites = [];
            foreach (HomeContent::menu() as $item) {
                $href = (string) ($item['href'] ?? '');
                if ($href !== '' && $this->localPath($href) === null) {
                    $sites[] = ['label' => $item['th'] . ' (' . $item['en'] . ')', 'url' => $href];
                }
            }

            return $sites;
        } catch (Throwable) {
            return [];
        }
    }

    /** The path of a link on this site, or null when it leads somewhere else. */
    public function localPath(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $ours = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($host !== null && $host !== false && strcasecmp((string) $host, (string) $ours) !== 0) {
            return null;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');

        return '/' . ltrim($path, '/');
    }

    public function needsLogin(Route $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            $middleware = is_string($middleware) ? $middleware : '';
            if (preg_match('/^(auth(?![a-z])|verified|signed|password\.confirm|kyc\.verified)|Authenticate$|EnsureEmailIsVerified/', $middleware)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(Route $route): bool
    {
        if (str_starts_with((string) $route->getName(), 'admin.') || str_starts_with($route->uri(), 'admin')) {
            return true;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && preg_match('/^(admin|role|permission|can)(:|$)|AdminMiddleware/', $middleware)) {
                return true;
            }
        }

        return false;
    }

    private function isThrottled(Route $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && (str_starts_with($middleware, 'throttle') || str_contains($middleware, 'ThrottleRequests'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Named GET routes without parameters that are pages, not machinery.
     *
     * @return array<int, Route>
     */
    private function pageRoutes(bool $adminToo = false): array
    {
        $routes = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();
            $uri = trim($route->uri(), '/');

            if ($name === null || ! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
                continue;
            }

            if ($route->getDomain() !== null) {
                continue;
            }

            if ($adminToo && str_starts_with($uri, 'admin')) {
                $routes[] = $route;

                continue;
            }

            if (preg_match(self::NOT_A_PAGE, $uri)) {
                continue;
            }

            $routes[] = $route;
        }

        return $routes;
    }

    private function path(Route $route): string
    {
        return '/' . ltrim($route->uri(), '/');
    }
}
