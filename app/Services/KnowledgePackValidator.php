<?php

namespace App\Services;

use Illuminate\Support\Str;
use stdClass;
use ZipArchive;

/**
 * Checks a knowledge pack zip the way the GigGok app will, before anyone can pay for it.
 *
 * The app refuses to install a pack that breaks any of these rules (lib/store/knowledge_pack.dart
 * and docs/knowledge-packs.md in the app repo). A zip that gets past this screen but not past the
 * app is a customer who paid and got an install error, so every rule the app enforces is repeated
 * here. A few are stricter: where the app quietly trims (a persona that runs long) or guesses (a
 * missing category becomes "general"), an admin can still fix the manifest and a customer cannot.
 *
 * A pack is data only - manifest.json, text and images. Any other file refuses the whole pack.
 * Nothing is extracted to disk: each file is read in memory, never past its limit.
 */
class KnowledgePackValidator
{
    /**
     * The app adds its own safety rules per category. An investing pack filed as "general"
     * escapes every one of them, which is why the admin form asks a person to check it.
     */
    public const CATEGORIES = ['survival', 'first_aid', 'engine', 'cooking', 'trading', 'general'];

    public const MAX_ZIP_BYTES = 8 * 1024 * 1024;

    /** What the zip says it unpacks to, all files together - a zip bomb stops here. */
    public const MAX_UNPACKED_BYTES = 24 * 1024 * 1024;

    public const MAX_TEXT_FILE_BYTES = 512 * 1024;

    public const MAX_MANIFEST_BYTES = 64 * 1024;

    /** Searchable content, measured on the sections the app cuts it into (see sections()). */
    public const MAX_CONTENT_CHARS = 400000;

    public const MAX_PERSONA = 300;

    public const MAX_SAFETY_RULES = 4;

    public const MAX_SAFETY_RULE = 160;

    public const MAX_STARTERS = 6;

    public const MAX_STARTER = 120;

    /** product_versions.version is shared with every product; the admin form caps it at 50 too. */
    public const MAX_VERSION = 50;

    /** Illustrations on the reading page, not a photo library. */
    public const MAX_IMAGES = 120;

    public const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

    /**
     * Pixels per side. Matters more than file size: a flat-colour PNG of 2 MB can declare
     * 30000x30000 and unpack to gigabytes when the phone draws it.
     */
    public const MAX_IMAGE_SIDE = 4096;

