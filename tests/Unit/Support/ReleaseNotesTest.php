<?php

namespace Tests\Unit\Support;

use App\Support\ReleaseNotes;
use PHPUnit\Framework\TestCase;

/**
 * release notes ที่ลูกค้าเห็นต้องไม่บอกว่า repo อยู่ไหน (กฎเจ้าของ 2026-09-24)
 * ตัวอย่างทุกอันเลียนแบบ body จริงของ release ในบัญชี xjanova (ดึงมาดู 220 release จาก 25 repo)
 */
class ReleaseNotesTest extends TestCase
{
    private const ACCOUNTS = ['xjanova'];

    public function test_githubs_generated_notes_keep_only_what_a_person_wrote(): void
    {
        $body = "<!-- Release notes generated using configuration in .github/release.yml at main -->\r\n"
            . "## What's Changed\r\n"
            . "* feat(discord): English-first room content with Thai underneath by @xjanova in https://github.com/xjanova/BrainX/pull/41\r\n"
            . "* Bump the microsoft group with 15 updates by @dependabot[bot] in https://github.com/xjanova/xcluadeagent/pull/122\r\n"
            . "\r\n"
            . "## New Contributors\r\n"
            . "* @someone made their first contribution in https://github.com/xjanova/BrainX/pull/40\r\n"
            . "\r\n"
            . '**Full Changelog**: https://github.com/xjanova/BrainX/compare/v2.0.403...v2.0.408';

        $this->assertSame(
            "## What's Changed\n"
            . "* feat(discord): English-first room content with Thai underneath\n"
            . '* Bump the microsoft group with 15 updates',
            ReleaseNotes::forCustomers($body, self::ACCOUNTS),
        );
    }

    public function test_release_drafter_notes_lose_the_authors_and_the_contributors(): void
    {
        $body = "## What's Changed\n\n"
            . "## ⬆️ Dependencies\n\n"
            . "- Bump Swashbuckle.AspNetCore from 10.1.0 to 10.1.1 @[dependabot[bot]](https://github.com/apps/dependabot) (#124)\n\n"
            . "## Contributors\n\n"
            . "@dependabot[bot], @xjanova and [dependabot[bot]](https://github.com/apps/dependabot)\n\n"
            . '**Full Changelog**: https://github.com/xjanova/xcluadeagent/compare/v0.12.10...v0.12.11';

        // "## What's Changed" ที่ว่างเพราะหัวข้อถัดไปมาทันที ก็ไม่ต้องโชว์
        $this->assertSame(
            "## ⬆️ Dependencies\n\n- Bump Swashbuckle.AspNetCore from 10.1.0 to 10.1.1",
            ReleaseNotes::forCustomers($body, self::ACCOUNTS),
        );
    }

    public function test_a_release_that_is_only_the_full_changelog_line_has_nothing_left(): void
    {
        // GPUxMINE v0.1.13–0.1.17 ใน production เป็นแบบนี้ทั้งหมด → กล่อง Changelog ต้องหายไป ไม่ใช่กล่องเปล่า
        $this->assertNull(ReleaseNotes::forCustomers(
            "\r\n\r\n**Full Changelog**: https://github.com/xjanova/GpuXmine/compare/v0.1.16...v0.1.17",
            self::ACCOUNTS,
        ));
    }

    public function test_ci_commit_messages_lose_the_sha_the_merge_line_and_the_trailers(): void
    {
        // SmsChecker: CI แปะ commit message ทั้งก้อน — SHA เต็มเอาไปค้น commit บน GitHub แล้วเจอ repo
        $body = "**Build #189** — Release APK (signed)\n\n"
            . "Commit: dc0d3523af9dbc1e4ab1e71fe35b922700db982f\n"
            . "Merge pull request #14 from xjanova/claude/millennium-3d-accuracy\n\n"
            . "fix(orders): แท็บออเดอร์ไม่เด้ง \"เกิดข้อผิดพลาด\" ตอนเปิดจอแล้ว\n\n"
            . "- `introShownThisProcess` เป็น @Volatile static (หลุดมาจาก 21d8c79)\n\n"
            . "Signed-off-by: Someone <someone@example.com>\n"
            . 'Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>';

        $this->assertSame(
            "**Build #189** — Release APK (signed)\n\n"
            . "fix(orders): แท็บออเดอร์ไม่เด้ง \"เกิดข้อผิดพลาด\" ตอนเปิดจอแล้ว\n\n"
            . '- `introShownThisProcess` เป็น @Volatile static (หลุดมาจาก 21d8c79)',
            ReleaseNotes::forCustomers($body, self::ACCOUNTS),
        );
    }

