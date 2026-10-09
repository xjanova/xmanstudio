@props(['disabled' => false])

{{-- Size, border and focus ring come from the site-wide field styles in app.css. --}}
<input @disabled($disabled) {{ $attributes }}>
