{{-- Questions and answers: [[question, answer], …] --}}
@props(['items' => []])
<section {{ $attributes->merge(['class' => 'relative py-20']) }}>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <p class="xp-eyebrow mb-3">FAQ</p>
            <h2 class="text-3xl md:text-4xl font-black text-white">คำถามที่พบบ่อย</h2>
        </div>
        <div class="space-y-3">
            @foreach($items as [$q, $a])
                <details class="xp-faq xp-card px-5 py-4">
                    <summary class="flex items-center justify-between gap-4 text-white font-bold">
                        <span>{{ $q }}</span>
                        <span class="pm flex-none w-7 h-7 rounded-full border border-white/15 flex items-center justify-center text-lg leading-none">+</span>
                    </summary>
                    <p class="mt-3 text-gray-300 leading-relaxed">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
