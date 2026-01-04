<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    /**
     * Display ticket list
     */
    public function index(Request $request)
    {
        $query = SupportTicket::with(['user', 'assignedTo', 'lastReplyBy'])
            ->withCount(['replies' => function ($q) {
                $q->where('is_internal', false);
            }]);

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->inProgress();
            } elseif ($request->status === 'closed') {
                $query->closed();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        // Filter by assigned
        if ($request->filled('assigned')) {
            if ($request->assigned === 'me') {
                $query->assignedTo(Auth::id());
            } elseif ($request->assigned === 'unassigned') {
                $query->unassigned();
            } else {
                $query->assignedTo($request->assigned);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Default sort by priority and date
        $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByRaw("FIELD(status, 'open', 'in_progress', 'waiting_reply', 'resolved', 'closed')")
            ->orderBy('created_at', 'desc');

        $tickets = $query->paginate(20);

        // Get staff for assignment filter
        $staff = User::whereIn('role', ['admin', 'staff', 'support'])->get();

        // Stats
        $stats = [
            'open' => SupportTicket::open()->count(),
            'in_progress' => SupportTicket::whereIn('status', [
                SupportTicket::STATUS_IN_PROGRESS,
                SupportTicket::STATUS_WAITING_REPLY,
            ])->count(),
            'unassigned' => SupportTicket::unassigned()->inProgress()->count(),
            'urgent' => SupportTicket::where('priority', 'urgent')->inProgress()->count(),
        ];

        return view('admin.support.index', [
            'tickets' => $tickets,
            'statuses' => SupportTicket::getStatuses(),
            'categories' => SupportTicket::getCategories(),
            'priorities' => SupportTicket::getPriorities(),
            'staff' => $staff,
            'stats' => $stats,
        ]);
    }

    /**
     * Show ticket details
     */
    public function show(SupportTicket $ticket)
    {
        $ticket->load([
            'user',
            'order.items.product',
            'assignedTo',
            'replies.user',
        ]);

        // Get staff for assignment
        $staff = User::whereIn('role', ['admin', 'staff', 'support'])->get();

        return view('admin.support.show', [
            'ticket' => $ticket,
            'statuses' => SupportTicket::getStatuses(),
            'priorities' => SupportTicket::getPriorities(),
            'staff' => $staff,
        ]);
    }

    /**
     * Add a reply to the ticket
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:3',
            'is_internal' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip',
        ]);

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments/'.date('Y/m'), 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $isInternal = $request->boolean('is_internal');
        $ticket->addReply(Auth::user(), $validated['message'], $attachments, $isInternal);

        // Update ticket status
        if (! $isInternal && $ticket->status === SupportTicket::STATUS_OPEN) {
            $ticket->update([
                'status' => SupportTicket::STATUS_IN_PROGRESS,
                'responded_at' => now(),
            ]);
        } elseif (! $isInternal && $ticket->status === SupportTicket::STATUS_WAITING_REPLY) {
            $ticket->update(['status' => SupportTicket::STATUS_IN_PROGRESS]);
        }

        // Auto-assign to current admin if unassigned
        if (empty($ticket->assigned_to)) {
            $ticket->assignTo(Auth::user());
        }

        $message = $isInternal ? 'บันทึก Internal Note เรียบร้อยแล้ว' : 'ส่งข้อความเรียบร้อยแล้ว';

        return back()->with('success', $message);
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(SupportTicket::getStatuses())),
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'closed_at' => in_array($validated['status'], [
                SupportTicket::STATUS_RESOLVED,
                SupportTicket::STATUS_CLOSED,
            ]) ? now() : null,
        ]);

        return back()->with('success', 'อัพเดทสถานะเรียบร้อยแล้ว');
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'priority' => 'required|string|in:'.implode(',', array_keys(SupportTicket::getPriorities())),
        ]);

        $ticket->update(['priority' => $validated['priority']]);

        return back()->with('success', 'อัพเดท Priority เรียบร้อยแล้ว');
    }

    /**
     * Assign ticket to staff
     */
    public function assign(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validated['assigned_to']) {
            $user = User::find($validated['assigned_to']);
            $ticket->assignTo($user);
            $message = 'มอบหมายให้ '.$user->name.' เรียบร้อยแล้ว';
        } else {
            $ticket->update(['assigned_to' => null]);
            $message = 'ยกเลิกการมอบหมายเรียบร้อยแล้ว';
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk update tickets
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:support_tickets,id',
            'action' => 'required|string|in:close,resolve,assign,delete',
            'assigned_to' => 'nullable|required_if:action,assign|exists:users,id',
        ]);

        $tickets = SupportTicket::whereIn('id', $validated['ticket_ids']);
        $count = $tickets->count();

        switch ($validated['action']) {
            case 'close':
                $tickets->update([
                    'status' => SupportTicket::STATUS_CLOSED,
                    'closed_at' => now(),
                ]);
                $message = "ปิด {$count} Ticket เรียบร้อยแล้ว";
                break;

            case 'resolve':
                $tickets->update([
                    'status' => SupportTicket::STATUS_RESOLVED,
                    'closed_at' => now(),
                ]);
                $message = "แก้ไข {$count} Ticket เรียบร้อยแล้ว";
                break;

            case 'assign':
                $tickets->update([
                    'assigned_to' => $validated['assigned_to'],
                    'status' => SupportTicket::STATUS_IN_PROGRESS,
                ]);
                $user = User::find($validated['assigned_to']);
                $message = "มอบหมาย {$count} Ticket ให้ {$user->name} เรียบร้อยแล้ว";
                break;

            case 'delete':
                $tickets->delete();
                $message = "ลบ {$count} Ticket เรียบร้อยแล้ว";
                break;

            default:
                $message = 'ไม่มีการดำเนินการ';
        }

        return back()->with('success', $message);
    }
}
