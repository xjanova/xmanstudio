@extends($adminLayout ?? 'layouts.admin')

@section('title', 'รายละเอียดโครงการ')
@section('page-title', $project->project_name)

@section('content')
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        {{ session('success') }}
    </div>
@endif

<!-- Header -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h2 class="text-2xl font-bold text-gray-900">{{ $project->project_name }}</h2>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                    @switch($project->status_color)
                        @case('green') bg-green-100 text-green-800 @break
                        @case('blue') bg-blue-100 text-blue-800 @break
                        @case('yellow') bg-yellow-100 text-yellow-800 @break
                        @case('purple') bg-purple-100 text-purple-800 @break
                        @case('orange') bg-orange-100 text-orange-800 @break
                        @case('red') bg-red-100 text-red-800 @break
                        @default bg-gray-100 text-gray-800
                    @endswitch">
                    {{ $project->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-4 text-sm text-gray-500">
                <span class="font-mono">{{ $project->project_number }}</span>
                <span>{{ $project->type_label }}</span>
                @if($project->user)
                    <span>ลูกค้า: {{ $project->user->name }}</span>
                @endif
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.projects.edit', $project) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                แก้ไข
            </a>
            <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="inline"
                  onsubmit="return confirm('ยืนยันการลบโครงการนี้?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    ลบ
                </button>
            </form>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mt-6">
        <div class="flex justify-between text-sm text-gray-600 mb-2">
            <span>ความคืบหน้า</span>
            <span class="font-semibold">{{ $project->progress_percent }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="h-3 rounded-full transition-all duration-500
                @if($project->progress_percent >= 100) bg-green-500
                @elseif($project->progress_percent >= 50) bg-blue-500
                @else bg-yellow-500 @endif"
                style="width: {{ $project->progress_percent }}%"></div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t">
        <div>
            <p class="text-sm text-gray-500">ฟีเจอร์</p>
            <p class="text-lg font-semibold">{{ $project->completed_features_count }}/{{ $project->total_features_count }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">วันเริ่ม</p>
            <p class="text-lg font-semibold">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">กำหนดส่ง</p>
            <p class="text-lg font-semibold {{ $project->isOverdue() ? 'text-red-600' : '' }}">
                {{ $project->expected_end_date?->format('d/m/Y') ?? '-' }}
                @if($project->isOverdue())
                    <span class="text-sm">(เกินกำหนด)</span>
                @endif
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-500">ยอดคงเหลือ</p>
            <p class="text-lg font-semibold {{ $project->remaining_amount > 0 ? 'text-orange-600' : 'text-green-600' }}">
                {{ number_format($project->remaining_amount, 0) }} บาท
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Features -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">ฟีเจอร์/ไมล์สโตน</h3>
                <button type="button" onclick="document.getElementById('addFeatureModal').classList.remove('hidden')"
                        class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                    + เพิ่มฟีเจอร์
                </button>
            </div>

            @if($project->features->count() > 0)
                <div class="space-y-3">
                    @foreach($project->features as $feature)
                        <div class="border rounded-lg p-4 {{ $feature->isOverdue() ? 'border-red-300 bg-red-50' : '' }}">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $feature->status_icon }}</span>
                                        <h4 class="font-medium {{ $feature->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                                            {{ $feature->name }}
                                        </h4>
                                        <span class="inline-flex px-2 py-0.5 text-xs rounded-full
                                            @switch($feature->status_color)
                                                @case('green') bg-green-100 text-green-800 @break
                                                @case('blue') bg-blue-100 text-blue-800 @break
                                                @case('red') bg-red-100 text-red-800 @break
                                                @default bg-gray-100 text-gray-800
                                            @endswitch">
                                            {{ $feature->status_label }}
                                        </span>
                                    </div>
                                    @if($feature->description)
                                        <p class="text-sm text-gray-500 mt-1">{{ $feature->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        @if($feature->due_date)
                                            <span class="{{ $feature->isOverdue() ? 'text-red-600 font-semibold' : '' }}">
                                                กำหนด: {{ $feature->due_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        @if($feature->progress_percent > 0)
                                            <span>{{ $feature->progress_percent }}%</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <!-- Update Status -->
                                    <form action="{{ route('admin.projects.features.update', [$project, $feature]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        @if($feature->status !== 'completed')
                                            <input type="hidden" name="status" value="completed">
                                            <input type="hidden" name="progress_percent" value="100">
                                            <button type="submit" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="ทำเครื่องหมายว่าเสร็จ">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </form>
                                    <!-- Delete -->
                                    <form action="{{ route('admin.projects.features.destroy', [$project, $feature]) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ยืนยันการลบฟีเจอร์นี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-lg" title="ลบ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>ยังไม่มีฟีเจอร์</p>
                </div>
            @endif
        </div>

        <!-- Progress Updates -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">รายงานความคืบหน้า</h3>
                <button type="button" onclick="document.getElementById('addProgressModal').classList.remove('hidden')"
                        class="text-sm px-3 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200">
                    + เพิ่มรายงาน
                </button>
            </div>

            @if($project->progress->count() > 0)
                <div class="space-y-4">
                    @foreach($project->progress as $progress)
                        <div class="border-l-4 pl-4 py-2
                            @switch($progress->type)
                                @case('milestone') border-blue-500 @break
                                @case('issue') border-red-500 @break
                                @case('delivery') border-purple-500 @break
                                @case('meeting') border-yellow-500 @break
                                @default border-gray-300
                            @endswitch">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $progress->title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ $progress->description }}</p>
                                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                        <span>{{ $progress->type_label }}</span>
                                        @if($progress->feature)
                                            <span>{{ $progress->feature->name }}</span>
                                        @endif
                                        <span>{{ $progress->created_at->format('d/m/Y H:i') }}</span>
                                        @if($progress->createdBy)
                                            <span>โดย {{ $progress->createdBy->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    @if($progress->is_public)
                                        <span class="text-green-600" title="ลูกค้าเห็น">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>ยังไม่มีรายงานความคืบหน้า</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Team Members -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">ทีมงาน</h3>
                <button type="button" onclick="document.getElementById('addMemberModal').classList.remove('hidden')"
                        class="text-sm px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                    +
                </button>
            </div>

            @if($project->members->count() > 0)
                <div class="space-y-3">
                    @foreach($project->members as $member)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        {{ $member->name }}
                                        @if($member->is_lead)
                                            <span class="text-yellow-500" title="หัวหน้าโครงการ">*</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $member->role_label }}</div>
                                </div>
                            </div>
                            <form action="{{ route('admin.projects.members.destroy', [$project, $member]) }}" method="POST"
                                  onsubmit="return confirm('ยืนยันการลบสมาชิกนี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">ยังไม่มีสมาชิกในทีม</p>
            @endif
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">ไทม์ไลน์</h3>
                <button type="button" onclick="document.getElementById('addTimelineModal').classList.remove('hidden')"
                        class="text-sm px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                    +
                </button>
            </div>

            @if($project->timeline->count() > 0)
                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    <div class="space-y-4">
                        @foreach($project->timeline as $event)
                            <div class="relative pl-10">
                                <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center text-lg
                                    {{ $event->is_completed ? 'bg-green-100' : ($event->isOverdue() ? 'bg-red-100' : 'bg-gray-100') }}">
                                    {{ $event->type_icon }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium {{ $event->is_completed ? 'text-green-600' : ($event->isOverdue() ? 'text-red-600' : '') }}">
                                            {{ $event->title }}
                                        </span>
                                        <form action="{{ route('admin.projects.timeline.toggle', [$project, $event]) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-gray-400 hover:text-green-600">
                                                @if($event->is_completed)
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $event->event_date->format('d/m/Y') }}</div>
                                    @if($event->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $event->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-gray-500 text-sm">ยังไม่มีเหตุการณ์</p>
            @endif
        </div>

        <!-- Links -->
        @if($project->repository_url || $project->staging_url || $project->production_url)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ลิงก์</h3>
                <div class="space-y-2">
                    @if($project->repository_url)
                        <a href="{{ $project->repository_url }}" target="_blank" class="flex items-center gap-2 text-primary-600 hover:underline">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.341-3.369-1.341-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                            </svg>
                            Repository
                        </a>
                    @endif
                    @if($project->staging_url)
                        <a href="{{ $project->staging_url }}" target="_blank" class="flex items-center gap-2 text-primary-600 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Staging
                        </a>
                    @endif
                    @if($project->production_url)
                        <a href="{{ $project->production_url }}" target="_blank" class="flex items-center gap-2 text-primary-600 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            Production
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Add Feature Modal -->
<div id="addFeatureModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold mb-4">เพิ่มฟีเจอร์</h3>
        <form action="{{ route('admin.projects.features.store', $project) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อฟีเจอร์</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">กำหนดส่ง</label>
                    <input type="date" name="due_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('addFeatureModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:text-gray-900">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">เพิ่ม</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Progress Modal -->
<div id="addProgressModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <h3 class="text-lg font-semibold mb-4">เพิ่มรายงานความคืบหน้า</h3>
        <form action="{{ route('admin.projects.progress.store', $project) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อ</label>
                    <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                    <textarea name="description" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                        <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach(\App\Models\ProjectProgress::TYPE_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ฟีเจอร์ที่เกี่ยวข้อง</label>
                        <select name="project_feature_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">- ไม่ระบุ -</option>
                            @foreach($project->features as $feature)
                                <option value="{{ $feature->id }}">{{ $feature->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_public" value="1" checked class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">ลูกค้าเห็น</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_customer" value="1" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">แจ้งเตือนลูกค้า</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('addProgressModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:text-gray-900">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">เพิ่ม</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Member Modal -->
<div id="addMemberModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold mb-4">เพิ่มสมาชิก</h3>
        <form action="{{ route('admin.projects.members.store', $project) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง</label>
                    <select name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="project_manager">Project Manager</option>
                        <option value="developer">Developer</option>
                        <option value="designer">Designer</option>
                        <option value="tester">Tester</option>
                        <option value="analyst">Analyst</option>
                        <option value="support">Support</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_lead" value="1" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">หัวหน้าโครงการ</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('addMemberModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:text-gray-900">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">เพิ่ม</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Timeline Modal -->
<div id="addTimelineModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold mb-4">เพิ่มเหตุการณ์</h3>
        <form action="{{ route('admin.projects.timeline.store', $project) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อ</label>
                    <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">วันที่</label>
                        <input type="date" name="event_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                        <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach(\App\Models\ProjectTimeline::TYPE_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('addTimelineModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:text-gray-900">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">เพิ่ม</button>
            </div>
        </form>
    </div>
</div>
@endsection
