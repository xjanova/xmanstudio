@extends($adminLayout ?? 'layouts.admin')

@section('title', 'รายงานรายได้')
@section('page-title', 'รายงานรายได้')

@section('content')
<!-- Date Filter -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="{{ route('admin.rentals.reports') }}" method="GET" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">วันที่เริ่ม</label>
            <input type="date" name="start_date" value="{{ $startDate }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">วันที่สิ้นสุด</label>
            <input type="date" name="end_date" value="{{ $endDate }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
        </div>
        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            แสดงรายงาน
        </button>
    </form>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">รายได้รวม</p>
                <p class="text-3xl font-bold text-gray-900">฿{{ number_format($stats['total_revenue']) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">จำนวนธุรกรรม</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_transactions']) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-primary-100 text-primary-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">ค่าเฉลี่ย/ธุรกรรม</p>
                <p class="text-3xl font-bold text-gray-900">฿{{ number_format($stats['average_transaction']) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Daily Chart -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">รายได้รายวัน</h2>
        </div>
        <div class="p-6">
            <canvas id="dailyChart" height="300"></canvas>
        </div>
    </div>

    <!-- By Package -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">รายได้ตามแพ็กเกจ</h2>
        </div>
        <div class="p-6">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="py-3 text-left text-sm font-medium text-gray-500">แพ็กเกจ</th>
                        <th class="py-3 text-right text-sm font-medium text-gray-500">จำนวน</th>
                        <th class="py-3 text-right text-sm font-medium text-gray-500">รายได้</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byPackage as $item)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-sm text-gray-900">{{ $item['name'] }}</td>
                            <td class="py-3 text-right text-sm text-gray-600">{{ $item['count'] }}</td>
                            <td class="py-3 text-right text-sm font-semibold text-gray-900">฿{{ number_format($item['revenue']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-gray-500">ไม่มีข้อมูล</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = @json($chartData);

    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.map(d => d.date),
            datasets: [{
                label: 'รายได้ (บาท)',
                data: chartData.map(d => d.revenue),
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '฿' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
