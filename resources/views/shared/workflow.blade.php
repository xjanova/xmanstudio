<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $workflow->name }} - Tping Shared Workflow</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 py-8 sm:py-12">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center px-3 py-1 rounded-full bg-cyan-100 text-cyan-700 text-sm font-medium mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                Shared Workflow
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $workflow->name }}</h1>
            @if($workflow->target_app_name)
                <p class="text-gray-500 mt-2">สำหรับ {{ $workflow->target_app_name }}</p>
            @endif
            <p class="text-sm text-gray-400 mt-1">
                แชร์โดย {{ $workflow->user->name ?? 'ไม่ทราบ' }}
                &middot; {{ count($steps) }} ขั้นตอน
            </p>
        </div>

        <!-- Steps -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-gray-100">
            <h2 class="text-lg font-bold text-gray-900 mb-4">ขั้นตอนทั้งหมด</h2>

            @if(empty($steps))
                <p class="text-gray-400 text-center py-8">ไม่มีข้อมูลขั้นตอน</p>
            @else
                <div class="space-y-3">
                    @foreach($steps as $i => $step)
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold">
                                {{ $i + 1 }}
                            </div>
                            <div class="flex-1">
                                @php
                                    $actionType = $step['actionType'] ?? $step['type'] ?? 'unknown';
                                    $badgeColor = match($actionType) {
                                        'CLICK' => 'bg-blue-100 text-blue-700',
                                        'INPUT_TEXT' => 'bg-green-100 text-green-700',
                                        'SCROLL' => 'bg-amber-100 text-amber-700',
                                        'SWIPE' => 'bg-orange-100 text-orange-700',
                                        'BACK' => 'bg-gray-100 text-gray-700',
                                        'WAIT' => 'bg-purple-100 text-purple-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $badgeColor }}">
                                    {{ $actionType }}
                                </span>
                                @if(!empty($step['description']))
                                    <span class="text-sm text-gray-600 ml-2">{{ $step['description'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- CTA -->
        <div class="text-center">
            <p class="text-gray-500 text-sm mb-4">นำเข้า Workflow นี้ในแอพ Tping ของคุณ</p>
            <a href="https://xmanstudio.com/products/tping" target="_blank"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl font-medium hover:shadow-lg transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                ดาวน์โหลด Tping
            </a>
        </div>

        <!-- Footer -->
        <div class="text-center mt-12 text-xs text-gray-400">
            <p>Powered by <a href="https://xmanstudio.com" class="hover:text-cyan-600 transition-colors">XMAN Studio</a></p>
        </div>
    </div>
</body>
</html>
