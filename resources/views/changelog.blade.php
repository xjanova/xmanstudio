@extends('layouts.app')

@section('title', 'Changelog - XMAN Studio')
@section('meta_description', 'ประวัติการอัปเดตและการเปลี่ยนแปลงทั้งหมดของ XMAN Studio')

@section('content')
<div class="min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">Changelog</h1>
            <p class="text-lg text-gray-500 dark:text-gray-400">ประวัติการอัปเดตและการเปลี่ยนแปลงทั้งหมด</p>
        </div>

        @if(empty($versions))
            <div class="text-center py-16">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีข้อมูล changelog</p>
            </div>
        @else
            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-500 via-purple-500 to-gray-300 dark:to-gray-700 hidden md:block"></div>

                @foreach($versions as $index => $version)
                <div class="relative mb-10 md:pl-20" x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                    <!-- Timeline Dot -->
                    <div class="absolute left-6 w-5 h-5 rounded-full border-4 border-white dark:border-gray-900 shadow hidden md:block {{ $index === 0 ? 'bg-blue-500' : 'bg-gray-400 dark:bg-gray-500' }}"></div>

                    <!-- Version Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition hover:shadow-md">
                        <!-- Version Header -->
                        <button @click="open = !open" class="w-full flex items-center justify-between p-6 text-left focus:outline-none">
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $index === 0 ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                    v{{ $version['version'] }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $version['date'] }}</span>
                                @if($index === 0)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">ล่าสุด</span>
                                @endif
                            </div>
                            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Version Content -->
                        <div x-show="open" x-collapse class="px-6 pb-6">
                            <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-5">
                                @foreach($version['sections'] as $sectionName => $items)
                                    @if(!empty($items) && $sectionName !== 'Files Modified')
                                        @php
                                            $sectionConfig = match($sectionName) {
                                                'Added' => ['color' => 'green', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                                                'Changed' => ['color' => 'blue', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                                                'Fixed' => ['color' => 'yellow', 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
                                                'Security' => ['color' => 'red', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                                                'Removed' => ['color' => 'gray', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                                                default => ['color' => 'purple', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                            };
                                        @endphp
                                        <div>
                                            <div class="flex items-center space-x-2 mb-2">
                                                <svg class="w-4 h-4 text-{{ $sectionConfig['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sectionConfig['icon'] }}"/>
                                                </svg>
                                                <h4 class="text-sm font-bold text-{{ $sectionConfig['color'] }}-700 dark:text-{{ $sectionConfig['color'] }}-400 uppercase tracking-wide">{{ $sectionName }}</h4>
                                            </div>
                                            <ul class="space-y-2 ml-6">
                                                @foreach($items as $item)
                                                    <li class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-{{ $sectionConfig['color'] }}-400 mr-2 align-middle"></span>
                                                        {!! preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-semibold text-gray-900 dark:text-white">$1</strong>', e($item)) !!}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
