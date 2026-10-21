{{--
    The site logo with a glint that sweeps across it and stars that twinkle
    around it (universe.css .xu-logo). $src is App\Support\BrandLogo::darkUrl():
    the uploaded logo with its black ink turned white for the dark universe.
    $class sizes it: xu-logo--gate, xu-logo--hud or xu-logo--hero.
--}}
<span class="xu-logo {{ $class ?? '' }}" style="--logo: url('{{ $src }}');">
    <img src="{{ $src }}" alt="{{ $alt ?? 'XMAN Studio' }}" class="xu-logo__img" decoding="async" draggable="false">
    <span class="xu-logo__shine" aria-hidden="true"></span>
    <span class="xu-logo__stars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span>
</span>
