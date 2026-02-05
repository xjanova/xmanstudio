<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRental;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::query()->latest();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('line_display_name', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by LINE connection
        if ($request->filled('line_connected')) {
            if ($request->line_connected === 'yes') {
                $query->whereNotNull('line_uid')->where('line_uid', '!=', '');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('line_uid')->orWhere('line_uid', '');
                });
            }
        }

        // Stats
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'admin_users' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'line_connected' => User::whereNotNull('line_uid')->where('line_uid', '!=', '')->count(),
        ];

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['user', 'admin', 'super_admin'])],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        // Sync roles
        if (! empty($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "สร้างสมาชิก {$user->name} สำเร็จ");
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        // Load relationships
        $user->load(['orders' => fn ($q) => $q->latest()->take(5), 'roles.permissions']);

        // Get user's wallet
        $wallet = Wallet::where('user_id', $user->id)->first();

        // Get user's active rental
        $activeRental = $user->activeRental();

        // Get recent rentals
        $rentals = UserRental::where('user_id', $user->id)
            ->with('package')
            ->latest()
            ->take(5)
            ->get();

        // Get user's licenses through orders
        $licenses = LicenseKey::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['product', 'order'])
            ->latest()
            ->take(10)
            ->get();

        // Get orders stats
        $orderStats = [
            'total' => Order::where('user_id', $user->id)->count(),
            'paid' => Order::where('user_id', $user->id)->where('payment_status', 'paid')->count(),
            'total_spent' => Order::where('user_id', $user->id)->where('payment_status', 'paid')->sum('total'),
        ];

        return view('admin.users.show', compact('user', 'wallet', 'activeRental', 'rentals', 'licenses', 'orderStats'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles');

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['user', 'admin', 'super_admin'])],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
            'marketing_email_enabled' => ['boolean'],
            'marketing_line_enabled' => ['boolean'],
        ]);

        // Prevent super_admin from being demoted by non-super_admin
        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถแก้ไข Super Admin ได้');
        }

        // Prevent removing last super_admin
        if ($user->isSuperAdmin() && $validated['role'] !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()
                    ->back()
                    ->with('error', 'ไม่สามารถลดระดับ Super Admin คนสุดท้ายได้');
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->role = $validated['role'];
        $user->is_active = $request->boolean('is_active', true);
        $user->marketing_email_enabled = $request->boolean('marketing_email_enabled');
        $user->marketing_line_enabled = $request->boolean('marketing_line_enabled');

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Sync roles
        $user->roles()->sync($validated['role_ids'] ?? []);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'อัพเดทข้อมูลสมาชิกสำเร็จ');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
        }

        // Prevent deleting super_admin by non-super_admin
        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบ Super Admin ได้');
        }

        // Prevent deleting last super_admin
        if ($user->isSuperAdmin()) {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()
                    ->back()
                    ->with('error', 'ไม่สามารถลบ Super Admin คนสุดท้ายได้');
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "ลบสมาชิก {$userName} สำเร็จ");
    }

    /**
     * Toggle user active status
     */
    public function toggle(User $user)
    {
        // Prevent toggling self
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถปิดการใช้งานบัญชีของตัวเองได้');
        }

        // Prevent toggling super_admin by non-super_admin
        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถปิดการใช้งาน Super Admin ได้');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $status = $user->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "{$status}บัญชี {$user->name} สำเร็จ");
    }

    /**
     * Disconnect LINE account
     */
    public function disconnectLine(User $user)
    {
        $user->line_uid = null;
        $user->line_display_name = null;
        $user->line_access_token = null;
        $user->line_refresh_token = null;
        $user->line_picture_url = null;
        $user->save();

        return redirect()
            ->back()
            ->with('success', 'ยกเลิกการเชื่อมต่อ LINE สำเร็จ');
    }

    /**
     * Export users to CSV
     */
    public function export(Request $request)
    {
        $query = User::query()->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->get();

        $filename = 'users-'.date('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'ID',
                'ชื่อ',
                'อีเมล',
                'เบอร์โทร',
                'บทบาท',
                'สถานะ',
                'LINE UID',
                'LINE Name',
                'สมัครเมื่อ',
            ]);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->role,
                    $user->is_active ? 'ใช้งาน' : 'ปิดใช้งาน',
                    $user->line_uid,
                    $user->line_display_name,
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk action on users
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $userIds = collect($request->user_ids)
            ->filter(fn ($id) => $id != auth()->id()); // Exclude self

        $users = User::whereIn('id', $userIds)->get();

        // Filter out super_admins if current user is not super_admin
        if (! auth()->user()->isSuperAdmin()) {
            $users = $users->filter(fn ($user) => ! $user->isSuperAdmin());
        }

        $count = 0;

        switch ($request->action) {
            case 'activate':
                foreach ($users as $user) {
                    $user->update(['is_active' => true]);
                    $count++;
                }
                $message = "เปิดใช้งาน {$count} บัญชีสำเร็จ";
                break;

            case 'deactivate':
                foreach ($users as $user) {
                    $user->update(['is_active' => false]);
                    $count++;
                }
                $message = "ปิดใช้งาน {$count} บัญชีสำเร็จ";
                break;

            case 'delete':
                // Prevent deleting last super_admin
                $superAdminCount = User::where('role', 'super_admin')->count();
                $superAdminToDelete = $users->filter(fn ($u) => $u->isSuperAdmin())->count();

                if ($superAdminCount - $superAdminToDelete < 1) {
                    return redirect()
                        ->back()
                        ->with('error', 'ไม่สามารถลบ Super Admin คนสุดท้ายได้');
                }

                foreach ($users as $user) {
                    $user->delete();
                    $count++;
                }
                $message = "ลบ {$count} บัญชีสำเร็จ";
                break;
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(Request $request, User $user)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
        ]);

        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return redirect()
            ->back()
            ->with('success', 'อัพเดทรูปโปรไฟล์สำเร็จ');
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(User $user)
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return redirect()
            ->back()
            ->with('success', 'ลบรูปโปรไฟล์สำเร็จ');
    }
}
