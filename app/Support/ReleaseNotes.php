<?php

namespace App\Support;

use App\Models\GithubSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Release notes ในรูปที่ลูกค้าเห็นได้ — ไม่มีลิงก์ไป GitHub และไม่เหลือร่องรอยว่า repo อยู่ที่ไหน
 *
 * กฎเจ้าของ (2026-09-24): "ห้ามโหลดหรือมีลิ้งค์ไป github" และ "ห้ามให้ลูกค้ารู้ repo" — แต่ body ของ release
 * ที่ sync มาพกลิงก์ repo มาเต็ม ("**Full Changelog**: …/compare/vA...vB" ที่ GitHub เติมให้ทุก release,
 * bullet "… by @user in …/pull/N", section Contributors, "Commit: <sha>" ของ CI) แล้วไปโผล่ในหน้าสินค้า
 * หน้าโหลด และ update/check ที่แอปเอาไปแสดง
 *
 * - บรรทัดที่เครื่องมือเติมเอง (GitHub, Release Drafter, CI, git trailer) ทิ้งทั้งบรรทัด
 * - ลิงก์ไปโฮสต์ของ GitHub ทุกตัว (github.com, *.githubusercontent.com, *.github.io, ghcr.io …) ถูกถอด
 *   เก็บข้อความของลิงก์ไว้ · รูปที่อยู่บน GitHub ทิ้งทั้งรูป · บรรทัดที่เหลือแต่ป้าย ("Docs:") ทิ้ง
 * - ชื่อบัญชีของเรา ("xjanova/GpuXmine", "@xjanova") ที่เขียนเป็นข้อความเฉย ๆ ก็ถอด · SHA เต็มของ commit ย่อเหลือ 7 ตัว
 * - ลิงก์เว็บอื่น (xman4289.com, ollama.com) และ @ อื่น ๆ (@Volatile, @tailwindcss/…, LINE @ร้าน) อยู่ตามเดิม
 *
 * ไม่มีอะไรต้องตัด = คืนค่าเดิมทุก byte · ผ่านซ้ำกี่รอบก็ได้ผลเท่าเดิม — ใช้ทั้งตอน sync, migration และตอนอ่านจาก DB
 */
final class ReleaseNotes
{
    /** รายชื่อบัญชีเก็บ cache ไว้ — GithubSetting ล้างเองทุกครั้งที่มีการแก้ */
    public const ACCOUNTS_CACHE_KEY = 'release-notes:github-accounts';

    /** หัวข้อ markdown (# ถึง ######) — นอกบล็อกโค้ดเท่านั้น */
    private const HEADING = '/^ {0,3}(#{1,6})\s+\S/u';

    /** เส้นเปิด/ปิดบล็อกโค้ด ``` หรือ ~~~ — ข้างใน "# download" คือ comment ของ shell ไม่ใช่หัวข้อ */
    private const FENCE = '/^ {0,3}(?:```|~~~)/u';

    /** comment HTML ที่ยาวหลายบรรทัด — GitHub ไม่แสดงอยู่แล้ว (แบบบรรทัดเดียวอยู่ใน GENERATED_LINE) */
    private const COMMENT_OPEN = '/^\s*<!--(?!.*-->)/u';

    /** "## Contributors" / "## New Contributors" (มี emoji นำหน้าได้) — ทั้ง section เป็นรายชื่อบัญชี GitHub */
    private const CONTRIBUTORS_HEADING = '/^ {0,3}#{1,6}\s+[^\p{L}\p{N}]*(?:new\s+)?contributors?\b/iu';

    /** เนื้อใน section Contributors: bullet หรือบรรทัดที่ขึ้นต้นด้วย @ หรือ [ */
    private const CONTRIBUTOR_LINE = '/^\s*(?:(?:[-*+]|\d+[.)])\s|[@\[])/u';

