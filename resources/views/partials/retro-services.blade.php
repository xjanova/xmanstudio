<section style="max-width:1200px;margin:0 auto;padding:120px 32px;">
    <div style="text-align:center;margin-bottom:64px;">
        <div style="font-family:var(--font-ui);font-size:11px;letter-spacing:.4em;color:var(--tron-cyan);text-transform:uppercase;margin-bottom:16px;">— Our Programs —</div>
        <h2 style="font-family:var(--font-display);font-size:clamp(40px,5vw,64px);line-height:1.05;margin:0 0 20px;color:var(--fg-1);">
            Six Divisions.<br><span class="tron-gold-foil">One Studio.</span>
        </h2>
        <p style="font-family:var(--font-serif);font-size:19px;font-style:italic;color:var(--fg-3);max-width:560px;margin:0 auto;">
            "บริการครบวงจรจากทีมผู้เชี่ยวชาญ — พร้อมเทคโนโลยีล่าสุด และความขลังของฝีมือเดิม"
        </p>
    </div>

    @php
        $services = $services ?? [
            ['num'=>'01', 'title'=>'Blockchain',             'thai'=>'บล็อกเชน & สมาร์ทคอนแทรคต์', 'desc'=>'Smart Contracts · DeFi protocols · NFT marketplaces. Audited, gas-optimized, production-grade.', 'tag'=>'Flagship', 'color'=>'#00e5ff', 'href'=>url('/services#blockchain')],
            ['num'=>'02', 'title'=>'Web Development',        'thai'=>'พัฒนาเว็บไซต์',               'desc'=>'E-Commerce · corporate sites · bespoke web applications. Built to perform and built to last.', 'tag'=>null,        'color'=>'#4dd0e1', 'href'=>url('/services#web')],
            ['num'=>'03', 'title'=>'Mobile Software',        'thai'=>'แอปพลิเคชันมือถือ',           'desc'=>'iOS, Android, React Native, Flutter. One codebase — every pocket.',                             'tag'=>'New',       'color'=>'#ff2d95', 'href'=>url('/services#mobile')],
            ['num'=>'04', 'title'=>'Artificial Intelligence','thai'=>'ปัญญาประดิษฐ์',              'desc'=>'Conversational agents · computer vision · NLP pipelines. Trained on your data.',                'tag'=>'Hot',       'color'=>'#d4af37', 'href'=>url('/services#ai')],
            ['num'=>'05', 'title'=>'Internet of Things',     'thai'=>'อินเตอร์เน็ตของสรรพสิ่ง',     'desc'=>'Smart home · industrial telemetry · edge compute. From sensor to dashboard.',                   'tag'=>null,        'color'=>'#7c4dff', 'href'=>url('/services#iot')],
            ['num'=>'06', 'title'=>'Consulting',             'thai'=>'ที่ปรึกษาด้านไอที',          'desc'=>'Digital transformation · architecture review · team mentorship. Wisdom on retainer.',            'tag'=>null,        'color'=>'#4ade80', 'href'=>url('/services#consulting')],
        ];
    @endphp

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;">
        @foreach($services as $s)
            <a href="{{ $s['href'] }}" class="tron-panel retro-card" data-color="{{ $s['color'] }}" style="padding:28px 24px;cursor:pointer;transition:all .4s var(--ease-tron);position:relative;text-decoration:none;color:inherit;display:block;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
                    <div style="font-family:var(--font-mono);font-size:10px;color:var(--fg-3);letter-spacing:.2em;">№ {{ $s['num'] }}</div>
                    @if($s['tag'])
                        <span style="font-family:var(--font-ui);font-size:9px;padding:3px 10px;letter-spacing:.25em;color:{{ $s['color'] }};border:1px solid {{ $s['color'] }};text-transform:uppercase;">{{ $s['tag'] }}</span>
                    @endif
                </div>
                <div style="width:56px;height:56px;border:1px solid {{ $s['color'] }};border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px;box-shadow:0 0 16px {{ $s['color'] }}44, inset 0 0 12px {{ $s['color'] }}22;position:relative;">
                    <div style="position:absolute;inset:-4px;border:1px solid {{ $s['color'] }}33;border-radius:50%;"></div>
                    <div style="font-family:var(--font-display);font-size:22px;color:{{ $s['color'] }};text-shadow:0 0 8px {{ $s['color'] }}99;">⬢</div>
                </div>
                <h3 style="font-family:var(--font-display);font-size:26px;margin:0 0 4px;color:var(--fg-1);line-height:1.15;">{{ $s['title'] }}</h3>
                <div style="font-family:var(--font-serif);font-style:italic;font-size:15px;color:var(--fg-3);margin-bottom:16px;">{{ $s['thai'] }}</div>
                <p style="font-family:var(--font-serif);font-size:15px;line-height:1.55;color:var(--fg-2);margin:0 0 20px;">{{ $s['desc'] }}</p>
                <div style="display:flex;align-items:center;gap:8px;font-family:var(--font-ui);font-size:10px;letter-spacing:.25em;color:{{ $s['color'] }};text-transform:uppercase;">
                    <span style="flex:1;height:1px;background:linear-gradient(90deg,{{ $s['color'] }},transparent);"></span>
                    <span>Access →</span>
                </div>
            </a>
        @endforeach
    </div>
</section>

<script>
    document.querySelectorAll('.retro-card').forEach(function(el){
        var c = el.getAttribute('data-color');
        el.addEventListener('mouseenter', function(){
            el.style.transform='translateY(-4px)';
            el.style.boxShadow='0 0 0 1px '+c+', 0 0 30px '+c+'55';
            el.style.borderColor=c;
        });
        el.addEventListener('mouseleave', function(){
            el.style.transform='';
            el.style.boxShadow='';
            el.style.borderColor='rgba(0,229,255,.25)';
        });
    });
</script>