    /** The types the app decodes. Which of the three sits behind which extension does not matter. */
    public const IMAGE_TYPES = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP];

    /**
     * @return array{version: string, category: string}
     *
     * @throws KnowledgePackException with a Thai message that says what to fix
     */
    public function validate(string $path, string $packId): array
    {
        $size = @filesize($path);

        if ($size === false) {
            throw new KnowledgePackException('อ่านไฟล์ที่อัปโหลดไม่ได้ — ลองอัปโหลดใหม่');
        }

        if ($size > self::MAX_ZIP_BYTES) {
            throw new KnowledgePackException(sprintf(
                'ไฟล์ zip ใหญ่ %s เกินเพดาน %s ของแพ็กความรู้ — แอปจะหยุดโหลดกลางทาง',
                $this->megabytes($size),
                $this->megabytes(self::MAX_ZIP_BYTES)
            ));
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new KnowledgePackException('เปิดไฟล์ zip ไม่ได้ — ไฟล์เสียหรือไม่ใช่ zip');
        }

        try {
            return $this->inspect($zip, $packId);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{version: string, category: string}
     */
    protected function inspect(ZipArchive $zip, string $packId): array
    {
        $entries = $this->entries($zip);
        $manifestIndex = null;

        foreach ($entries as $entry) {
            if ($entry['name'] === 'manifest.json') {
                $manifestIndex = $entry['index'];
            }
        }

        if ($manifestIndex === null) {
            throw new KnowledgePackException(
                'ไม่มี manifest.json ที่รากของ zip (หรือในโฟลเดอร์ครอบชั้นเดียว) — แพ็กความรู้ใช้ manifest.json ไม่ใช่ pack.json ของชุด'
            );
        }

        $texts = [];
        $images = [];

        foreach ($entries as $entry) {
            if ($entry['name'] === 'manifest.json') {
                continue;
            }

            if (preg_match('/\.(png|jpe?g|webp)$/i', $entry['name'])) {
                $images[] = $entry;

                continue;
            }

            if (! preg_match('/\.(md|markdown|txt)$/i', $entry['name'])) {
                throw new KnowledgePackException(sprintf(
                    'มีไฟล์ "%s" ที่แพ็กความรู้ไม่รับ — รับแค่ manifest.json ข้อความ .md .markdown .txt และรูป .png .jpg .jpeg .webp (แพ็กมีโค้ดไม่ได้)',
                    $entry['name']
                ));
            }

            $texts[] = $entry;
        }

        $raw = $this->read($zip, $manifestIndex, 'manifest.json', self::MAX_MANIFEST_BYTES);

        if (! json_decode($raw) instanceof stdClass) {
            throw new KnowledgePackException(sprintf(
                'manifest.json อ่านเป็น JSON object ไม่ได้ (%s)',
                json_last_error() === JSON_ERROR_NONE ? 'ไม่ใช่ object' : json_last_error_msg()
            ));
        }

        $result = $this->checkManifest(json_decode($raw, true), $packId);

        $this->checkContent($zip, $texts);
        $this->checkImages($zip, $images);

        return $result;
    }

    /**
     * Every file in the zip with junk skipped and one wrapper folder unwrapped - the list the app
     * ends up with. Pairs rather than an array keyed by name: PHP turns a key like "123" into an int.
     *
     * @return list<array{name: string, index: int}>
     */
    protected function entries(ZipArchive $zip): array
    {
        $entries = [];
        $declared = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if ($stat === false) {
                throw new KnowledgePackException('อ่านรายชื่อไฟล์ใน zip ไม่ได้ — ไฟล์อาจเสีย');
            }

            $name = str_replace('\\', '/', $stat['name']);

            // What macOS and Windows add when zipping, not the author's doing - matched as the app matches it
            if (str_starts_with($name, '__MACOSX/') || str_ends_with($name, '.DS_Store') || str_ends_with($name, 'Thumbs.db')) {
                continue;
            }

            if (str_starts_with($name, '/') || str_contains($name, ':') || in_array('..', explode('/', $name), true)) {
                throw new KnowledgePackException(sprintf(
                    'path "%s" ใน zip ไม่ปลอดภัย (มี .. ขึ้นต้นด้วย / หรือมี :) — แอปจะปฏิเสธทั้งแพ็ก',
                    $name
                ));
            }

            if ($this->isSymlink($zip, $i)) {
                throw new KnowledgePackException(sprintf('"%s" เป็น symlink — แพ็กต้องมีแต่ไฟล์จริง', $name));
            }

            if (str_ends_with($name, '/')) {
                continue; // a folder
            }

            $declared += (int) $stat['size'];
            $entries[] = ['name' => $name, 'index' => $i];
        }

        if ($declared > self::MAX_UNPACKED_BYTES) {
            throw new KnowledgePackException(sprintf(
                'zip บอกว่าแตกแล้วได้ %s เกินเพดาน %s — แอปจะปฏิเสธทั้งแพ็ก',
                $this->megabytes($declared),
                $this->megabytes(self::MAX_UNPACKED_BYTES)
            ));
        }

        // Zipping a folder with right-click on Windows wraps everything one level deep. Like the
        // app, unwrap it when there is no manifest.json at the root and every file shares one folder.
        $names = array_column($entries, 'name');

        if (! in_array('manifest.json', $names, true)) {
            $tops = array_unique(array_map(
                fn (string $name) => str_contains($name, '/') ? Str::before($name, '/') . '/' : '',
                $names
            ));

            if (count($tops) === 1 && reset($tops) !== '') {
                $cut = strlen(reset($tops));
                $entries = array_map(
                    fn (array $entry) => ['name' => substr($entry['name'], $cut), 'index' => $entry['index']],
                    $entries
                );
            }
        }

        return $entries;
    }

    /**
     * @param  array<mixed>  $manifest
     * @return array{version: string, category: string}
     */
    protected function checkManifest(array $manifest, string $packId): array
    {
        $kind = is_string($manifest['kind'] ?? null) ? trim($manifest['kind']) : '';

        if ($kind !== 'knowledge') {
            throw new KnowledgePackException(sprintf(
                'kind ใน manifest.json คือ "%s" — แพ็กความรู้ต้องเป็น "knowledge" ไม่งั้นแอปไม่ติดตั้ง',
                $kind
            ));
        }

        $id = is_string($manifest['id'] ?? null) ? trim($manifest['id']) : '';

        if ($id !== $packId) {
            throw new KnowledgePackException(sprintf(
                'id ใน manifest.json คือ "%s" แต่แพ็กนี้ขายในชื่อ "%s" — แอปจะโหลดมาแล้วไม่ยอมติดตั้ง',
                $id,
                $packId
            ));
        }

        // A string, not a number: JSON 1.0 reaches PHP as 1 and the app as "1.0", and two versions
        // that differ only in spelling show an update button that never goes away.
        $version = $manifest['version'] ?? null;

        if (! is_string($version) || trim($version) === '') {
            throw new KnowledgePackException(
                'manifest.json ต้องมี "version" เป็นข้อความในเครื่องหมายคำพูด เช่น "1.0.0" — ร้านใช้เลขนี้บอกแอปว่ามีรุ่นใหม่'
            );
        }

        $version = trim($version);

        if (mb_strlen($version) > self::MAX_VERSION) {
            throw new KnowledgePackException(sprintf('version ใน manifest.json ยาวเกิน %d ตัวอักษร', self::MAX_VERSION));
        }

        $category = is_string($manifest['category'] ?? null) ? strtolower(trim($manifest['category'])) : '';

        if (! in_array($category, self::CATEGORIES, true)) {
            throw new KnowledgePackException(sprintf(
                'category "%s" ใช้ไม่ได้ — ต้องเป็นหนึ่งใน %s · แพ็กเรื่องลงทุน เทรด หรือคริปโต ต้องใช้ "trading" แอปจึงใส่กฎห้ามชี้ซื้อขายให้',
                $category,
                implode(' ', self::CATEGORIES)
            ));
        }

        foreach ($this->perLanguage($manifest['persona'] ?? null) as $language => $persona) {
            if (! is_string($persona)) {
                continue; // the app reads anything else as no persona
            }

            // The app squeezes runs of whitespace before it measures
            $length = $this->length((string) preg_replace('/\s+/u', ' ', trim($persona)));

            if ($length > self::MAX_PERSONA) {
                throw new KnowledgePackException(sprintf(
                    '%s ยาว %d ตัวอักษร เกินเพดาน %d — เข้า system prompt ทุกข้อความ ส่วนเกินแอปตัดทิ้ง',
                    $this->label('persona', $language),
                    $length,
                    self::MAX_PERSONA
                ));
            }
        }

        $this->checkList($manifest['safety'] ?? null, 'safety', self::MAX_SAFETY_RULES, self::MAX_SAFETY_RULE);
        $this->checkList($manifest['starters'] ?? null, 'starters', self::MAX_STARTERS, self::MAX_STARTER);

        return ['version' => $version, 'category' => $category];
    }

    protected function checkList(mixed $value, string $field, int $maxItems, int $maxLength): void
    {
        foreach ($this->perLanguage($value) as $language => $list) {
            if (! is_array($list)) {
                continue; // the app reads anything but a list as an empty one
            }

            // Counted as the app counts them: blank and non-text entries are dropped first
            $items = array_values(array_filter(
                array_map(fn ($item) => is_string($item) ? trim($item) : '', $list),
                fn (string $item) => $item !== ''
            ));

            if (count($items) > $maxItems) {
                throw new KnowledgePackException(sprintf(
                    '%s มี %d ข้อ เกินเพดาน %d ข้อ — ข้อที่เกินแอปตัดทิ้ง',
                    $this->label($field, $language),
                    count($items),
                    $maxItems
                ));
            }

            foreach ($items as $item) {
                if ($this->length($item) > $maxLength) {
                    throw new KnowledgePackException(sprintf(
                        '%s ข้อ "%s…" ยาว %d ตัวอักษร เกินเพดาน %d — ส่วนเกินแอปตัดทิ้ง',
                        $this->label($field, $language),
                        mb_substr($item, 0, 30),
                        $this->length($item),
                        $maxLength
                    ));
                }
            }
        }
    }

    /**
     * @param  list<array{name: string, index: int}>  $texts
     */
    protected function checkContent(ZipArchive $zip, array $texts): void
    {
        $sections = 0;
        $chars = 0;

        foreach ($texts as $entry) {
            // Read every text file, in content/ or not: the cap holds wherever a file sits
            $body = $this->read($zip, $entry['index'], $entry['name'], self::MAX_TEXT_FILE_BYTES);

            if (! str_starts_with($entry['name'], 'content/')) {
                continue; // carried along (a README), never searched
            }

            $title = (string) preg_replace('/\.[^.]+$/', '', Str::afterLast($entry['name'], '/'));

            foreach ($this->sections($title, $body) as [$heading, $text]) {
                $sections++;
                $chars += $this->length($heading) + $this->length($text);
            }
        }

        if ($sections === 0) {
            throw new KnowledgePackException(
                'ไม่มีเนื้อหาในโฟลเดอร์ content/ — ต้องมีไฟล์ .md ที่มีข้อความอย่างน้อยหนึ่งไฟล์ แอปจึงมีอะไรให้ค้น'
            );
        }

        if ($chars > self::MAX_CONTENT_CHARS) {
            throw new KnowledgePackException(sprintf(
                'เนื้อหาใน content/ รวม %s ตัวอักษร เกินเพดาน %s — แบ่งเป็นสองแพ็กหรือตัดให้สั้นลง',
                number_format($chars),
                number_format(self::MAX_CONTENT_CHARS)
            ));
        }
    }

    /**
     * The app installs the images and draws them on the reading page, so each one is opened the
     * way the app opens it - anywhere in the zip, not only under content/.
     *
     * @param  list<array{name: string, index: int}>  $images
     */
    protected function checkImages(ZipArchive $zip, array $images): void
    {
        if (count($images) > self::MAX_IMAGES) {
            throw new KnowledgePackException(sprintf(
                'มีรูป %d รูป เกินเพดาน %d รูปต่อแพ็ก — รูปเป็นภาพประกอบในหน้าอ่าน ไม่ใช่คลังรูป',
                count($images),
                self::MAX_IMAGES
            ));
        }

        foreach ($images as $entry) {
            $info = @getimagesizefromstring($this->bytes($zip, $entry['index'], $entry['name'], self::MAX_IMAGE_BYTES));

            // A GIF, or a script renamed to .png
            if ($info === false || ! in_array($info[2], self::IMAGE_TYPES, true)) {
                throw new KnowledgePackException(sprintf(
                    '"%s" ไม่ใช่รูป PNG JPEG หรือ WebP จริง (ดูจากเนื้อไฟล์ ไม่ใช่นามสกุล) — แอปจะปฏิเสธทั้งแพ็ก',
                    $entry['name']
                ));
            }

            if ($info[0] > self::MAX_IMAGE_SIDE || $info[1] > self::MAX_IMAGE_SIDE) {
                throw new KnowledgePackException(sprintf(
                    '"%s" ขนาด %d×%d พิกเซล เกินเพดาน %d พิกเซลต่อด้าน — ย่อรูปก่อน รูปใหญ่ขนาดนี้ทำแอปค้างตอนแสดง',
                    $entry['name'],
                    $info[0],
                    $info[1],
                    self::MAX_IMAGE_SIDE
                ));
            }
        }
    }

    /**
     * One file's text, never reading more than one byte past $cap.
     */
    protected function read(ZipArchive $zip, int $index, string $name, int $cap): string
    {
        $body = $this->bytes($zip, $index, $name, $cap);

        // Notepad's old "ANSI" is TIS-620 for Thai. The app decodes as UTF-8 regardless, and would
        // install a pack whose every Thai letter is garbage - searchable by nobody.
        if (! mb_check_encoding($body, 'UTF-8')) {
            throw new KnowledgePackException(sprintf(
                '"%s" ไม่ได้บันทึกเป็น UTF-8 (น่าจะเป็น ANSI/TIS-620) — ภาษาไทยจะเพี้ยนในแอป ให้บันทึกใหม่เป็น UTF-8',
                $name
            ));
        }

        if (str_starts_with($body, "\u{FEFF}")) {
            $body = substr($body, 3);
        }

        return str_replace("\r\n", "\n", $body);
    }

    /**
     * One file's bytes, never reading more than one byte past $cap.
     *
     * The sizes a zip declares can lie - a file that claims 1 KB can unpack to gigabytes - so the
     * bytes actually read are what count. The app reads them the same way.
     */
    protected function bytes(ZipArchive $zip, int $index, string $name, int $cap): string
    {
        $stat = $zip->statIndex($index);

        if ($stat === false || (int) $stat['size'] > $cap) {
            throw new KnowledgePackException(sprintf(
                '"%s" ใหญ่ %s เกินเพดาน %s ต่อไฟล์',
                $name,
                $this->kilobytes((int) ($stat['size'] ?? 0)),
                $this->kilobytes($cap)
            ));
        }

        $body = $zip->getFromIndex($index, $cap + 1);

        if ($body === false) {
            throw new KnowledgePackException(sprintf('แตกไฟล์ "%s" ไม่ได้ — zip อาจเสีย', $name));
        }

        if (strlen($body) > $cap) {
            throw new KnowledgePackException(sprintf(
                '"%s" แตกออกมาใหญ่กว่าที่ zip บอกไว้ และเกินเพดาน %s ต่อไฟล์',
                $name,
                $this->kilobytes($cap)
            ));
        }

        return $body;
    }

    /**
     * The sections the app cuts a markdown file into (KnowledgePacks.markdownSections), as
     * [heading, text]. The character cap is measured on these, not on the raw file: a heading is
     * kept once as a title, and text before the first heading takes the file name as its title.
     *
     * @return list<array{0: string, 1: string}>
     */
    protected function sections(string $fallbackTitle, string $markdown): array
    {
        $sections = [];
        $title = $fallbackTitle;
        $buffer = '';

        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^#{1,3}\s+(.+?)\s*#*\s*$/u', $line, $match)) {
                if (($text = trim($buffer)) !== '') {
                    $sections[] = [$title, $text];
                }

                $title = trim($match[1]);
                $buffer = '';

                continue;
            }

            $buffer .= $line . "\n";
        }

        if (($text = trim($buffer)) !== '') {
            $sections[] = [$title, $text];
        }

        return $sections;
    }

    /**
     * A manifest field holding either one value for both languages or {"th": …, "en": …}, as the
     * app reads it. One value is checked once.
     *
     * @return array<string, mixed>
     */
    protected function perLanguage(mixed $value): array
    {
        if (is_array($value) && ! array_is_list($value)) {
            return ['th' => $value['th'] ?? null, 'en' => $value['en'] ?? null];
        }

        return ['' => $value];
    }

    /**
     * A Unix symlink entry: file type S_IFLNK in the top half of the external attributes.
     */
    protected function isSymlink(ZipArchive $zip, int $index): bool
    {
        $opsys = 0;
        $attributes = 0;

        if (! $zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            return false;
        }

        return $opsys === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0xF000) === 0xA000;
    }

    protected function label(string $field, string $language): string
    {
        return $language === '' ? $field : $field . ' (' . $language . ')';
    }

    /**
     * Length as the app measures it: Dart strings count UTF-16 code units, so an emoji is two.
     * Thai is one either way.
     */
    protected function length(string $text): int
    {
        return intdiv(strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }

    protected function megabytes(int $bytes): string
    {
        return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
    }

    protected function kilobytes(int $bytes): string
    {
        return number_format((int) ceil($bytes / 1024)) . ' KB';
    }
}