    /**
     * บรรทัดที่เครื่องมือเติมเองโดยไม่มีคนเขียน — ทิ้งทั้งบรรทัด:
     * "**Full Changelog**: …", "@x made their first contribution in …", comment ของ release.yml,
     * "Commit: <sha>" ที่ CI แปะมากับ commit message (SHA เต็มเอาไปค้นหา repo บน GitHub ได้),
     * "Merge pull request #N from <บัญชี>/<branch>" และ git trailer (Co-Authored-By / Signed-off-by …)
     */
    private const GENERATED_LINE = '/^\s*(?:(?:[-*+]\s+)?(?:\*\*|__)?\s*full\s+changelog\s*(?:\*\*|__)?\s*:|(?:[-*+]\s+)?@\S+\s+made\s+(?:their|his|her)\s+first\s+contribution\b|<!--.*-->\s*$|(?:[-*+]\s+)?(?:\*\*|__)?\s*(?:commit|revision)\s*(?::\s*(?:\*\*|__)?|(?:\*\*|__)?\s*:)\s*`?[0-9a-f]{7,40}`?\s*$|(?:[-*+]\s+)?merge\s+pull\s+request\s+#\d+\s+from\s+\S+|(?:co-authored-by|signed-off-by|reviewed-by|acked-by|tested-by|reported-by|suggested-by|helped-by)\s*:)/iu';

    /** ท้าย bullet ที่บอกว่าใครทำ PR ไหน: "… by @user in <ลิงก์ PR>" (GitHub) และ "… @user (#12)" (Release Drafter) */
    private const ATTRIBUTION = '/(?:\s+by\s+@[a-z0-9][a-z0-9-]*(?:\[bot\])?\s+in\s+(?:https?:\/\/\S+|#\d+)|\s+@(?:\[(?:[^\[\]]|\[[^\[\]]*\])*\]\([^)\s]*\)|[a-z0-9][a-z0-9-]*(?:\[bot\])?)\s*\(#\d+\))\s*$/iu';

    /** [ข้อความ](ลิงก์) และ ![รูป](ลิงก์) — ข้อความซ้อน [] ได้หนึ่งชั้น เช่น [dependabot[bot]] */
    private const MARKDOWN_LINK = '/(!?)\[((?:[^\[\]\n]|\[[^\[\]\n]*\])*)\]\(\s*<?([^\s<>()]+)>?(?:\s+(?:"[^"]*"|\'[^\']*\'))?\s*\)/u';

    private const HTML_IMAGE = '/<img\b[^>]*?\bsrc\s*=\s*["\']?([^"\'\s>]+)["\']?[^>]*>/iu';

    private const HTML_ANCHOR = '/<a\b[^>]*?\bhref\s*=\s*["\']?([^"\'\s>]+)["\']?[^>]*>(.*?)<\/a>/iu';

    /** [ref]: ลิงก์ — นิยามลิงก์แบบอ้างอิงของ markdown */
    private const REFERENCE_DEFINITION = '/^ {0,3}\[[^\]]+\]:\s*<?(\S+?)>?(?:\s+.*)?$/u';

    /** ข้อความของลิงก์ที่เป็นแค่ SHA หรือ #N (ลิงก์ commit/PR แบบ release-please) — ลิงก์หายแล้วไม่เหลือความหมาย */
    private const REFERENCE_ONLY_TEXT = '/^\s*`?(?:[0-9a-f]{7,40}|#\d+)`?\s*$/iu';

    /** ลิงก์ไปโฮสต์ของ GitHub มีหรือไม่มี https:// ก็ได้ */
    private const GITHUB_URL = '(?:https?:\/\/|(?<![\w.\/@-]))(?:[a-z0-9-]+\.)*(?:github\.com|githubusercontent\.com|github\.io|github\.dev|githubassets\.com|ghcr\.io)(?::\d+)?(?:\/[^\s<>()\[\]"\'`]*)?';

    /** โฮสต์ของ GitHub (รวม subdomain) */
    private const GITHUB_HOSTS = ['github.com', 'githubusercontent.com', 'github.io', 'github.dev', 'githubassets.com', 'ghcr.io'];

