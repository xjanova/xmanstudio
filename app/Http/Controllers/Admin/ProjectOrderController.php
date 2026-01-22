<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectFeature;
use App\Models\ProjectMember;
use App\Models\ProjectOrder;
use App\Models\ProjectTimeline;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectOrderController extends Controller
{
    /**
     * Display a listing of projects
     */
    public function index(Request $request)
    {
        $query = ProjectOrder::with(['user', 'members' => function ($q) {
            $q->where('is_lead', true);
        }]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_number', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('project_type', $request->type);
        }

        $projects = $query->orderByDesc('created_at')->paginate(20);

        // Stats
        $stats = [
            'total' => ProjectOrder::count(),
            'active' => ProjectOrder::active()->count(),
            'completed' => ProjectOrder::completed()->count(),
            'overdue' => ProjectOrder::active()
                ->where('expected_end_date', '<', now())
                ->count(),
        ];

        return view('admin.projects.index', compact('projects', 'stats'));
    }

    /**
     * Show the form for creating a new project
     */
    public function create(Request $request)
    {
        $quotation = null;
        if ($request->filled('quotation_id')) {
            $quotation = Quotation::findOrFail($request->quotation_id);
        }

        $users = User::where('is_active', true)->orderBy('name')->get();
        $staffMembers = User::where('role', 'admin')->orWhere('role', 'staff')->get();

        return view('admin.projects.create', compact('quotation', 'users', 'staffMembers'));
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'quotation_id' => 'nullable|exists:quotations,id',
            'project_name' => 'required|string|max:255',
            'project_description' => 'nullable|string',
            'project_type' => 'required|string',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'total_price' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string',

            // Features
            'features' => 'nullable|array',
            'features.*.name' => 'required|string|max:255',
            'features.*.description' => 'nullable|string',
            'features.*.due_date' => 'nullable|date',

            // Team members
            'members' => 'nullable|array',
            'members.*.name' => 'required|string|max:255',
            'members.*.role' => 'required|string',
            'members.*.email' => 'nullable|email',
            'members.*.phone' => 'nullable|string',
            'members.*.is_lead' => 'nullable|boolean',
        ]);

        // Create project
        $project = ProjectOrder::create([
            'user_id' => $validated['user_id'],
            'quotation_id' => $validated['quotation_id'] ?? null,
            'project_name' => $validated['project_name'],
            'project_description' => $validated['project_description'] ?? null,
            'project_type' => $validated['project_type'],
            'start_date' => $validated['start_date'] ?? null,
            'expected_end_date' => $validated['expected_end_date'] ?? null,
            'total_price' => $validated['total_price'] ?? 0,
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        // Create features
        if (! empty($validated['features'])) {
            foreach ($validated['features'] as $index => $feature) {
                $project->features()->create([
                    'name' => $feature['name'],
                    'description' => $feature['description'] ?? null,
                    'due_date' => $feature['due_date'] ?? null,
                    'order' => $index,
                ]);
            }
        }

        // Create team members
        if (! empty($validated['members'])) {
            foreach ($validated['members'] as $member) {
                $project->members()->create([
                    'name' => $member['name'],
                    'role' => $member['role'],
                    'email' => $member['email'] ?? null,
                    'phone' => $member['phone'] ?? null,
                    'is_lead' => $member['is_lead'] ?? false,
                ]);
            }
        }

        // Create initial timeline event
        $project->timeline()->create([
            'title' => 'สร้างโครงการ',
            'description' => 'โครงการถูกสร้างขึ้นในระบบ',
            'event_date' => now(),
            'type' => 'start',
            'is_completed' => true,
        ]);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', "สร้างโครงการ '{$project->project_name}' เรียบร้อยแล้ว");
    }

    /**
     * Display the specified project
     */
    public function show(ProjectOrder $project)
    {
        $project->load([
            'user',
            'quotation',
            'members',
            'features',
            'progress' => function ($q) {
                $q->with('createdBy', 'feature')->latest()->limit(10);
            },
            'timeline',
        ]);

        return view('admin.projects.show', compact('project'));
    }

    /**
     * Show the form for editing the project
     */
    public function edit(ProjectOrder $project)
    {
        $project->load(['members', 'features']);
        $users = User::where('is_active', true)->orderBy('name')->get();
        $staffMembers = User::where('role', 'admin')->orWhere('role', 'staff')->get();

        return view('admin.projects.edit', compact('project', 'users', 'staffMembers'));
    }

    /**
     * Update the specified project
     */
    public function update(Request $request, ProjectOrder $project)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'project_description' => 'nullable|string',
            'project_type' => 'required|string',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|string',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'total_price' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|string',
            'repository_url' => 'nullable|url',
            'staging_url' => 'nullable|url',
            'production_url' => 'nullable|url',
            'admin_notes' => 'nullable|string',
            'customer_notes' => 'nullable|string',
        ]);

        $project->update($validated);

        // If completed, set actual end date
        if ($validated['status'] === 'completed' && ! $project->actual_end_date) {
            $project->update(['actual_end_date' => now()]);
        }

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'อัพเดทโครงการเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified project
     */
    public function destroy(ProjectOrder $project)
    {
        $name = $project->project_name;
        $project->delete();

        return redirect()
            ->route('admin.projects.index')
            ->with('success', "ลบโครงการ '{$name}' เรียบร้อยแล้ว");
    }

    /**
     * Add a progress update
     */
    public function addProgress(Request $request, ProjectOrder $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string',
            'project_feature_id' => 'nullable|exists:project_features,id',
            'is_public' => 'boolean',
            'notify_customer' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('project-attachments/'.$project->id, 'public');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $progress = $project->progress()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'project_feature_id' => $validated['project_feature_id'] ?? null,
            'created_by' => auth()->id(),
            'is_public' => $request->boolean('is_public', true),
            'notify_customer' => $request->boolean('notify_customer', false),
            'attachments' => $attachments,
        ]);

        // TODO: Send notification to customer if notify_customer is true

        return redirect()
            ->back()
            ->with('success', 'เพิ่มรายงานความคืบหน้าเรียบร้อยแล้ว');
    }

    /**
     * Update feature status
     */
    public function updateFeature(Request $request, ProjectOrder $project, ProjectFeature $feature)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $feature->update($validated);

        // Auto-complete if status is completed
        if ($validated['status'] === 'completed') {
            $feature->update([
                'progress_percent' => 100,
                'completed_at' => now(),
            ]);
        }

        // Update project progress
        $project->updateProgress();

        return redirect()
            ->back()
            ->with('success', "อัพเดทฟีเจอร์ '{$feature->name}' เรียบร้อยแล้ว");
    }

    /**
     * Add a new feature
     */
    public function addFeature(Request $request, ProjectOrder $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $maxOrder = $project->features()->max('order') ?? 0;

        $project->features()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return redirect()
            ->back()
            ->with('success', "เพิ่มฟีเจอร์ '{$validated['name']}' เรียบร้อยแล้ว");
    }

    /**
     * Delete a feature
     */
    public function deleteFeature(ProjectOrder $project, ProjectFeature $feature)
    {
        $name = $feature->name;
        $feature->delete();
        $project->updateProgress();

        return redirect()
            ->back()
            ->with('success', "ลบฟีเจอร์ '{$name}' เรียบร้อยแล้ว");
    }

    /**
     * Add team member
     */
    public function addMember(Request $request, ProjectOrder $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'is_lead' => 'boolean',
        ]);

        // If this is lead, remove lead from others
        if ($request->boolean('is_lead')) {
            $project->members()->update(['is_lead' => false]);
        }

        $project->members()->create($validated);

        return redirect()
            ->back()
            ->with('success', "เพิ่มสมาชิก '{$validated['name']}' เรียบร้อยแล้ว");
    }

    /**
     * Remove team member
     */
    public function deleteMember(ProjectOrder $project, ProjectMember $member)
    {
        $name = $member->name;
        $member->delete();

        return redirect()
            ->back()
            ->with('success', "ลบสมาชิก '{$name}' เรียบร้อยแล้ว");
    }

    /**
     * Add timeline event
     */
    public function addTimeline(Request $request, ProjectOrder $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'type' => 'required|string',
        ]);

        $project->timeline()->create($validated);

        return redirect()
            ->back()
            ->with('success', 'เพิ่มเหตุการณ์ในไทม์ไลน์เรียบร้อยแล้ว');
    }

    /**
     * Toggle timeline completion
     */
    public function toggleTimeline(ProjectOrder $project, ProjectTimeline $timeline)
    {
        $timeline->update(['is_completed' => ! $timeline->is_completed]);

        return redirect()->back();
    }

    /**
     * Create project from quotation
     */
    public function createFromQuotation(Quotation $quotation)
    {
        // Check if project already exists
        if (ProjectOrder::where('quotation_id', $quotation->id)->exists()) {
            return redirect()
                ->route('admin.projects.index')
                ->with('error', 'โครงการจากใบเสนอราคานี้ถูกสร้างไปแล้ว');
        }

        // Create project from quotation
        $project = ProjectOrder::create([
            'user_id' => $quotation->user_id,
            'quotation_id' => $quotation->id,
            'project_name' => $quotation->service_name ?? $quotation->service_type,
            'project_description' => $quotation->project_description,
            'project_type' => $quotation->service_type,
            'total_price' => $quotation->grand_total,
        ]);

        // Create features from service options
        if ($quotation->service_options) {
            foreach ($quotation->service_options as $index => $option) {
                $project->features()->create([
                    'name' => is_array($option) ? ($option['name'] ?? $option) : $option,
                    'order' => $index,
                ]);
            }
        }

        // Create initial timeline
        $project->timeline()->create([
            'title' => 'สร้างโครงการจากใบเสนอราคา',
            'description' => "สร้างจากใบเสนอราคา #{$quotation->quote_number}",
            'event_date' => now(),
            'type' => 'start',
            'is_completed' => true,
        ]);

        return redirect()
            ->route('admin.projects.edit', $project)
            ->with('success', 'สร้างโครงการจากใบเสนอราคาเรียบร้อยแล้ว กรุณากรอกรายละเอียดเพิ่มเติม');
    }
}
