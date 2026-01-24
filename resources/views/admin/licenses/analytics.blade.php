@extends($adminLayout ?? 'layouts.admin')

@section('title', 'License Analytics')
@section('page-title', 'License Analytics')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                License Analytics
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">สถิติและการวิเคราะห์ระบบ License</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.licenses.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-medium backdrop-blur-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                รายการ License
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="{{ route('admin.licenses.analytics') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ผลิตภัณฑ์</label>
            <select name="product_id" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white">
                <option value="">ทุกผลิตภัณฑ์</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ $productId == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ช่วงเวลา</label>
            <select name="period" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white">
                <option value="7" {{ $period == '7' ? 'selected' : '' }}>7 วัน</option>
                <option value="30" {{ $period == '30' ? 'selected' : '' }}>30 วัน</option>
                <option value="90" {{ $period == '90' ? 'selected' : '' }}>90 วัน</option>
                <option value="365" {{ $period == '365' ? 'selected' : '' }}>1 ปี</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl hover:from-purple-600 hover:to-violet-700 transition-all font-medium shadow-lg">
            อัปเดต
        </button>
    </form>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-8">
    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-indigo-600/10"></div>
        <div class="relative">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">License ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_licenses']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
        <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-emerald-600/10"></div>
        <div class="relative">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Active</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_licenses']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-violet-600/10"></div>
        <div class="relative">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Activated</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['activated_licenses']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 to-orange-600/10"></div>
        <div class="relative">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">ใกล้หมดอายุ</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['expiring_soon']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 to-green-600/10"></div>
        <div class="relative">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-green-600 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">รายได้โดยประมาณ</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($revenueData['total']) }}</p>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Licenses Created Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            License สร้างใหม่
        </h3>
        <canvas id="licensesChart" height="200"></canvas>
    </div>

    <!-- Activations Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Activations
        </h3>
        <canvas id="activationsChart" height="200"></canvas>
    </div>
</div>

<!-- Distribution Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- License Types -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ประเภท License</h3>
        <canvas id="typeChart" height="200"></canvas>
        <div class="mt-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Lifetime</span>
                <span class="font-medium text-purple-600">{{ number_format($stats['lifetime_licenses']) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Yearly</span>
                <span class="font-medium text-blue-600">{{ number_format($stats['yearly_licenses']) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Monthly</span>
                <span class="font-medium text-cyan-600">{{ number_format($stats['monthly_licenses']) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Demo</span>
                <span class="font-medium text-gray-600">{{ number_format($stats['demo_licenses']) }}</span>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะ License</h3>
        <canvas id="statusChart" height="200"></canvas>
        <div class="mt-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Active</span>
                <span class="font-medium text-green-600">{{ number_format($statusDistribution['active'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Expired</span>
                <span class="font-medium text-amber-600">{{ number_format($statusDistribution['expired'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Revoked</span>
                <span class="font-medium text-red-600">{{ number_format($statusDistribution['revoked'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    <!-- Revenue by Type -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รายได้ตามประเภท</h3>
        <canvas id="revenueChart" height="200"></canvas>
        <div class="mt-4 space-y-2">
            @foreach($revenueData['by_type'] as $type => $data)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($type) }}</span>
                    <span class="font-medium text-emerald-600">฿{{ number_format($data['revenue']) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Top Products & Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Products -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            ผลิตภัณฑ์ยอดนิยม
        </h3>
        <div class="space-y-4">
            @forelse($topProducts as $index => $item)
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $item->product?->name ?? 'Unknown' }}</span>
                            <span class="text-sm text-gray-500">{{ number_format($item->count) }} licenses</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-gradient-to-r from-indigo-400 to-purple-600 h-2 rounded-full" style="width: {{ ($item->count / max($topProducts->max('count'), 1)) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีข้อมูล</p>
            @endforelse
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            กิจกรรมล่าสุด
        </h3>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($recentActivities as $activity)
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $activity->action_color }} flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $activity->action_icon }}"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->action_label }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $activity->license?->license_key }}">
                            {{ Str::limit($activity->license?->license_key, 20) }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                    @if($activity->ip_address)
                        <span class="text-xs text-gray-400">{{ $activity->ip_address }}</span>
                    @endif
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีกิจกรรม</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Activity Summary -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        สรุปกิจกรรม ({{ $period }} วันที่ผ่านมา)
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @php
            $activityColors = [
                'activated' => 'from-green-400 to-emerald-600',
                'deactivated' => 'from-gray-400 to-gray-600',
                'validated' => 'from-cyan-400 to-teal-600',
                'revoked' => 'from-red-400 to-rose-600',
                'extended' => 'from-purple-400 to-violet-600',
                'machine_reset' => 'from-orange-400 to-amber-600',
            ];
            $activityLabels = [
                'activated' => 'Activated',
                'deactivated' => 'Deactivated',
                'validated' => 'Validated',
                'revoked' => 'Revoked',
                'extended' => 'Extended',
                'machine_reset' => 'Machine Reset',
            ];
        @endphp
        @foreach($activityLabels as $action => $label)
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $activityColors[$action] ?? 'from-gray-400 to-gray-600' }} flex items-center justify-center mx-auto mb-2">
                    <span class="text-white font-bold text-sm">{{ $activityByType[$action] ?? 0 }}</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
</style>
<script>
    // Chart.js default config
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.plugins.legend.display = false;

    // Licenses Created Chart
    const licensesCtx = document.getElementById('licensesChart').getContext('2d');
    new Chart(licensesCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($chartData)) !!},
            datasets: [{
                label: 'Licenses',
                data: {!! json_encode(array_values($chartData)) !!},
                borderColor: 'rgb(139, 92, 246)',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } }
            }
        }
    });

    // Activations Chart
    const activationsCtx = document.getElementById('activationsChart').getContext('2d');
    new Chart(activationsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($activationChartData)) !!},
            datasets: [{
                label: 'Activations',
                data: {!! json_encode(array_values($activationChartData)) !!},
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } }
            }
        }
    });

    // Type Distribution Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Lifetime', 'Yearly', 'Monthly', 'Demo'],
            datasets: [{
                data: [
                    {{ $typeDistribution['lifetime'] ?? 0 }},
                    {{ $typeDistribution['yearly'] ?? 0 }},
                    {{ $typeDistribution['monthly'] ?? 0 }},
                    {{ $typeDistribution['demo'] ?? 0 }}
                ],
                backgroundColor: [
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(6, 182, 212, 0.8)',
                    'rgba(156, 163, 175, 0.8)',
                ],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: { legend: { display: false } }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Expired', 'Revoked'],
            datasets: [{
                data: [
                    {{ $statusDistribution['active'] ?? 0 }},
                    {{ $statusDistribution['expired'] ?? 0 }},
                    {{ $statusDistribution['revoked'] ?? 0 }}
                ],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                ],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: { legend: { display: false } }
        }
    });

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Lifetime', 'Yearly', 'Monthly', 'Demo'],
            datasets: [{
                data: [
                    {{ $revenueData['by_type']['lifetime']['revenue'] ?? 0 }},
                    {{ $revenueData['by_type']['yearly']['revenue'] ?? 0 }},
                    {{ $revenueData['by_type']['monthly']['revenue'] ?? 0 }},
                    {{ $revenueData['by_type']['demo']['revenue'] ?? 0 }}
                ],
                backgroundColor: [
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(6, 182, 212, 0.8)',
                    'rgba(156, 163, 175, 0.8)',
                ],
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
@endsection