    /**
     * @param  iterable<?string>  $accounts  บัญชี GitHub ของเรา (เช่น "xjanova") — ใช้ถอด "owner/repo" และ "@owner"
     *                                       ที่เขียนเป็นข้อความ · ลิงก์ไป GitHub ถูกถอดเสมอไม่ว่ารู้บัญชีหรือไม่
     * @return string|null null เมื่อไม่เหลืออะไรให้อ่าน (release ที่มีแต่บรรทัด Full Changelog)
     */
    public static function forCustomers(?string $notes, iterable $accounts = []): ?string
    {
        if ($notes === null || trim($notes) === '') {
            return null;
        }

        $accounts = self::normalizeAccounts($accounts);
        // byte ที่ไม่ใช่ UTF-8 ทำให้ regex แบบ /u ล้มทั้งบรรทัด แล้วลิงก์ในบรรทัดนั้นหลุดรอดไป — ซ่อมก่อน
        // (ถ้าสุดท้ายไม่มีอะไรต้องตัด ยังคืนค่าเดิมที่ยังไม่ซ่อม)
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", mb_scrub($notes, 'UTF-8')));
        $kept = [];
        $changed = false;
        $inContributors = false;
        $inComment = false;
        $inFence = false;

        foreach ($lines as $line) {
            if ($inComment) {
                $changed = true;
                $inComment = ! str_contains($line, '-->');

                continue;
            }

            $isFence = (bool) preg_match(self::FENCE, $line);

            // กฎระดับบรรทัด/หัวข้อใช้นอกบล็อกโค้ดเท่านั้น — ในโค้ดถอดแค่ลิงก์กับชื่อบัญชี
            if (! $inFence && ! $isFence) {
                if (preg_match(self::COMMENT_OPEN, $line)) {
                    $changed = $inComment = true;

                    continue;
                }

                if (preg_match(self::HEADING, $line)) {
                    $inContributors = (bool) preg_match(self::CONTRIBUTORS_HEADING, $line);
                } elseif ($inContributors && (trim($line) === '' || preg_match(self::CONTRIBUTOR_LINE, $line))) {
                    $changed = true;

                    continue;
                } else {
                    $inContributors = false;
                }

                if ($inContributors || preg_match(self::GENERATED_LINE, $line)) {
                    $changed = true;

                    continue;
                }
            }

            if ($isFence) {
                $inFence = ! $inFence;
                $inContributors = false;
            }

            $withoutAttribution = $inFence || $isFence ? $line : (preg_replace(self::ATTRIBUTION, '', $line) ?? $line);
            $clean = self::withoutGithub($withoutAttribution, $line, $accounts);

            if ($clean !== $line) {
                $changed = true;
            }

            if ($clean !== null) {
                $kept[] = rtrim($clean);
            }
        }

        // ไม่มีอะไรต้องตัด → คืนค่าเดิมทุก byte (CRLF, ช่องว่างท้ายบรรทัด) แถวใน DB ที่ไม่เกี่ยวจะได้ไม่ถูกแตะ
        if (! $changed) {
            return $notes;
        }

        $text = implode("\n", self::withoutEmptySections($kept));
        $text = trim((string) preg_replace("/\n{3,}/", "\n\n", $text), "\n");

        return trim($text) === '' ? null : $text;
    }

    /**
     * บัญชี GitHub ทุกบัญชีที่สินค้าของเรา sync release มา (github_settings.github_owner) ตัวพิมพ์เล็ก
     *
     * @return list<string>
     */
    public static function studioAccounts(): array
    {
        try {
            return Cache::remember(self::ACCOUNTS_CACHE_KEY, now()->addMinutes(10), fn () => GithubSetting::query()
                ->pluck('github_owner')
                ->map(fn ($owner) => strtolower(trim((string) $owner)))
                ->filter()
                ->unique()
                ->values()
                ->all());
        } catch (Throwable) {
            // อ่านรายชื่อไม่ได้ (DB/cache สะดุด) — ลิงก์ไป GitHub และส่วนที่เครื่องมือเติมเองยังถูกตัดอยู่ดี
            return [];
        }
    }

