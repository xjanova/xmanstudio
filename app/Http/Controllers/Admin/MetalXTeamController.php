<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXTeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetalXTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MetalXTeamMember::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('name_th', 'like', '%'.$request->search.'%')
                  ->orWhere('role', 'like', '%'.$request->search.'%')
                  ->orWhere('role_th', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === 'yes');
        }

        $members = $query->ordered()->paginate(20);

        $stats = [
            'total' => MetalXTeamMember::count(),
            'active' => MetalXTeamMember::where('is_active', true)->count(),
            'inactive' => MetalXTeamMember::where('is_active', false)->count(),
        ];

        return view('admin.metal-x.index', compact('members', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.metal-x.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'role' => 'required|string|max:255',
            'role_th' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'bio_th' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'youtube_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'tiktok_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('metal-x/team', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['order'] = $validated['order'] ?? 0;

        MetalXTeamMember::create($validated);

        return redirect()->route('admin.metal-x.index')
            ->with('success', 'Team member added successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MetalXTeamMember $metalX)
    {
        return view('admin.metal-x.edit', compact('metalX'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MetalXTeamMember $metalX)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'role' => 'required|string|max:255',
            'role_th' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'bio_th' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'youtube_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'tiktok_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($metalX->image) {
                Storage::disk('public')->delete($metalX->image);
            }
            $validated['image'] = $request->file('image')->store('metal-x/team', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        $metalX->update($validated);

        return redirect()->route('admin.metal-x.index')
            ->with('success', 'Team member updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MetalXTeamMember $metalX)
    {
        // Delete image
        if ($metalX->image) {
            Storage::disk('public')->delete($metalX->image);
        }

        $metalX->delete();

        return redirect()->route('admin.metal-x.index')
            ->with('success', 'Team member deleted successfully!');
    }
}
