<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $query = TeamMember::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('name_th', 'like', '%' . $request->search . '%')
                    ->orWhere('position', 'like', '%' . $request->search . '%')
                    ->orWhere('position_th', 'like', '%' . $request->search . '%')
                    ->orWhere('department', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === 'yes');
        }

        if ($request->filled('type')) {
            if ($request->type === 'leader') {
                $query->where('is_leader', true);
            } elseif ($request->type === 'member') {
                $query->where('is_leader', false);
            }
        }

        $members = $query->ordered()->paginate(20);

        $stats = [
            'total' => TeamMember::count(),
            'leaders' => TeamMember::where('is_leader', true)->count(),
            'active' => TeamMember::where('is_active', true)->count(),
        ];

        return view('admin.team.index', compact('members', 'stats'));
    }

    public function create()
    {
        return view('admin.team.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
            'position_th' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'bio_th' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'department' => 'nullable|string|max:255',
            'skills' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'is_leader' => 'boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('team', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_leader'] = $request->boolean('is_leader');
        $validated['order'] = $validated['order'] ?? 0;

        TeamMember::create($validated);

        return redirect()->route('admin.team.index')
            ->with('success', 'เพิ่มสมาชิกทีมเรียบร้อยแล้ว!');
    }

    public function edit(TeamMember $teamMember)
    {
        return view('admin.team.edit', compact('teamMember'));
    }

    public function update(Request $request, TeamMember $teamMember)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
            'position_th' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'bio_th' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'department' => 'nullable|string|max:255',
            'skills' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'is_leader' => 'boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($teamMember->image) {
                Storage::disk('public')->delete($teamMember->image);
            }
            $validated['image'] = $request->file('image')->store('team', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_leader'] = $request->boolean('is_leader');

        $teamMember->update($validated);

        return redirect()->route('admin.team.index')
            ->with('success', 'อัปเดตข้อมูลสมาชิกเรียบร้อยแล้ว!');
    }

    public function destroy(TeamMember $teamMember)
    {
        if ($teamMember->image) {
            Storage::disk('public')->delete($teamMember->image);
        }

        $teamMember->delete();

        return redirect()->route('admin.team.index')
            ->with('success', 'ลบสมาชิกทีมเรียบร้อยแล้ว!');
    }
}
