<?php

namespace App\Http\Controllers;

use App\Models\ProjectProgress;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Support\Attachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The only way to a file attached to a support ticket or a project update. The files live outside
 * the web root (App\Support\Attachments); this hands one to the customer it belongs to or to an
 * admin — the "view-ticket-attachment" and "view-project-attachment" gates — for the customer's
 * ticket and project pages and the admin's.
 */
class AttachmentController extends Controller
{
    /** GET /attachments/tickets/{ticket}/{index} — a file sent with the ticket itself. */
    public function ticket(Request $request, SupportTicket $ticket, string $index): Response
    {
        return $this->serve($request, 'view-ticket-attachment', $ticket, (int) $index, Attachments::TICKETS);
    }

    /** GET /attachments/ticket-replies/{reply}/{index} — a file sent with a reply or an internal note. */
    public function ticketReply(Request $request, TicketReply $reply, string $index): Response
    {
        return $this->serve($request, 'view-ticket-attachment', $reply, (int) $index, Attachments::TICKETS);
    }

    /** GET /attachments/project-progress/{progress}/{index} — a file on a project progress update. */
    public function projectProgress(Request $request, ProjectProgress $progress, string $index): Response
    {
        return $this->serve($request, 'view-project-attachment', $progress, (int) $index, Attachments::PROJECTS);
    }

    private function serve(Request $request, string $ability, Model $owner, int $index, string $directory): Response
    {
        // One answer for "not signed in", "not yours" and "no such file", so walking the ids tells a
        // stranger nothing about which tickets or projects exist or carry files.
        abort_unless($request->user()?->can($ability, $owner), 404);

        return Attachments::response($owner->getAttribute('attachments'), $index, $directory) ?? abort(404);
    }
}
