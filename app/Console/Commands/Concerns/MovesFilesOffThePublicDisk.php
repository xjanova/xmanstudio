<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Throwable;

/**
 * Moving a customer's file from the public disk to the private one under the same path, for the
 * commands that take a folder off the web root (`payment-slips:privatize`, `attachments:privatize`).
 *
 * Safe to stop and to run again. Each file is copied under a temporary name and renamed into place,
 * and its public copy is deleted only after the private one reads back with the same SHA-256. When
 * the private disk already holds a different file under that name, both are left alone and
 * reported.
 */
trait MovesFilesOffThePublicDisk
{
    /** @var list<string> */
    private array $problems = [];

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

    private function problem(string $message): false
    {
        $this->problems[] = $message;

        return false;
    }
}
