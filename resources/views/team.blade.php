@extends($publicLayout ?? 'layouts.app')

@section('title', 'ทีมงานและผู้บริหาร - XMAN Studio')
@section('meta_description', 'ทำความรู้จักทีมผู้บริหารและนักพัฒนาของ XMAN Studio ผู้เชี่ยวชาญด้าน IT Solutions และ Software Development ครบวงจร')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950 text-white py-28 overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary-900/30 via-transparent to-transparent"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,_var(--tw-gradient-stops))] from-purple-900/20 via-transparent to-transparent"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%239C92AC%22%20fill-opacity%3D%220.03%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center px-4 py-2 bg-primary-600/20 text-primary-300 text-sm font-semibold rounded-full mb-6 backdrop-blur-sm border border-primary-500/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Our Team & Leadership
        </div>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-6 tracking-tight">
            ทีมงาน<span class="bg-gradient-to-r from-primary-400 to-purple-400 bg-clip-text text-transparent">และผู้บริหาร</span>
        </h1>
        <p class="text-xl text-gray-400 max-w-3xl mx-auto leading-relaxed">
            เบื้องหลังความสำเร็จของ XMAN Studio คือทีมงานมืออาชีพที่มุ่งมั่นสร้างสรรค์เทคโนโลยีเพื่ออนาคต
        </p>
    </div>
</section>

<!-- Company Story -->
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">Our Story</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                    จากวิสัยทัศน์<br>
                    <span class="text-primary-600">สู่การสร้างสรรค์นวัตกรรม</span>
                </h2>
                <div class="space-y-4 text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                    <p>
                        <strong class="text-gray-900 dark:text-white">XMAN Enterprise</strong> ก่อตั้งขึ้นโดย <strong class="text-gray-900 dark:text-white">Mr. Entony (นายบุญณราช อุปเสน)</strong> ด้วยวิสัยทัศน์ที่ต้องการนำเทคโนโลยีที่ทันสมัยมาช่วยยกระดับธุรกิจและสังคมไทย บริษัทเริ่มต้นจากการพัฒนาซอฟต์แวร์และเว็บไซต์ให้กับธุรกิจท้องถิ่น และเติบโตขึ้นจนกลายเป็นบริษัทเทคโนโลยีสารสนเทศที่ให้บริการครบวงจร
                    </p>
                    <p>
                        ด้วยประสบการณ์กว่า <strong class="text-gray-900 dark:text-white">8 ปี</strong>ในวงการไอที เราได้พัฒนาโซลูชั่นที่หลากหลาย ตั้งแต่ระบบ Blockchain, AI, IoT จนถึง Mobile Application ให้กับลูกค้ามากกว่า 150 โปรเจค ทั้งธุรกิจ Startup จนถึงองค์กรระดับใหญ่
                    </p>
                    <p>
                        นอกจากธุรกิจด้านเทคโนโลยีแล้ว XMAN Enterprise ยังเป็นผู้อยู่เบื้องหลัง <strong class="text-gray-900 dark:text-white">Metal-X Project</strong> โปรเจคดนตรีบน YouTube ที่รวบรวมผลงานเพลงและคอนเทนต์สร้างสรรค์
                    </p>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-r from-primary-600/20 to-purple-600/20 rounded-3xl blur-3xl"></div>
                <div class="relative grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl p-6 text-white shadow-xl">
                            <div class="text-4xl font-black mb-1">8+</div>
                            <div class="text-primary-100 text-sm font-medium">ปีประสบการณ์</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 text-white shadow-xl">
                            <div class="text-4xl font-black mb-1">150+</div>
                            <div class="text-purple-100 text-sm font-medium">โปรเจคสำเร็จ</div>
                        </div>
                    </div>
                    <div class="space-y-4 pt-8">
                        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-6 text-white shadow-xl">
                            <div class="text-4xl font-black mb-1">50+</div>
                            <div class="text-emerald-100 text-sm font-medium">ลูกค้าพึงพอใจ</div>
                        </div>
                        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
                            <div class="text-4xl font-black mb-1">24/7</div>
                            <div class="text-amber-100 text-sm font-medium">ซัพพอร์ตทุกวัน</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Leadership Section (from database) -->
