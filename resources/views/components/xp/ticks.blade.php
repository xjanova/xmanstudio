{{-- A list of ✓ points; `pro` paints the ticks amber --}}
@props(['items' => [], 'pro' => false])
@if($items)
    <ul {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @foreach($items as $item)
            <li class="flex gap-3"><span class="xp-tick {{ $pro ? 'xp-tick-pro' : '' }}">✓</span><span class="text-gray-200">{{ $item }}</span></li>
        @endforeach
    </ul>
@endif
