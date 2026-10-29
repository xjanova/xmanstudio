{{--
    A tool explorer: tabs on the left, one panel at a time on the right (all panels show without script).
    tool: key, name, short, tier?, th, en?, points[]?, img?, mock?, alt?
--}}
@props(['id', 'label' => '', 'tools' => []])
<div {{ $attributes->merge(['class' => 'grid lg:grid-cols-12 gap-6']) }} data-xp-explorer>
    <div class="lg:col-span-4 flex lg:flex-col gap-2 overflow-x-auto pb-2 lg:pb-0 -mx-4 px-4 lg:mx-0 lg:px-0" role="tablist" aria-label="{{ $label }}">
        @foreach($tools as $k => $tool)
            <button type="button" role="tab" data-tab id="{{ $id }}-tab-{{ $tool['key'] }}" aria-controls="{{ $id }}-panel-{{ $tool['key'] }}"
                    aria-selected="{{ $k === 0 ? 'true' : 'false' }}"
                    class="xp-tab flex-none lg:flex-auto w-60 lg:w-full px-4 py-3 flex items-center gap-3">
                <span class="min-w-0 flex-1">
                    <span class="n block font-bold text-gray-200">{{ $tool['name'] }}</span>
                    <span class="block text-xs text-gray-500 truncate">{{ $tool['short'] }}</span>
                </span>
                @if(! empty($tool['tier']))<x-xp.badge :tier="$tool['tier']" />@endif
            </button>
        @endforeach
    </div>
    <div class="lg:col-span-8">
        @foreach($tools as $tool)
            <div class="xp-panel" role="tabpanel" data-panel id="{{ $id }}-panel-{{ $tool['key'] }}" aria-labelledby="{{ $id }}-tab-{{ $tool['key'] }}">
                <div class="xp-card p-5 sm:p-7 backdrop-blur-sm">
                    <h3 class="text-2xl font-black text-white mb-3 flex flex-wrap items-center gap-3">
                        {{ $tool['name'] }}
                        @if(! empty($tool['tier']))<x-xp.badge :tier="$tool['tier']" />@endif
                    </h3>
                    <p class="text-gray-300 leading-relaxed">{{ $tool['th'] }}</p>
                    @if(! empty($tool['en']))<p class="text-sm text-gray-500 leading-relaxed mt-2">{{ $tool['en'] }}</p>@endif
                    <x-xp.ticks :items="$tool['points'] ?? []" :pro="($tool['tier'] ?? null) === 'pro'" class="mt-5" />
                    @if(! empty($tool['img']))
                        <x-xp.screen :src="$tool['img']" :alt="$tool['alt'] ?? $tool['name']" :mock="! empty($tool['mock'])" class="mt-7" />
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