@if($leaders->isNotEmpty())
<section class="py-20 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">Leadership</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">ผู้บริหาร</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">ผู้นำที่ขับเคลื่อน XMAN Enterprise ให้ก้าวไปข้างหน้า</p>
        </div>

        @foreach($leaders as $leader)
        <div class="max-w-4xl mx-auto {{ !$loop->last ? 'mb-12' : '' }}">
            <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-48 bg-gradient-to-r from-primary-600 via-primary-700 to-purple-700"></div>
                <div class="absolute top-0 left-0 right-0 h-48 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2240%22%20height%3D%2240%22%20viewBox%3D%220%200%2040%2040%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22%23fff%22%20fill-opacity%3D%220.05%22%20fill-rule%3D%22evenodd%22%3E%3Cpath%20d%3D%22M0%2040L40%200H20L0%2020M40%2040V20L20%2040%22/%3E%3C/g%3E%3C/svg%3E')]"></div>

                <div class="relative pt-24 pb-10 px-8 md:px-12 text-center">
                    <!-- Avatar -->
                    <div class="mx-auto w-36 h-36 rounded-full bg-gradient-to-br from-primary-400 to-purple-600 p-1 shadow-2xl mb-6">
                        <div class="w-full h-full rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-5xl font-bold text-primary-600 dark:text-primary-400 overflow-hidden">
                            @if($leader->image)
                                <img src="{{ asset('storage/' . $leader->image) }}" alt="{{ $leader->name }}" class="w-full h-full object-cover rounded-full">
                            @else
                                <svg class="w-20 h-20 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            @endif
                        </div>
                    </div>

                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $leader->name }}</h3>
                    @if($leader->name_th)
                        <p class="text-lg text-gray-600 dark:text-gray-300 mb-1">{{ $leader->name_th }}</p>
                    @endif
                    <div class="inline-flex items-center px-4 py-1.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-6">
                        {{ $leader->position }}
                        @if($leader->position_th)
                            / {{ $leader->position_th }}
                        @endif
                    </div>

                    @if($leader->bio || $leader->bio_th)
                    <div class="max-w-2xl mx-auto space-y-4 text-gray-600 dark:text-gray-300 text-left md:text-center leading-relaxed">
                        @if($leader->bio_th)
                            <p>{{ $leader->bio_th }}</p>
                        @endif
                        @if($leader->bio)
                            <p>{{ $leader->bio }}</p>
                        @endif
                    </div>
                    @endif

                    <!-- Skills Tags -->
                    @if($leader->skills)
                    <div class="flex flex-wrap justify-center gap-2 mt-6">
                        @foreach($leader->skills_array as $skill)
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-full">{{ trim($skill) }}</span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Social Links -->
                    @if($leader->facebook_url || $leader->linkedin_url || $leader->github_url || $leader->website_url)
                    <div class="flex justify-center space-x-4 mt-6">
                        @if($leader->facebook_url)
                        <a href="{{ $leader->facebook_url }}" target="_blank" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition-colors shadow-lg">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        @endif
                        @if($leader->linkedin_url)
                        <a href="{{ $leader->linkedin_url }}" target="_blank" class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center hover:bg-blue-800 transition-colors shadow-lg">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        @endif
                        @if($leader->github_url)
                        <a href="{{ $leader->github_url }}" target="_blank" class="w-10 h-10 rounded-full bg-gray-800 text-white flex items-center justify-center hover:bg-gray-900 transition-colors shadow-lg">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        </a>
                        @endif
                        @if($leader->website_url)
                        <a href="{{ $leader->website_url }}" target="_blank" class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center hover:bg-emerald-700 transition-colors shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

