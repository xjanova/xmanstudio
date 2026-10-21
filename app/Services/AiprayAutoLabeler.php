<?php

namespace App\Services;

use App\Models\AiprayChant;

/**
 * Labels app uploads with the text of the chant line they were recorded for.
 *
 * The app sends chant_id and line_index, so the transcript is already known:
 * it is that line of the chant. The label is only as good as the app's line
 * tracking, so these samples get their own status (`auto_labeled`) and a
 * human `verified` still ranks above them.
 */
class AiprayAutoLabeler
{
    public const STATUS = 'auto_labeled';

    /** @var array<string, array<int, string>|null> */
    private array $lines = [];

    public function transcriptFor(?string $chantId, ?int $lineIndex): ?string
    {
        if ($chantId === null || $lineIndex === null || $lineIndex < 0) {
            return null;
        }

        if (! array_key_exists($chantId, $this->lines)) {
            $chant = AiprayChant::where('chant_id', $chantId)->first();
            $this->lines[$chantId] = $chant ? array_values((array) $chant->lines) : null;
        }

        $line = $this->lines[$chantId][$lineIndex] ?? null;
        // Lines are plain strings from the admin form; tolerate {"text": ...} too.
        $text = is_array($line) ? ($line['text'] ?? null) : $line;

        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }
}
