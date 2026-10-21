<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MovesFilesOffThePublicDisk;
use App\Support\Attachments;
use App\Support\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Moves support-ticket and project attachments off the public disk —
 * storage/app/public/ticket-attachments and …/project-attachments, which the web server hands to
 * anyone who has the address — onto the private disk under the same path, so the rows listing them
 * stay right. A listed path saved as the public address ("/storage/ticket-attachments/…") instead
 * is rewritten to the path.
 *
 * Safe to stop and to run again: a public file is deleted only after its private copy reads back
 * with the same SHA-256 (Concerns\MovesFilesOffThePublicDisk). Every file in both folders moves,
 * files no row lists any more included: a customer's slip or ID card all the same.
 */
class PrivatizeAttachmentsCommand extends Command
{
    use MovesFilesOffThePublicDisk;

    protected $signature = 'attachments:privatize {--dry-run : Report what would change without touching anything}';

    protected $description = 'Move support-ticket and project attachments from the public disk to the private one, deleting each public file only after its copy is verified';

    /** The tables whose rows list attachments, and the folder their files belong in. */
    private const ROWS = [
        'support_tickets' => Attachments::TICKETS,
        'ticket_replies' => Attachments::TICKETS,
        'project_progress' => Attachments::PROJECTS,
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $public = Storage::disk(PrivateFiles::LEGACY_DISK);
        $private = Storage::disk(PrivateFiles::DISK);

        $moved = [];
        foreach ([Attachments::TICKETS, Attachments::PROJECTS] as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                if ($this->move($public, $private, $path, $dry)) {
                    $moved[] = $path;
                    $this->line(($dry ? '  would move ' : '  moved ') . $path);
                }
            }
        }
        $rows = $this->rewriteRows($dry);

        foreach ($this->problems as $problem) {
            $this->warn($problem);
        }
        $count = count($moved);
        $problems = count($this->problems);
        $this->info($dry
            ? "Dry run: would move {$count} attachment(s) to the private disk and rewrite {$rows} row(s); {$problems} problem(s)."
            : "Moved {$count} attachment(s) to the private disk and rewrote {$rows} row(s); {$problems} problem(s).");

        if (! $dry && ($count > 0 || $rows > 0 || $problems > 0)) {
            // The paths are the old /storage/… addresses a CDN may still hold in its cache.
            Log::log($problems === 0 ? 'info' : 'warning', 'Ticket and project attachments moved off the public disk', [
                'moved' => $count, 'paths' => array_slice($moved, 0, 200), 'rows_rewritten' => $rows, 'problems' => $this->problems,
            ]);
        }

        return $this->problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Rows listing a path saved as the public address in place of the path. None are known — every
     * upload stored the path — but a hand-edited or imported row would otherwise list an address
     * that no longer serves anything. A listed path that is not in the row's folder at all is
     * reported and left as it is; the attachment route will not open it.
     */
    private function rewriteRows(bool $dry): int
    {
        $rewritten = 0;

        foreach (self::ROWS as $table => $directory) {
            if (! Schema::hasColumn($table, 'attachments')) {
                continue;
            }
            DB::table($table)->select(['id', 'attachments'])->whereNotNull('attachments')
                ->eachById(function (object $row) use ($table, $directory, $dry, &$rewritten) {
                    $entries = json_decode((string) $row->attachments, true);
                    if (! is_array($entries)) {
                        $this->problem("{$table} #{$row->id}: attachments is not a list; left as it is.");

                        return;
                    }

                    $changed = false;
                    foreach ($entries as $i => $entry) {
                        $stored = is_array($entry) ? ($entry['path'] ?? null) : null;
                        $path = is_string($stored) ? PrivateFiles::normalize($stored, $directory) : null;
                        if ($path === null) {
                            $this->problem("{$table} #{$row->id}: attachment {$i} is not a path in {$directory}/; left as it is.");
                        } elseif ($path !== $stored) {
                            $entries[$i]['path'] = $path;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        if (! $dry) {
                            DB::table($table)->where('id', $row->id)->update(['attachments' => json_encode($entries)]);
                        }
                        $rewritten++;
                    }
                });
        }

        return $rewritten;
    }
}
