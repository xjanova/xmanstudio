<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class ChangelogController extends Controller
{
    public function index()
    {
        $changelogPath = base_path('CHANGELOG.md');
        $content = File::exists($changelogPath) ? File::get($changelogPath) : '';

        // Parse the markdown changelog into structured data
        $versions = $this->parseChangelog($content);

        return view('changelog', compact('versions', 'content'));
    }

    private function parseChangelog(string $content): array
    {
        $versions = [];
        $currentVersion = null;
        $currentSection = null;
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            // Match version headers like "## [1.0.225] - 2026-02-12"
            if (preg_match('/^## \[(.+?)\]\s*-\s*(.+)$/', $line, $matches)) {
                if ($currentVersion) {
                    $versions[] = $currentVersion;
                }
                $currentVersion = [
                    'version' => trim($matches[1]),
                    'date' => trim($matches[2]),
                    'sections' => [],
                ];
                $currentSection = null;

                continue;
            }

            // Match section headers like "### Added", "### Changed", etc.
            if (preg_match('/^### (.+)$/', $line, $matches) && $currentVersion) {
                $currentSection = trim($matches[1]);
                $currentVersion['sections'][$currentSection] = [];

                continue;
            }

            // Match list items
            if (preg_match('/^- (.+)$/', $line, $matches) && $currentVersion && $currentSection) {
                $currentVersion['sections'][$currentSection][] = trim($matches[1]);

                continue;
            }

            // Continuation lines (indented under list items)
            if (preg_match('/^  (.+)$/', $line, $matches) && $currentVersion && $currentSection) {
                $items = &$currentVersion['sections'][$currentSection];
                if (! empty($items)) {
                    $items[count($items) - 1] .= "\n" . trim($matches[1]);
                }
            }
        }

        if ($currentVersion) {
            $versions[] = $currentVersion;
        }

        return $versions;
    }
}