    public function test_links_to_github_keep_their_text_and_lose_the_link(): void
    {
        // กฎเจ้าของคือ "ห้ามมีลิ้งค์ไป github" — ไม่ใช่แค่ repo ของเรา
        $body = implode("\n", [
            '- See [CHANGELOG.md](https://github.com/xjanova/Skidrowkiller/blob/main/CHANGELOG.md) for details',
            'พิมพ์เขียวอยู่ใน [`docs/`](https://github.com/xjanova/aquachord/tree/main/docs)',
            '[![build](https://raw.githubusercontent.com/xjanova/aquachord/main/badge.svg)](https://github.com/xjanova/aquachord/actions) สร้างแล้ว',
            '![หน้าจอใหม่](https://github.com/user-attachments/assets/0f1e2d3c-4b5a-6978-8a9b-0c1d2e3f4a5b)',
            '<img src="https://raw.githubusercontent.com/xjanova/BrainX/main/shot.png" width="600"> หน้าจอใหม่',
            'ดูคู่มือ <a href="https://xjanova.github.io/brainx/">ที่นี่</a>',
            'แจ้งปัญหาที่ https://github.com/xjanova/BrainX/issues หรือ <https://github.com/xjanova/BrainX/discussions>',
            '- ใช้ [llama.cpp](https://github.com/ggerganov/llama.cpp) รันโมเดล',
            '* **deps:** bump sqlite ([#12](https://github.com/xjanova/GpuXmine/issues/12)) ([a1b2c3d](https://github.com/xjanova/GpuXmine/commit/a1b2c3d4e5f6))',
            'API: https://api.github.com/repos/xjanova/BrainX/releases/latest',
            'clone: github.com/xjanova/BrainX',
            'Image: ghcr.io/xjanova/gpuxmine:0.1.17',
            'สนับสนุนได้ที่ https://github.com/sponsors/xjanova ขอบคุณครับ',
            'ขอบคุณ @xjanova ที่ช่วยทดสอบ ดูได้ที่ xjanova/BrainX#12',
            '[repo]: https://github.com/xjanova/BrainX',
        ]);

        $this->assertSame(implode("\n", [
            '- See CHANGELOG.md for details',
            'พิมพ์เขียวอยู่ใน `docs/`',
            'สร้างแล้ว',
            'หน้าจอใหม่',
            'ดูคู่มือ ที่นี่',
            'แจ้งปัญหาที่ หรือ',
            '- ใช้ llama.cpp รันโมเดล',
            '* **deps:** bump sqlite',
            'สนับสนุนได้ที่ ขอบคุณครับ',
            'ขอบคุณ ที่ช่วยทดสอบ ดูได้ที่',
        ]), ReleaseNotes::forCustomers($body, self::ACCOUNTS));
    }

    public function test_everything_that_is_not_github_stays_exactly_as_written(): void
    {
        // ไม่มีอะไรต้องตัด → คืนค่าเดิมทุก byte (CRLF + ช่องว่างท้ายบรรทัด) — migration จะได้ไม่แตะแถวนี้
        $body = "📦 ซื้อ License: https://xman4289.com/localvpn/buy  \r\n"
            . "- Local mode: a GGUF model, or [Ollama](https://ollama.com)\r\n"
            . "- CI (GitHub Actions): composer install + build + migrate\r\n"
            . "- Bump @tailwindcss/typography from 0.5.9 to 0.5.10\r\n"
            . "- `introShownThisProcess` เป็น @Volatile static (หลุดมาจาก 21d8c79)\r\n"
            . "- ติดต่อ LINE @xmanstudio หรือ support@xman4289.com · ไม่ใช่บัญชีเรา: @xjanovax, xjanovax/tools\r\n"
            . "- SHA256: 3532c4682dd4fe0977cc5d9e3add414f4a1ffa1314f0a0ad9038c567ee6c0576\r\n"
            . "- SHA1 (certutil): 2FD4E1C67A2D28FCED849EE1BB76E7391B93EB12\r\n"
            . "- Fixed crash (#124) · Build #184\r\n"
            . 'Auto-generated release from commit `dd6fbfe`.';

        $this->assertSame($body, ReleaseNotes::forCustomers($body, self::ACCOUNTS));
    }

    public function test_account_names_match_whatever_their_case(): void
    {
        $this->assertSame(
            'ขอบคุณ ที่ช่วยทดสอบ',
            ReleaseNotes::forCustomers('ขอบคุณ @XJanova ที่ช่วยทดสอบ XJANOVA/GpuXmine#3', ['XJANOVA']),
        );
    }

    public function test_a_label_left_without_its_link_goes_with_it(): void
    {
        $this->assertNull(ReleaseNotes::forCustomers(
            "Docs: https://xjanova.github.io/GpuXmine/\n- **Script**: https://raw.githubusercontent.com/xjanova/GpuXmine/main/install.ps1",
            self::ACCOUNTS,
        ));
    }

    public function test_without_known_accounts_links_still_go_but_plain_names_stay(): void
    {
        $this->assertSame(
            'See docs, thanks @xjanova',
            ReleaseNotes::forCustomers(
                "See [docs](https://github.com/xjanova/BrainX/tree/main/docs), thanks @xjanova\n\n**Full Changelog**: https://github.com/xjanova/BrainX/compare/v1...v2",
            ),
        );
    }

