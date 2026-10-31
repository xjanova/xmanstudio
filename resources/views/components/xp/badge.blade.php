{{-- A tier badge: free (the product's own gradient), pro (amber, the same on every product), soon --}}
@props(['tier' => 'free', 'label' => null])
@php($tier = in_array($tier, ['free', 'pro', 'soon'], true) ? $tier : 'free')
<span {{ $attributes->merge(['class' => 'xp-badge xp-' . $tier]) }}>{{ $label ?? ['free' => 'ฟรี', 'pro' => 'PRO', 'soon' => 'เร็ว ๆ นี้'][$tier] }}</span>
