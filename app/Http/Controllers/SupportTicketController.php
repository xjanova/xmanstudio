<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    /**
     * Display list of user's tickets
     */
    public function index(Request $request)
    {
        $query = SupportTicket::byUser(Auth::id())
            ->with(['replies' => function ($q) {
                $q->where('is_internal', false)->latest();
            }])
            ->latest();

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

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $tickets = $query->paginate(10);

        return view('customer.support.index', [
            'tickets' => $tickets,
            'statuses' => SupportTicket::getStatuses(),
            'categories' => SupportTicket::getCategories(),
        ]);
    }

    /**
     * Show create ticket form
     */
    public function create(Request $request)
    {
        $orders = Order::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->latest()
            ->get();

        return view('customer.support.create', [
            'orders' => $orders,
            'categories' => SupportTicket::getCategories(),
            'priorities' => SupportTicket::getPriorities(),
            'preselectedOrder' => $request->order_id,
        ]);
    }

    /**
     * Store a new ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(SupportTicket::getCategories())),
            'priority' => 'required|string|in:' . implode(',', array_keys(SupportTicket::getPriorities())),
            'message' => 'required|string|min:10',
            'order_id' => 'nullable|exists:orders,id',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip',
        ]);

        $user = Auth::user();

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments/' . date('Y/m'), 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
            'order_id' => $validated['order_id'] ?? null,
            'attachments' => $attachments,
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        return redirect()
            ->route('customer.support.show', $ticket)
            ->with('success', 'สร้าง Ticket #' . $ticket->ticket_number . ' เรียบร้อยแล้ว');
    }

    /**
     * Show ticket details
     */
    public function show(SupportTicket $ticket)
    {
        // Ensure user owns the ticket
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load([
            'publicReplies.user',
            'order',
        ]);

        return view('customer.support.show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Add a reply to the ticket
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        // Ensure user owns the ticket
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        // Can't reply to closed tickets
        if ($ticket->isClosed()) {
            return back()->with('error', 'ไม่สามารถตอบกลับ Ticket ที่ปิดแล้วได้');
        }

        $validated = $request->validate([
            'message' => 'required|string|min:3',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip',
        ]);

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments/' . date('Y/m'), 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $ticket->addReply(Auth::user(), $validated['message'], $attachments);

        // Update ticket status to waiting_reply (waiting for staff to reply)
        if ($ticket->status !== SupportTicket::STATUS_OPEN) {
            $ticket->update(['status' => SupportTicket::STATUS_WAITING_REPLY]);
        }

        return back()->with('success', 'ส่งข้อความเรียบร้อยแล้ว');
    }

    /**
     * Close ticket
     */
    public function close(SupportTicket $ticket)
    {
        // Ensure user owns the ticket
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $ticket->close();

        return back()->with('success', 'ปิด Ticket เรียบร้อยแล้ว');
    }

    /**
     * Reopen ticket
     */
    public function reopen(SupportTicket $ticket)
    {
        // Ensure user owns the ticket
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $ticket->reopen();

        return back()->with('success', 'เปิด Ticket ใหม่เรียบร้อยแล้ว');
    }
}
