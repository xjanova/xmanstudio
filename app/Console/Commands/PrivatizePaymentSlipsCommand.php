<?php

namespace App\Console\Commands;

use App\Support\PaymentSlips;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Moves payment slips off the public disk — storage/app/public/payment-slips, which the web server
 * hands to anyone who has the address — onto the private disk under the same path, so the rows
 * pointing at them stay right. Rows that saved the public address ("/storage/payment-slips/…")
 * instead of the path are rewritten to the path.
 *
 * Safe to stop and to run again. Each file is copied under a temporary name and renamed into place,
 * and its public copy is deleted only after the private one reads back with the same SHA-256. When
 * the private disk already holds a different file under that name, both are left alone and
 * reported. Every file in the folder moves, slips no row points at any more included: they are
 * someone's bank details all the same.
 */
class PrivatizePaymentSlipsCommand extends Command
{
    protected $signature = 'payment-slips:privatize {--dry-run : Report what would change without touching anything}';

    protected $description = 'Move payment slips from the public disk to the private one, deleting each public file only after its copy is verified';

    /** @var list<string> */
    private array $problems = [];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $public = Storage::disk(PaymentSlips::LEGACY_DISK);
        $private = Storage::disk(PaymentSlips::DISK);

        $moved = [];
        foreach ($public->allFiles(PaymentSlips::DIRECTORY) as $path) {
            if ($this->move($public, $private, $path, $dry)) {
                $moved[] = $path;
                $this->line(($dry ? '  would move ' : '  moved ') . $path);
            }
        }
        $rows = $this->rewriteRows($dry);

        foreach ($this->problems as $problem) {
            $this->warn($problem);
        }
        $count = count($moved);
        $problems = count($this->problems);
        $this->info($dry
            ? "Dry run: would move {$count} slip(s) to the private disk and rewrite {$rows} row(s); {$problems} problem(s)."
            : "Moved {$count} slip(s) to the private disk and rewrote {$rows} row(s); {$problems} problem(s).");

        if (! $dry && ($count > 0 || $rows > 0 || $problems > 0)) {
            // The paths are the old /storage/… addresses a CDN may still hold in its cache.
            Log::log($problems === 0 ? 'info' : 'warning', 'Payment slips moved off the public disk', [
                'moved' => $count, 'paths' => array_slice($moved, 0, 200), 'rows_rewritten' => $rows, 'problems' => $this->problems,
            ]);
        }

        return $this->problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /** One public file to the private disk. False when it stays where it is. */
    private function move(Filesystem $public, Filesystem $private, string $path, bool $dry): bool
    {
        $hash = $this->hash($public, $path);
        if ($hash === null) {
            return $this->problem("{$path}: the public file cannot be read; left where it is.");
        }

        if ($private->exists($path)) {
            // Copied by an earlier run that stopped before deleting the public file — or a different
            // file under the same name, which a person has to look at.
            if ($this->hash($private, $path) !== $hash) {
                return $this->problem("{$path}: the private disk holds a different file under this name; both left alone.");
            }
        } elseif (! $dry && ! $this->copy($public, $private, $path, $hash)) {
            return $this->problem("{$path}: the private copy could not be written and verified; the public file is left where it is.");
        }

        if (! $dry && ! $public->delete($path)) {
            return $this->problem("{$path}: copied and verified, but the public file could not be deleted; run again.");
        }

        return true;
    }

    /** Copy under a temporary name, rename into place, then read it back. Only this copy is removed on failure. */
    private function copy(Filesystem $from, Filesystem $to, string $path, string $hash): bool
    {
        $temporary = $path . '.' . Str::lower(Str::random(8)) . '.part';
        $stream = null;
        $placed = false;
        try {
            $stream = $from->readStream($path);
            if (is_resource($stream) && $to->writeStream($temporary, $stream)) {
                $placed = $to->move($temporary, $path);
                if ($placed && $this->hash($to, $path) === $hash) {
                    return true;
                }
            }
        } catch (Throwable) {
            // Reported by the caller; the cleanup below runs either way.
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        $to->delete($placed ? [$temporary, $path] : [$temporary]);

        return false;
    }

    private function hash(Filesystem $disk, string $path): ?string
    {
        try {
            $stream = $disk->readStream($path);
        } catch (Throwable) {
            return null;
        }
        if (! is_resource($stream)) {
            return null;
        }
        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
    }

    /**
     * Rows that saved the public address in place of the path. None are known — every upload stored
     * the path — but a hand-edited or imported row would otherwise point at an address that no
     * longer serves anything.
     */
    private function rewriteRows(bool $dry): int
    {
        $rewritten = 0;

        // orders.payment_slip is missing on databases from before 2026_10_09_200001. By id, not by
        // page: a rewritten row drops out of the filter, and paging by offset would skip the next.
        foreach (['orders' => 'payment_slip', 'rental_payments' => 'transfer_slip_url'] as $table => $column) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }
            DB::table($table)->select(['id', $column])->whereNotNull($column)->where($column, '!=', '')
                ->where($column, 'not like', PaymentSlips::DIRECTORY . '/%')
                ->eachById(function (object $row) use ($table, $column, $dry, &$rewritten) {
                    $path = PaymentSlips::normalize($row->{$column});
                    if ($path === null) {
                        $this->problem("{$table} #{$row->id}: {$column} is not a slip path; left as it is.");

                        return;
                    }
                    if (! $dry) {
                        DB::table($table)->where('id', $row->id)->update([$column => $path]);
                    }
                    $rewritten++;
                });
        }

        // Product checkouts keep the slip in orders.metadata, often as JSON text inside the JSON.
        DB::table('orders')->select(['id', 'metadata'])->where('metadata', 'like', '%' . PaymentSlips::DIRECTORY . '%')
            ->eachById(function (object $row) use ($dry, &$rewritten) {
                [$metadata, $depth] = self::decode($row->metadata);
                $stored = $metadata['payment_slip'] ?? null;
                if (! is_string($stored) || $stored === '' || str_starts_with($stored, PaymentSlips::DIRECTORY . '/')) {
                    return;
                }
                $path = PaymentSlips::normalize($stored);
                if ($path === null) {
                    $this->problem("orders #{$row->id}: metadata payment_slip is not a slip path; left as it is.");

                    return;
                }
                $metadata['payment_slip'] = $path;
                if (! $dry) {
                    DB::table('orders')->where('id', $row->id)->update(['metadata' => self::encode($metadata, $depth)]);
                }
                $rewritten++;
            });

        return $rewritten;
    }

    /**
     * Metadata as an array, and how many times it was JSON-encoded (twice when a checkout saved
     * json_encode()d text into the array-cast column), so it can be written back the same way.
     *
     * @return array{0: array<string, mixed>, 1: int}
     */
    private static function decode(?string $raw): array
    {
        $value = $raw;
        $depth = 0;
        while (is_string($value) && $depth < 3) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            $value = $decoded;
            $depth++;
        }

        return [is_array($value) ? $value : [], $depth];
    }

    /** @param array<string, mixed> $metadata */
    private static function encode(array $metadata, int $depth): string
    {
        $value = $metadata;
        for ($i = 0; $i < max(1, $depth); $i++) {
            $value = json_encode($value);
        }

        return (string) $value;
    }

    private function problem(string $message): false
    {
        $this->problems[] = $message;

        return false;
    }
}
