@if($ad && $ad->enabled && $ad->code)
<div class="google-ad-placement google-ad-{{ $position }}" data-position="{{ $position }}" data-page="{{ $page }}">
    {!! $ad->code !!}
</div>
@endif