    /**
     * ถอดลิงก์ GitHub และชื่อบัญชีเราออกจากบรรทัด — null เมื่อไม่เหลืออะไรให้อ่าน
     *
     * @param  string  $original  บรรทัดก่อนตัด attribution (ใช้ดูว่าเยื้องมาแต่แรกไหม)
     * @param  list<string>  $accounts
     */
    private static function withoutGithub(string $line, string $original, array $accounts): ?string
    {
        $before = $line;

        // "Docs: <ลิงก์ GitHub>" — ป้ายสั้น ๆ ที่เหลือโดยไม่มีลิงก์ไม่มีความหมาย ทิ้งทั้งบรรทัด
        if (preg_match('/^\s*(?:(?:[-*+]|\d+[.)])\s+)?(?:\*\*|__)?[^:：\n]{1,40}?(?:\*\*|__)?\s*[:：]\s*(?:\*\*|__)?\s*<?(' . self::GITHUB_URL . ')>?\s*$/iu', $line, $m)
            && self::isGithubUrl($m[1])) {
            return null;
        }

        // [ref]: ลิงก์ GitHub — บรรทัดนี้มีไว้ให้ลิงก์อย่างเดียว
        if (preg_match(self::REFERENCE_DEFINITION, $line, $m) && self::isGithubUrl($m[1])) {
            return null;
        }

        // [ข้อความ](ลิงก์ GitHub) → ข้อความ · รูปบน GitHub ทิ้งทั้งรูป · ข้อความที่เป็นแค่ SHA/#N ทิ้งด้วย
        // วนซ้ำให้ badge ที่ซ้อนอยู่ในลิงก์ ([![…](…)](…)) หลุดครบ
        for ($pass = 0; $pass < 3; $pass++) {
            $next = preg_replace_callback(self::MARKDOWN_LINK, function (array $m): string {
                if (! self::isGithubUrl($m[3])) {
                    return $m[0];
                }

                return $m[1] === '!' || preg_match(self::REFERENCE_ONLY_TEXT, $m[2]) ? '' : $m[2];
            }, $line) ?? $line;

            if ($next === $line) {
                break;
            }

            $line = $next;
        }

        $line = preg_replace_callback(self::HTML_IMAGE, fn (array $m): string => self::isGithubUrl($m[1]) ? '' : $m[0], $line) ?? $line;
        $line = preg_replace_callback(
            self::HTML_ANCHOR,
            fn (array $m): string => ! self::isGithubUrl($m[1]) ? $m[0] : (preg_match(self::REFERENCE_ONLY_TEXT, $m[2]) ? '' : $m[2]),
            $line,
        ) ?? $line;

        // ลิงก์เปล่า / <ลิงก์> / github.com/… ที่ไม่มี https://
        $line = preg_replace_callback(
            '/<?' . self::GITHUB_URL . '>?/iu',
            fn (array $m): string => self::isGithubUrl(trim($m[0], '<>')) ? '' : $m[0],
            $line,
        ) ?? $line;

        // ชื่อ owner/repo และ @บัญชีเรา ที่เขียนเป็นข้อความ (@ อื่นไม่แตะ: @Volatile, @tailwindcss/…, LINE @ร้าน)
        if ($accounts !== []) {
            $line = preg_replace(self::accountMentionPatterns($accounts), '', $line) ?? $line;
        }

        // SHA เต็ม 40 ตัวของ commit ค้นหา repo บน GitHub ได้ทันที — เหลือ 7 ตัวแบบ git log --oneline (ในบล็อกโค้ดด้วย)
        // checksum SHA-256 (64 ตัว ไม่มีจุดตัดให้ 40 ตัวยืนเดี่ยว) และ checksum ตัวพิมพ์ใหญ่ไม่เข้าเงื่อนไข
        $line = preg_replace('/(?<![0-9a-f])([0-9a-f]{7})[0-9a-f]{33}(?![0-9a-f])/', '$1', $line) ?? $line;

        if ($line === $before) {
            return $line;
        }

        // เก็บรอยที่ลิงก์หายไป: วงเล็บว่าง, ช่องว่างซ้อน, วรรคหน้าเครื่องหมาย, ตัวคั่นที่ค้างท้ายบรรทัด
        $line = preg_replace(
            ['/\(\s*\)|\[\s*\]/u', '/(?<=\S) {2,}(?=\S)/u', '/ +([,.;!?)])/u', '/[\s:：—–-]+$/u'],
            ['', ' ', '$1', ''],
            $line,
        ) ?? $line;

        // ลิงก์ที่หายไปอยู่ต้นบรรทัด — อย่าทิ้งช่องว่างไว้ข้างหน้า (บรรทัดที่เยื้องมาแต่แรกคงการเยื้องไว้)
        if (! preg_match('/^\s/', $original)) {
            $line = ltrim($line);
        }

        // เหลือแต่ bullet หรือเครื่องหมาย = ไม่มีอะไรให้อ่าน
        return preg_match('/[\p{L}\p{N}]/u', $line) ? $line : null;
    }

