<?php

namespace App\Support;

use App\Models\ProjectProgress;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Files attached to a support ticket, a ticket reply or a project progress update. Customers attach
 * bank slips, ID cards and screenshots of their accounts, so these never sit under the web root.
 *
 * They used to go to the "public" disk, served at /storage/ticket-attachments/… and
 * /storage/project-attachments/… to anyone holding the address. They now go to the private disk
 * (App\Support\PrivateFiles) and are opened only through AttachmentController: a ticket's files by
 * the customer who owns the ticket — never an internal note's — and a progress update's files by the
 * project's customer when the update is shown to them; an admin opens any of them.
 *
 * A row lists its files as [{name, path, size, type}, …] with each path relative to the disk, the
 * same on either disk, so moving the files (`attachments:privatize`) leaves the rows right.
 */
final class Attachments
{
    /** Tickets and their replies: ticket-attachments/{year}/{month}/… */
    public const TICKETS = 'ticket-attachments';

    /** Project progress updates: project-attachments/{project id}/… */
    public const PROJECTS = 'project-attachments';

    /**
     * Keep uploaded files in $folder on the private disk, as the entries a row lists. An empty slot
     * (a file field left blank arrives as null) is skipped. Null when the disk refused a file; the
     * files already written for this call are removed again.
     *
     * @param  array<UploadedFile|null>  $files
     * @return list<array{name: string, path: string, size: int|false, type: ?string}>|null
     */
    public static function store(array $files, string $folder): ?array
    {
        $entries = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store($folder, PrivateFiles::DISK);
            if (! is_string($path) || $path === '') {
                Storage::disk(PrivateFiles::DISK)->delete(array_column($entries, 'path'));

                return null;
            }
            $entries[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
            ];
        }

        return $entries;
    }

    /**
     * May $user open the files on a ticket or a reply? The customer who owns the ticket may, except
     * on a staff member's internal note; an admin may (App\Support\PrivateFiles::adminMayOpen).
     */
    public static function canViewTicket(User $user, SupportTicket|TicketReply $owner): bool
    {
        $ticket = $owner instanceof TicketReply ? $owner->ticket : $owner;
        $internal = $owner instanceof TicketReply && $owner->is_internal;

        if (! $internal && $ticket?->user_id !== null && (int) $ticket->user_id === (int) $user->id) {
            return true;
        }

        return PrivateFiles::adminMayOpen($user);
    }

    /**
     * May $user open the files on a progress update? The project's customer may when the update is
     * shown to them, as on their project page; an admin may.
     */
    public static function canViewProgress(User $user, ProjectProgress $progress): bool
    {
        $project = $progress->projectOrder;

        if ($progress->is_public && $project?->user_id !== null && (int) $project->user_id === (int) $user->id) {
            return true;
        }

        return PrivateFiles::adminMayOpen($user);
    }

    /**
     * File number $index of a row's list, from $directory, as a response to someone already allowed
     * to see it, saved under the name it was uploaded with. Null when there is no such file.
     */
    public static function response(mixed $attachments, int $index, string $directory): ?StreamedResponse
    {
        $entry = is_array($attachments) ? ($attachments[$index] ?? null) : null;
        if (! is_array($entry) || ! is_string($entry['path'] ?? null)) {
            return null;
        }
        $name = is_string($entry['name'] ?? null) ? pathinfo($entry['name'], PATHINFO_FILENAME) : '';

        return PrivateFiles::response($entry['path'], $directory, $name !== '' ? $name : 'attachment-' . ($index + 1));
    }
}
