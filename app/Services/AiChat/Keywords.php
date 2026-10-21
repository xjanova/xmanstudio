<?php

namespace App\Services\AiChat;

use IntlBreakIterator;

/**
 * The search words in a visitor's question, for matching it against the site.
 *
 * Thai runs words together, so splitting on spaces turned "ราคาTpingเท่าไหร่"
 * into one token that matched nothing. The question is cut where Thai meets
 * Latin or digits first, then each Thai run is split into words with ICU's
 * dictionary (the intl extension, which production and CI both have). Without
 * intl, known filler words are cut out of each Thai run instead: coarser, but
 * the names people ask about (Tping, VPS, BrainX) are Latin anyway.
 */
class Keywords
{
    public const MAX = 12;

    /** Words that say nothing about what the visitor is looking for. */
    private const STOP_WORDS = [
        // Thai particles, question words and fillers
        'ไหม', 'มั้ย', 'มัย', 'ครับ', 'คับ', 'ค่ะ', 'คะ', 'ค่า', 'นะ', 'นะคะ', 'นะครับ', 'จ้า', 'จ้ะ', 'จ๊ะ', 'ฮะ',
        'หน่อย', 'ได้', 'ได้ไหม', 'บ้าง', 'อะไร', 'ยังไง', 'อย่างไร', 'เท่าไหร่', 'เท่าไร', 'เท่า', 'ไหร่', 'กี่',
        'มี', 'เป็น', 'คือ', 'ที่', 'ของ', 'ให้', 'กับ', 'และ', 'หรือ', 'จะ', 'ก็', 'แล้ว', 'สิ', 'เลย', 'ด้วย',
        'กัน', 'ไป', 'มา', 'ถาม', 'อยาก', 'ต้องการ', 'ขอ', 'ช่วย', 'บอก', 'รู้', 'ไหน', 'ตรงไหน', 'เรา', 'ฉัน',
        'ผม', 'หนู', 'คุณ', 'นี้', 'นั้น', 'นี่', 'นั่น', 'อันนี้', 'ตัวนี้', 'แบบ', 'ยัง', 'ไม่', 'ใช่', 'เอง',
        'ทำ', 'การ', 'ความ', 'อยู่', 'แต่', 'ถ้า', 'เพราะ', 'ว่า', 'ซึ่ง', 'โดย', 'จาก', 'ใน', 'บน', 'ต้อง',
        'สวัสดี', 'ขอบคุณ', 'หา', 'เห็น', 'ดู', 'เอา', 'ใคร', 'เมื่อไหร่', 'ทำไม', 'หรอ', 'เหรอ',
        // In a chat these point at the page or ask for a verdict; they name nothing
        'หน้า', 'สุด', 'ที่สุด', 'แนะนำ', 'ตัว', 'อัน', 'ใช้', 'มาก', 'ดี', 'กว่า', 'ดีกว่า', 'ค่อย', 'เหมาะ', 'ไง',
        // English
        'the', 'is', 'a', 'an', 'and', 'or', 'what', 'how', 'do', 'does', 'you', 'have', 'has', 'can', 'about',
        'this', 'that', 'to', 'of', 'for', 'in', 'on', 'it', 'me', 'my', 'your', 'are', 'there', 'any', 'much',
        'please', 'with', 'be', 'i', 'we', 'hi', 'hello', 'thanks',
    ];

    /**
     * Words of the site's trade that ICU's dictionary cuts apart ("แพ็กเกจ"
     * comes out as "แพ็ก|เกจ", "ติดตั้ง" as "ติด|ตั้ง"): taken out of a Thai
     * run whole, before the dictionary sees it. Longest first.
     */
    private const WHOLE_WORDS = [
        'แอพพลิเคชั่น', 'แอปพลิเคชัน', 'ลืมรหัสผ่าน', 'ใบเสนอราคา', 'กระเป๋าเงิน', 'ใบแจ้งหนี้', 'โปรโมชั่น',
        'โปรโมชัน', 'รหัสผ่าน', 'รายเดือน', 'ทดลองใช้', 'แพ็กเกจ', 'แพ็คเกจ', 'ไลเซ้นส์', 'ไลเซนส์', 'บล็อกเชน',
        'ตลอดชีพ', 'ใบเสร็จ', 'ต่ออายุ', 'เติมเงิน', 'แชทบอท', 'ติดตั้ง', 'แอดมิน', 'คลาวด์', 'รายปี',
    ];

    /** @return array<int, string> */
    public static function extract(string $text): array
    {
        $text = mb_strtolower(mb_substr($text, 0, 500));

        // A space wherever Thai meets anything else, so each run stands alone.
        $text = preg_replace('/(?<=\p{Thai})(?=[^\p{Thai}])|(?<=[^\p{Thai}])(?=\p{Thai})/u', ' ', $text) ?? $text;

        $stop = array_flip(self::STOP_WORDS);
        $found = [];

        foreach (preg_split('/[^\p{L}\p{M}\p{N}.+#-]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            $token = trim($token, '.-');

            $words = preg_match('/\p{Thai}/u', $token) ? self::thaiWords($token) : [$token];

            foreach ($words as $word) {
                if (mb_strlen($word) < 2 || isset($stop[$word]) || isset($found[$word])) {
                    continue;
                }

                $found[$word] = true;

                if (count($found) >= self::MAX) {
                    return array_keys($found);
                }
            }
        }

        return array_keys($found);
    }

    /** @return array<int, string> */
    private static function thaiWords(string $run): array
    {
        $whole = [];
        foreach (self::WHOLE_WORDS as $word) {
            if (str_contains($run, $word)) {
                $whole[] = $word;
                $run = str_replace($word, ' ', $run);
            }
        }

        $words = $whole;
        foreach (preg_split('/\s+/u', $run, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $piece) {
            array_push($words, ...self::dictionaryWords($piece));
        }

        return $words;
    }

    /** @return array<int, string> */
    private static function dictionaryWords(string $run): array
    {
        if (class_exists(IntlBreakIterator::class)) {
            $iterator = IntlBreakIterator::createWordInstance('th');

            if ($iterator !== null) {
                $iterator->setText($run);
                $words = [];

                foreach ($iterator->getPartsIterator() as $part) {
                    $part = trim((string) $part);
                    if ($part !== '') {
                        $words[] = $part;
                    }
                }

                return $words;
            }
        }

        // No dictionary: cut the known fillers out, longest first so "เท่าไหร่"
        // goes before "เท่า" does, and keep whatever stands between them.
        $fillers = array_values(array_filter(self::STOP_WORDS, fn (string $w) => (bool) preg_match('/\p{Thai}/u', $w)));
        usort($fillers, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        return preg_split('/\s+/u', str_replace($fillers, ' ', $run), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