    public function test_code_blocks_keep_their_comment_lines_and_lose_only_links(): void
    {
        // ในบล็อกโค้ด "# download" คือ comment ของ PowerShell ไม่ใช่หัวข้อ — ถ้าตีความเป็นหัวข้อว่างจะถูกตัดทิ้ง
        // และ "# Contributors" ในโค้ดต้องไม่ลากบรรทัดถัดไปหายไปทั้ง section
        $body = "## วิธีติดตั้ง\n"
            . "```powershell\n"
            . "# download\n"
            . "# Contributors\n"
            . "git clone https://github.com/xjanova/GpuXmine\n"
            . ".\\setup.exe\n"
            . "```\n\n"
            . '**Full Changelog**: https://github.com/xjanova/GpuXmine/compare/v0.1.16...v0.1.17';

        $this->assertSame(
            "## วิธีติดตั้ง\n```powershell\n# download\n# Contributors\ngit clone\n.\\setup.exe\n```",
            ReleaseNotes::forCustomers($body, self::ACCOUNTS),
        );
    }

    public function test_a_section_left_with_only_empty_subheadings_goes_too(): void
    {
        $this->assertSame(
            "## Features\n- Fast",
            ReleaseNotes::forCustomers("## Resources\n### Links\n- Source: https://github.com/xjanova/GpuXmine\n\n## Features\n- Fast", self::ACCOUNTS),
        );
    }

    public function test_multi_line_html_comments_are_dropped(): void
    {
        // GitHub ไม่แสดง comment อยู่แล้ว — หน้าเว็บที่แสดงเป็นข้อความเฉย ๆ จะโชว์ "<!--" ดิบ ๆ
        $this->assertSame(
            "เพิ่มโหมดมืด\nแก้บั๊กตอนเปิดแอป",
            ReleaseNotes::forCustomers("เพิ่มโหมดมืด\n<!--\nnote to self: https://github.com/xjanova/BrainX/issues/9\n-->\nแก้บั๊กตอนเปิดแอป", self::ACCOUNTS),
        );
    }

    public function test_a_repository_name_with_a_commit_goes_whole(): void
    {
        $this->assertSame('แก้แล้วใน', ReleaseNotes::forCustomers('แก้แล้วใน xjanova/GpuXmine@a1b2c3d', self::ACCOUNTS));
    }

    public function test_a_full_commit_sha_is_shortened_everywhere(): void
    {
        // SHA เต็มค้นหา commit บน GitHub แล้วเจอ repo — ในข้อความและในบล็อกโค้ด เหลือ 7 ตัวแบบ git log --oneline
        $this->assertSame(
            "- fixed crash (6b3493d)\n```\nCommit: dc0d352\n```",
            ReleaseNotes::forCustomers(
                "- fixed crash (6b3493d0206e6d741a42cf6c46616cd0f85d5b88)\n```\nCommit: dc0d3523af9dbc1e4ab1e71fe35b922700db982f\n```",
                self::ACCOUNTS,
            ),
        );
    }

    public function test_a_broken_byte_does_not_let_a_link_through(): void
    {
        // regex แบบ /u ล้มทั้งบรรทัดเมื่อเจอ byte ที่ไม่ใช่ UTF-8 — ต้องไม่กลายเป็นทางให้ลิงก์หลุดรอด
        $clean = ReleaseNotes::forCustomers("- bad byte \xff here https://github.com/xjanova/GpuXmine/pull/3", self::ACCOUNTS);

        $this->assertStringNotContainsString('github', (string) $clean);
        $this->assertStringContainsString('bad byte', (string) $clean);
    }

    public function test_nothing_in_nothing_out(): void
    {
        $this->assertNull(ReleaseNotes::forCustomers(null, self::ACCOUNTS));
        $this->assertNull(ReleaseNotes::forCustomers('', self::ACCOUNTS));
        $this->assertNull(ReleaseNotes::forCustomers(" \r\n\t", self::ACCOUNTS));
    }

    public function test_cleaning_again_changes_nothing(): void
    {
        // ใช้ทั้งตอน sync, migration และตอนอ่านจาก DB — ผ่านซ้ำต้องได้ผลเท่าเดิม
        $bodies = [
            "## What's Changed\n* Fix crash by @xjanova in https://github.com/xjanova/BrainX/pull/3\n\n**Full Changelog**: https://github.com/xjanova/BrainX/compare/v1...v2",
            "Commit: dc0d3523af9dbc1e4ab1e71fe35b922700db982f\nfix: things\n\nCo-Authored-By: Claude <noreply@anthropic.com>",
            "- See [CHANGELOG.md](https://github.com/xjanova/X/blob/main/CHANGELOG.md) for details\n\n\n\n- ขอบคุณ @xjanova",
        ];

        foreach ($bodies as $body) {
            $once = ReleaseNotes::forCustomers($body, self::ACCOUNTS);

            $this->assertSame($once, ReleaseNotes::forCustomers($once, self::ACCOUNTS));
        }
    }
}