    private static function isGithubUrl(string $url): bool
    {
        if (! preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (self::GITHUB_HOSTS as $github) {
            if ($host === $github || str_ends_with($host, '.' . $github)) {
                return true;
            }
        }

        return false;
    }

    /**
     * "xjanova/GpuXmine" ที่เขียนเป็นข้อความเฉย ๆ ก็คือชื่อ repo · "@xjanova" คือชื่อบัญชี
     *
     * @param  list<string>  $accounts
     * @return list<string>
     */
    private static function accountMentionPatterns(array $accounts): array
    {
        $names = implode('|', array_map(fn (string $account) => preg_quote($account, '/'), $accounts));

        return [
            '/(?<![\w.\/@:-])(?:' . $names . ')(?:\/[a-z0-9._-]+)+(?:#\d+|@[0-9a-f]{7,40})?(?![\w\/])/iu',
            '/(?<![\w@.\/-])@(?:' . $names . ')(?![\w-])/iu',
        ];
    }

    /**
     * หัวข้อที่เนื้อหาถูกตัดจนหมด (เช่น "## What's Changed" ที่เหลือแต่บรรทัด Full Changelog) ไม่ต้องโชว์หัวเปล่า
     * หัวข้อย่อยไม่นับเป็นเนื้อหา — "## Resources" ที่เหลือแต่ "### Links" ว่าง ๆ ก็หายไปด้วยกัน
     *
     * @param  list<string>  $lines
     * @return list<string>
     */
    private static function withoutEmptySections(array $lines): array
    {
        $levels = self::headingLevels($lines);
        $kept = [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            if ($levels[$i] > 0) {
                $hasContent = false;

                for ($j = $i + 1; $j < $count; $j++) {
                    if ($levels[$j] > 0 && $levels[$j] <= $levels[$i]) {
                        break;
                    }

                    if ($levels[$j] === 0 && trim($lines[$j]) !== '') {
                        $hasContent = true;

                        break;
                    }
                }

                if (! $hasContent) {
                    continue;
                }
            }

            $kept[] = $lines[$i];
        }

        return $kept;
    }

    /**
     * ระดับหัวข้อของแต่ละบรรทัด (0 = ไม่ใช่หัวข้อ หรืออยู่ในบล็อกโค้ด)
     *
     * @param  list<string>  $lines
     * @return list<int>
     */
    private static function headingLevels(array $lines): array
    {
        $levels = [];
        $inFence = false;

        foreach ($lines as $line) {
            if (preg_match(self::FENCE, $line)) {
                $inFence = ! $inFence;
                $levels[] = 0;

                continue;
            }

            $levels[] = ! $inFence && preg_match(self::HEADING, $line, $m) ? strlen($m[1]) : 0;
        }

        return $levels;
    }

    /**
     * @param  iterable<?string>  $accounts
     * @return list<string>
     */
    private static function normalizeAccounts(iterable $accounts): array
    {
        $normalized = [];

        foreach ($accounts as $account) {
            $account = strtolower(trim((string) $account));

            // ชื่อบัญชี GitHub: ตัวอักษร ตัวเลข และ - เท่านั้น
            if (preg_match('/^[a-z0-9][a-z0-9-]{0,38}$/', $account)) {
                $normalized[$account] = $account;
            }
        }

        return array_values($normalized);
    }
}
