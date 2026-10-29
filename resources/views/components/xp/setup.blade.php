{{--
    "Which machines" + "start in N steps", side by side.
    specs: [[label, Thai text, English], …]   steps: [[title, Thai text, English], …]
--}}
@props(['specs' => [], 'steps' => [], 'specsTitle' => 'ใช้กับเครื่องไหนได้บ้าง', 'stepsTitle' => null, 'specsNote' => null])
<section {{ $attributes->merge(['class' => 'relative py-20']) }}>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-8">
            <div>
                <p class="xp-eyebrow mb-3">Requirements</p>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-8">{{ $specsTitle }}</h2>
                <dl class="grid sm:grid-cols-2 gap-4">
                    @foreach($specs as [$k, $v, $en])
                        <div class="xp-card p-5">
                            <dt class="text-white font-bold mb-1">{{ $k }}</dt>
                            <dd class="text-sm text-gray-300">{{ $v }}</dd>
                            @if($en)<dd class="text-xs text-gray-500 mt-1">{{ $en }}</dd>@endif
                        </div>
                    @endforeach
                </dl>
                @if($specsNote)<p class="mt-4 text-xs text-gray-500">{{ $specsNote }}</p>@endif
            </div>
            <div>
                <p class="xp-eyebrow mb-3">Get started</p>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-8">{{ $stepsTitle ?? 'เริ่มใช้ใน ' . count($steps) . ' ขั้น' }}</h2>
                <ol class="space-y-4">
                    @foreach($steps as $n => [$title, $body, $en])
                        <li class="xp-card p-5 flex gap-4">
                            <span class="flex-none w-10 h-10 rounded-xl xp-btn-main flex items-center justify-center font-black text-lg">{{ $n + 1 }}</span>
                            <span>
                                <span class="block text-white font-bold">{{ $title }}</span>
                                <span class="block text-sm text-gray-300 mt-0.5">{{ $body }}</span>
                                @if($en)<span class="block text-xs text-gray-500 mt-1">{{ $en }}</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</section>