<!-- Team Members (from database) -->
@if($members->isNotEmpty())
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">Team</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">ทีมงานของเรา</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">ทีมนักพัฒนามืออาชีพที่ผ่านการฝึกฝนและมีความเชี่ยวชาญในด้านต่างๆ</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @php
                $gradients = [
                    'from-blue-400 to-blue-600',
                    'from-purple-400 to-purple-600',
                    'from-green-400 to-emerald-600',
                    'from-amber-400 to-orange-600',
                    'from-rose-400 to-red-600',
                    'from-cyan-400 to-teal-600',
                    'from-indigo-400 to-indigo-600',
                    'from-pink-400 to-pink-600',
                ];
            @endphp

            @foreach($members as $member)
                @php $gradient = $gradients[$loop->index % count($gradients)]; @endphp
                <div class="group bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-8 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-transparent hover:border-primary-200 dark:hover:border-primary-800">
                    <div class="mx-auto w-24 h-24 rounded-full bg-gradient-to-br {{ $gradient }} flex items-center justify-center mb-5 shadow-lg group-hover:scale-110 transition-transform overflow-hidden">
                        @if($member->image)
                            <img src="{{ asset('storage/' . $member->image) }}" alt="{{ $member->name }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        @endif
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $member->name }}</h3>
                    @if($member->name_th)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $member->name_th }}</p>
                    @endif
                    <p class="text-primary-600 dark:text-primary-400 text-sm font-medium mb-3">
                        {{ $member->position }}
                        @if($member->position_th) / {{ $member->position_th }} @endif
                    </p>
                    @if($member->bio_th || $member->bio)
                        <p class="text-gray-600 dark:text-gray-300 text-sm">{{ $member->bio_th ?: $member->bio }}</p>
                    @endif

                    @if($member->skills)
                    <div class="flex flex-wrap justify-center gap-1.5 mt-4">
                        @foreach($member->skills_array as $skill)
                            <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 text-xs rounded-full">{{ trim($skill) }}</span>
                        @endforeach
                    </div>
                    @endif

                    @if($member->facebook_url || $member->linkedin_url || $member->github_url || $member->website_url)
                    <div class="flex justify-center space-x-3 mt-4">
                        @if($member->facebook_url)
                        <a href="{{ $member->facebook_url }}" target="_blank" class="text-gray-400 hover:text-blue-600 transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                        @endif
                        @if($member->linkedin_url)
                        <a href="{{ $member->linkedin_url }}" target="_blank" class="text-gray-400 hover:text-blue-700 transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
                        @endif
                        @if($member->github_url)
                        <a href="{{ $member->github_url }}" target="_blank" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg></a>
                        @endif
                        @if($member->website_url)
                        <a href="{{ $member->website_url }}" target="_blank" class="text-gray-400 hover:text-emerald-600 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg></a>
                        @endif
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Technology Stack -->
<section class="py-20 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">Tech Stack</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">เทคโนโลยีที่เราใช้</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">เครื่องมือและเทคโนโลยีที่ทีมงานเราเชี่ยวชาญ</p>
        </div>

        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            @php
                $techStacks = [
                    ['name' => 'Laravel', 'color' => 'from-red-500 to-red-700'],
                    ['name' => 'React', 'color' => 'from-cyan-400 to-cyan-600'],
                    ['name' => 'Flutter', 'color' => 'from-blue-400 to-blue-600'],
                    ['name' => 'Node.js', 'color' => 'from-green-500 to-green-700'],
                    ['name' => 'Python', 'color' => 'from-yellow-400 to-yellow-600'],
                    ['name' => 'AWS', 'color' => 'from-orange-400 to-orange-600'],
                    ['name' => 'Blockchain', 'color' => 'from-purple-500 to-purple-700'],
                    ['name' => 'AI/ML', 'color' => 'from-pink-500 to-rose-600'],
                    ['name' => 'Docker', 'color' => 'from-blue-500 to-blue-700'],
                    ['name' => 'MySQL', 'color' => 'from-teal-500 to-teal-700'],
                    ['name' => 'Tailwind', 'color' => 'from-sky-400 to-sky-600'],
                    ['name' => 'Git', 'color' => 'from-gray-600 to-gray-800'],
                ];
            @endphp

            @foreach($techStacks as $tech)
                <div class="group flex flex-col items-center">
                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-gradient-to-br {{ $tech['color'] }} flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform mb-3">
                        <span class="text-white font-bold text-xs md:text-sm">{{ substr($tech['name'], 0, 2) }}</span>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $tech['name'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Work Culture / Values -->
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">Culture</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">วัฒนธรรมการทำงาน</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">ค่านิยมที่ขับเคลื่อนทีมของเรา</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">นวัตกรรม</h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm">เราเชื่อในการใช้เทคโนโลยีใหม่ๆ เพื่อสร้างโซลูชั่นที่ดีที่สุด</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">คุณภาพ</h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm">ทุกโปรเจคผ่านการทดสอบอย่างเข้มงวดก่อนส่งมอบ</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ตรงเวลา</h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm">ใช้ Agile Methodology เพื่อส่งมอบงานตรงตามกำหนด</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ใส่ใจลูกค้า</h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm">ซัพพอร์ต 24/7 เพราะความสำเร็จของลูกค้าคือเป้าหมายของเรา</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-gray-900 via-primary-900 to-gray-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23fff%22%20fill-opacity%3D%220.03%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
    <div class="relative max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">อยากร่วมงานกับเรา?</h2>
        <p class="text-xl text-gray-300 mb-8">เรากำลังมองหาคนที่มีความสามารถและหลงใหลในเทคโนโลยี มาร่วมสร้างสรรค์สิ่งใหม่ๆ ไปด้วยกัน</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="/support" class="inline-flex items-center px-8 py-4 bg-primary-600 text-white font-bold text-lg rounded-xl transition-all duration-300 hover:bg-primary-500 hover:shadow-2xl hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                ติดต่อเรา
            </a>
            <a href="/about" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white/30 text-white font-bold text-lg rounded-xl transition-all duration-300 hover:bg-white/10 hover:border-white/50">
                เกี่ยวกับบริษัท
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>
@endsection
