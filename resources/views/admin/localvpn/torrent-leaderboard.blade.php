@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent - Leaderboard')
@section('page-title', 'LocalVPN - BitTorrent Leaderboard')

@section('content')
@include('admin.localvpn._tabs')

{{-- Header --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-700 via-purple-600 to-indigo-500 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="relative flex items-center gap-4">
        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">Leaderboard</h1>
            <p class="text-violet-100">Top 50 Contributors</p>
        </div>
    </div>
</div>

{{-- Leaderboard Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium w-16">อันดับ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อ</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">คะแนน</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">ไฟล์ที่แชร์</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">อัปโหลด</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">ดาวน์โหลด</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">เวลา Seed</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">ถ้วยรางวัล</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($leaderboard ?? [] as $index => $user)
                <tr class="hover:bg-gray-50 {{ $index < 3 ? 'bg-violet-50/30' : '' }}">
                    <td class="py-3 px-4 text-center">
                        @if($index === 0)
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-yellow-700 font-bold text-sm shadow-sm">1</span>
                        @elseif($index === 1)
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-700 font-bold text-sm shadow-sm">2</span>
                        @elseif($index === 2)
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold text-sm shadow-sm">3</span>
                        @else
                            <span class="text-gray-500 font-medium">{{ $index + 1 }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $user->display_name ?? 'N/A' }}</td>
                    <td class="py-3 px-4 text-right font-bold text-violet-600">{{ number_format($user->score ?? 0) }}</td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ number_format($user->total_files_shared ?? 0) }}</td>
                    <td class="py-3 px-4 text-right text-gray-600 font-mono text-xs">
                        @php $up = $user->total_uploaded_bytes ?? 0; @endphp
                        @if($up >= 1073741824)
                            {{ number_format($up / 1073741824, 2) }} GB
                        @elseif($up >= 1048576)
                            {{ number_format($up / 1048576, 2) }} MB
                        @elseif($up >= 1024)
                            {{ number_format($up / 1024, 2) }} KB
                        @else
                            {{ number_format($up) }} B
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right text-gray-600 font-mono text-xs">
                        @php $dl = $user->total_downloaded_bytes ?? 0; @endphp
                        @if($dl >= 1073741824)
                            {{ number_format($dl / 1073741824, 2) }} GB
                        @elseif($dl >= 1048576)
                            {{ number_format($dl / 1048576, 2) }} MB
                        @elseif($dl >= 1024)
                            {{ number_format($dl / 1024, 2) }} KB
                        @else
                            {{ number_format($dl) }} B
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right text-gray-600 text-xs">
                        @php
                            $seconds = $user->seed_time_seconds ?? 0;
                            $days = floor($seconds / 86400);
                            $hours = floor(($seconds % 86400) / 3600);
                            $mins = floor(($seconds % 3600) / 60);
                        @endphp
                        @if($days > 0)
                            {{ $days }}d {{ $hours }}h
                        @elseif($hours > 0)
                            {{ $hours }}h {{ $mins }}m
                        @else
                            {{ $mins }}m
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if(($trophyCounts[$user->machine_id] ?? 0) > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800">
                                {{ $trophyCounts[$user->machine_id] ?? 0 }}
                            </span>
                        @else
                            <span class="text-gray-400">0</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-gray-500">ยังไม่มีข้อมูล Leaderboard</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
