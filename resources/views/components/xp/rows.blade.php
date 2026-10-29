{{--
    Feature rows, picture and words swapping sides row by row.
    item: name, eyebrow?, tier? (free|pro|soon), th, en?, points[]?, img?, mock?, alt?, phone?, legend? ([[colour, label], …])
--}}
@props(['items' => []])
<div {{ $attributes->merge(['class' => 'space-y-20 lg:space-y-28']) }}>
    @foreach($items as $i => $row)
        @php($phone = ! empty($row['phone']))
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-14 items-center">
            <div class="{{ $phone ? 'lg:col-span-5 max-w-xs mx-auto w-full' : 'lg:col-span-7' }} {{ $i % 2 === 1 ? 'lg:order-2' : '' }}">
                @if(! empty($row['img']))
                    <x-xp.screen :src="$row['img']" :alt="$row['alt'] ?? $row['name']" :mock="! empty($row['mock'])" :phone="$phone"
                                 :width="$phone ? 390 : 1600" :height="$row['h'] ?? ($phone ? 845 : 1000)" />
                @endif
            </div>
            <div class="{{ $phone ? 'lg:col-span-7' : 'lg:col-span-5' }} {{ $i % 2 === 1 ? 'lg:order-1' : '' }}">
                @if(! empty($row['eyebrow']))<p class="xp-eyebrow mb-3">{{ $row['eyebrow'] }}</p>@endif
                <h3 class="text-2xl md:text-4xl font-black text-white mb-4 flex flex-wrap items-center gap-3">
                    {{ $row['name'] }}
                    @if(! empty($row['tier']))<x-xp.badge :tier="$row['tier']" />@endif
                </h3>
                <p class="text-lg text-gray-300 leading-relaxed">{{ $row['th'] }}</p>
                @if(! empty($row['en']))<p class="text-sm text-gray-500 leading-relaxed mt-2">{{ $row['en'] }}</p>@endif
                @if(! empty($row['legend']))
                    <p class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-300">
                        @foreach($row['legend'] as [$colour, $label])
                            <span><b class="inline-block w-3 h-3 rounded mr-1.5 align-[-1px]" style="background: {{ $colour }}"></b>{{ $label }}</span>
                        @endforeach
                    </p>
                @endif
                <x-xp.ticks :items="$row['points'] ?? []" :pro="($row['tier'] ?? null) === 'pro'" class="mt-6" />
            </div>
        </div>
    @endforeach
</div>
