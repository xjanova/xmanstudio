<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LineNotifyService;
use Illuminate\Http\Request;

class LineMessagingController extends Controller
{
    public function __construct(
        protected LineNotifyService $lineService
    ) {}

    /**
     * หน้าส่งข้อความ Line
     * GET /admin/line-messaging
     */
    public function index()
    {
        $usersWithLine = User::withLineUid()
            ->select('id', 'name', 'email', 'line_uid', 'line_display_name')
            ->orderBy('name')
            ->get();

        $isConfigured = $this->lineService->isConfigured();

        return view('admin.line-messaging.index', compact('usersWithLine', 'isConfigured'));
    }

    /**
     * ค้นหา User ที่มี Line UID
     * GET /admin/line-messaging/search
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $users = User::withLineUid()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('line_uid', 'like', "%{$query}%")
                    ->orWhere('line_display_name', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'email', 'line_uid', 'line_display_name')
            ->limit(20)
            ->get();

        return response()->json($users);
    }

    /**
     * ส่งข้อความ Line
     * POST /admin/line-messaging/send
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipients' => 'required|string',
            'message' => 'required|string|max:5000',
        ]);

        // รองรับทั้ง Line UID แบบ comma-separated และ user IDs
        $recipientInput = $validated['recipients'];
        $message = $validated['message'];

        // แยก recipients ด้วย comma
        $recipientList = array_map('trim', explode(',', $recipientInput));
        $recipientList = array_filter($recipientList);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($recipientList as $recipient) {
            $lineUid = $recipient;

            // ถ้าเป็นตัวเลข (user ID) ให้ดึง line_uid จาก database
            if (is_numeric($recipient)) {
                $user = User::find($recipient);
                if ($user && $user->line_uid) {
                    $lineUid = $user->line_uid;
                } else {
                    $failedCount++;
                    $errors[] = "User ID {$recipient} ไม่มี Line UID";

                    continue;
                }
            }

            // ตรวจสอบรูปแบบ Line UID (ต้องขึ้นต้นด้วย U และมี 33 ตัวอักษร)
            if (! preg_match('/^U[a-f0-9]{32}$/i', $lineUid)) {
                $failedCount++;
                $errors[] = "Line UID ไม่ถูกต้อง: {$lineUid}";

                continue;
            }

            // ส่งข้อความ
            $result = $this->lineService->send($message, $lineUid);

            if ($result) {
                $successCount++;
            } else {
                $failedCount++;
                $errors[] = "ส่งไปยัง {$lineUid} ไม่สำเร็จ";
            }
        }

        $status = $failedCount === 0 ? 'success' : ($successCount > 0 ? 'warning' : 'error');
        $statusMessage = "ส่งสำเร็จ {$successCount} ราย";
        if ($failedCount > 0) {
            $statusMessage .= ", ไม่สำเร็จ {$failedCount} ราย";
        }

        return back()
            ->with($status, $statusMessage)
            ->with('errors_detail', $errors);
    }

    /**
     * อัพเดท Line UID ของ User
     * POST /admin/line-messaging/update-uid
     */
    public function updateUid(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'line_uid' => 'nullable|string|max:50',
            'line_display_name' => 'nullable|string|max:100',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->update([
            'line_uid' => $validated['line_uid'],
            'line_display_name' => $validated['line_display_name'],
        ]);

        return back()->with('success', "อัพเดท Line UID ของ {$user->name} สำเร็จ");
    }

    /**
     * หน้าจัดการ Line UID ของ Users
     * GET /admin/line-messaging/users
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('line_uid', 'like', "%{$search}%");
            });
        }

        if ($request->has('filter')) {
            if ($request->filter === 'with_line') {
                $query->withLineUid();
            } elseif ($request->filter === 'without_line') {
                $query->where(function ($q) {
                    $q->whereNull('line_uid')->orWhere('line_uid', '');
                });
            }
        }

        $users = $query->orderBy('name')->paginate(20);

        return view('admin.line-messaging.users', compact('users'));
    }
}
