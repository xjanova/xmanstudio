@extends($publicLayout ?? 'layouts.app')

@section('title', 'ติดตามงาน - XMAN Studio')
@section('meta_description', 'ติดตามความคืบหน้าโครงการของคุณ กรอกหมายเลขโครงการเพื่อดูสถานะล่าสุด')

@section('content')
<div class="min-h-screen relative overflow-hidden">
    {{-- Animated Background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-cyan-500/15 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        </div>
        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22><rect width=%2240%22 height=%2240%22 fill=%22none%22 stroke=%22white%22 stroke-width=%220.5%22/></svg>');"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">

        {{-- Hero Search Section --}}
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 mb-6 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-2xl shadow-indigo-500/30 ring-1 ring-white/10">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-3 tracking-tight">
                ติดตามงาน
            </h1>
            <p class="text-lg text-indigo-200/70 max-w-md mx-auto">
                กรอกหมายเลขโครงการเพื่อดูความคืบหน้าแบบเรียลไทม์
            </p>
        </div>

        {{-- Search Box --}}
        <div class="max-w-2xl mx-auto mb-12">
            <form method="GET" action="{{ route('tracking.search') }}" class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-500 rounded-2xl opacity-40 group-hover:opacity-60 blur transition duration-500"></div>
                <div class="relative flex bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 overflow-hidden">
                    <div class="flex-1 relative">
                        <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-indigo-300/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="query" value="{{ $query ?? '' }}" required minlength="3"
                               placeholder="PRJ-20260213-ABCD"
                               class="w-full pl-14 pr-4 py-5 bg-transparent text-white placeholder-indigo-300/40 text-lg font-mono focus:outline-none focus:ring-0 border-0">
                    </div>
                    <button type="submit" class="px-8 py-5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold text-base hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 flex items-center gap-2 whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span class="hidden sm:inline">ค้นหา</span>
                    </button>
                </div>
            </form>
            <p class="text-center text-sm text-indigo-300/40 mt-3">ตัวอย่าง: PRJ-20260213-ABCD</p>
        </div>

        {{-- Results --}}
        @isset($query)
            @if(isset($project) && $project)
                {{-- Project Found --}}
                <div class="space-y-6 animate-fade-in-up">

                    {{-- Project Header Card --}}
                    <div class="relative overflow-hidden rounded-3xl bg-white/5 backdrop-blur-xl border border-white/10 shadow-2xl">
                        {{-- Top accent --}}
                        <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-500"></div>

                        <div class="p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                                <div>
                                    <p class="font-mono text-sm text-indigo-400 font-semibold mb-1 tracking-wider">{{ $project->project_number }}</p>
                                    <h2 class="text-2xl sm:text-3xl font-bold text-white">{{ $project->project_name }}</h2>
                                    @if($project->project_type)
                                    <span class="inline-flex items-center mt-2 px-3 py-1 text-xs font-semibold rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/20">
                                        {{ \App\Models\ProjectOrder::TYPE_LABELS[$project->project_type] ?? $project->project_type }}
                                    </span>
                                    @endif
                                </div>
                                <div>
                                    @php
                                        $statusGradients = [
                                            'pending' => 'from-gray-400 to-gray-500',
                                            'in_progress' => 'from-blue-400 to-indigo-500',
                                            'on_hold' => 'from-yellow-400 to-amber-500',
                                            'review' => 'from-purple-400 to-violet-500',
                                            'revision' => 'from-orange-400 to-amber-500',
                                            'completed' => 'from-emerald-400 to-green-500',
                                            'cancelled' => 'from-red-400 to-rose-500',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold rounded-xl bg-gradient-to-r {{ $statusGradients[$project->status] ?? 'from-gray-400 to-gray-500' }} text-white shadow-lg">
                                        @if($project->status === 'completed')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        @elseif($project->status === 'in_progress')
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        @endif
                                        {{ \App\Models\ProjectOrder::STATUS_LABELS[$project->status] ?? $project->status }}
                                    </span>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-indigo-200/60">ความคืบหน้า</span>
                                    <span class="text-2xl font-black text-white">{{ $project->progress_percent ?? 0 }}<span class="text-base text-indigo-300/60">%</span></span>
                                </div>
                                <div class="w-full h-4 bg-white/5 rounded-full overflow-hidden border border-white/5">
                                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-400 transition-all duration-1000 ease-out relative"
                                         style="width: {{ $project->progress_percent ?? 0 }}%">
                                        <div class="absolute inset-0 bg-[length:20px_20px] animate-[shimmer_2s_linear_infinite] opacity-30"
                                             style="background-image: linear-gradient(45deg, transparent 25%, rgba(255,255,255,0.3) 25%, rgba(255,255,255,0.3) 50%, transparent 50%, transparent 75%, rgba(255,255,255,0.3) 75%);"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Quick Stats --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                                    <p class="text-xs text-indigo-300/50 font-medium mb-1">เริ่มงาน</p>
                                    <p class="text-sm font-bold text-white">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                                    <p class="text-xs text-indigo-300/50 font-medium mb-1">กำหนดส่ง</p>
                                    <p class="text-sm font-bold text-white">{{ $project->expected_end_date?->format('d/m/Y') ?? '-' }}</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                                    <p class="text-xs text-indigo-300/50 font-medium mb-1">มูลค่าโครงการ</p>
                                    <p class="text-sm font-bold text-emerald-400">฿{{ number_format($project->total_price, 0) }}</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                                    <p class="text-xs text-indigo-300/50 font-medium mb-1">ชำระแล้ว</p>
                                    <p class="text-sm font-bold text-cyan-400">฿{{ number_format($project->paid_amount, 0) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Features / Milestones Timeline --}}
                    @if($project->features->isNotEmpty())
                    <div class="rounded-3xl bg-white/5 backdrop-blur-xl border border-white/10 shadow-2xl overflow-hidden">
                        <div class="p-6 sm:p-8">
                            <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                รายการงาน
                                <span class="text-sm font-normal text-indigo-300/50">({{ $project->features->where('status', 'completed')->count() }}/{{ $project->features->count() }})</span>
                            </h3>

                            <div class="relative">
                                {{-- Vertical Line --}}
                                <div class="absolute left-[15px] top-2 bottom-2 w-0.5 bg-gradient-to-b from-indigo-500/50 via-purple-500/30 to-transparent"></div>

                                <div class="space-y-4">
                                    @foreach($project->features as $feature)
                                    @php
                                        $featureStatusConfig = [
                                            'completed' => ['icon' => 'check', 'color' => 'emerald', 'bg' => 'bg-emerald-500', 'ring' => 'ring-emerald-500/30'],
                                            'in_progress' => ['icon' => 'spin', 'color' => 'blue', 'bg' => 'bg-blue-500', 'ring' => 'ring-blue-500/30'],
                                            'pending' => ['icon' => 'dot', 'color' => 'gray', 'bg' => 'bg-white/20', 'ring' => 'ring-white/10'],
                                            'cancelled' => ['icon' => 'x', 'color' => 'red', 'bg' => 'bg-red-500/50', 'ring' => 'ring-red-500/20'],
                                        ];
                                        $fc = $featureStatusConfig[$feature->status] ?? $featureStatusConfig['pending'];
                                    @endphp
                                    <div class="relative flex items-start gap-4 pl-10">
                                        {{-- Timeline Dot --}}
                                        <div class="absolute left-0 top-1 w-[30px] h-[30px] rounded-full {{ $fc['bg'] }} ring-4 {{ $fc['ring'] }} flex items-center justify-center flex-shrink-0">
                                            @if($fc['icon'] === 'check')
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @elseif($fc['icon'] === 'spin')
                                                <svg class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            @elseif($fc['icon'] === 'x')
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                            @else
                                                <div class="w-2 h-2 rounded-full bg-white/40"></div>
                                            @endif
                                        </div>

                                        {{-- Content --}}
                                        <div class="flex-1 bg-white/5 rounded-xl p-4 border border-white/5 hover:bg-white/[0.07] transition-colors">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold text-white {{ $feature->status === 'completed' ? 'line-through opacity-60' : '' }}">
                                                    {{ $feature->name }}
                                                </span>
                                                @if($feature->progress_percent > 0 && $feature->status !== 'completed')
                                                <span class="text-xs font-bold text-indigo-300">{{ $feature->progress_percent }}%</span>
                                                @endif
                                            </div>
                                            @if($feature->description)
                                            <p class="text-sm text-indigo-200/40 mt-1">{{ Str::limit($feature->description, 100) }}</p>
                                            @endif
                                            @if($feature->due_date)
                                            <p class="text-xs text-indigo-300/30 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                กำหนด: {{ $feature->due_date->format('d/m/Y') }}
                                            </p>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Recent Public Progress Updates --}}
                    @if($project->progress->isNotEmpty())
                    <div class="rounded-3xl bg-white/5 backdrop-blur-xl border border-white/10 shadow-2xl overflow-hidden">
                        <div class="p-6 sm:p-8">
                            <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                อัปเดตล่าสุด
                            </h3>
                            <div class="space-y-4">
                                @foreach($project->progress as $update)
                                <div class="flex items-start gap-3">
                                    <div class="mt-1.5 w-2.5 h-2.5 rounded-full bg-gradient-to-br from-cyan-400 to-indigo-500 flex-shrink-0 ring-4 ring-cyan-500/10"></div>
                                    <div class="flex-1">
                                        @if($update->title)
                                        <p class="font-semibold text-white text-sm">{{ $update->title }}</p>
                                        @endif
                                        <p class="text-sm text-indigo-200/50">{{ $update->description }}</p>
                                        <p class="text-xs text-indigo-300/30 mt-1">{{ $update->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Payment Summary --}}
                    @if($project->total_price > 0)
                    <div class="rounded-3xl bg-white/5 backdrop-blur-xl border border-white/10 shadow-2xl overflow-hidden">
                        <div class="p-6 sm:p-8">
                            <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                สรุปค่าใช้จ่าย
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-3 border-b border-white/5">
                                    <span class="text-indigo-200/60">มูลค่าโครงการ</span>
                                    <span class="text-xl font-bold text-white">฿{{ number_format($project->total_price, 0) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-white/5">
                                    <span class="text-indigo-200/60">ชำระแล้ว</span>
                                    <span class="text-xl font-bold text-emerald-400">฿{{ number_format($project->paid_amount, 0) }}</span>
                                </div>
                                @if($project->remaining_amount > 0)
                                <div class="flex justify-between items-center py-3">
                                    <span class="text-indigo-200/60">ยอดค้างชำระ</span>
                                    <span class="text-xl font-bold text-amber-400">฿{{ number_format($project->remaining_amount, 0) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

            @else
                {{-- Not Found --}}
                <div class="text-center py-16 animate-fade-in-up">
                    <div class="inline-flex items-center justify-center w-20 h-20 mb-6 rounded-2xl bg-white/5 border border-white/10">
                        <svg class="w-10 h-10 text-indigo-300/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-xl font-bold text-white mb-2">ไม่พบโครงการ</p>
                    <p class="text-indigo-200/40">กรุณาตรวจสอบหมายเลขโครงการอีกครั้ง</p>
                    <p class="text-sm text-indigo-300/30 mt-1">ตัวอย่าง: PRJ-20260213-ABCD</p>
                </div>
            @endif
        @endisset

        {{-- Info Cards (show when no search yet) --}}
        @empty($query)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mx-auto animate-fade-in-up">
            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 text-center hover:bg-white/[0.07] transition-colors group">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-indigo-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white text-sm">กรอกหมายเลข</h3>
                <p class="text-xs text-indigo-200/40 mt-1">เช่น PRJ-20260213-ABCD</p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 text-center hover:bg-white/[0.07] transition-colors group">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-purple-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white text-sm">ดูความคืบหน้า</h3>
                <p class="text-xs text-indigo-200/40 mt-1">ไทม์ไลน์ + สถานะงาน</p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 text-center hover:bg-white/[0.07] transition-colors group">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-cyan-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white text-sm">ตรวจสอบยอดชำระ</h3>
                <p class="text-xs text-indigo-200/40 mt-1">สรุปค่าใช้จ่าย</p>
            </div>
        </div>
        @endempty

    </div>
</div>

@push('styles')
<style>
    @keyframes shimmer {
        0% { background-position: -20px 0; }
        100% { background-position: 40px 0; }
    }
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.6s ease-out both;
    }
</style>
@endpush
@endsection
