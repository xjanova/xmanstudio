<footer style="background:var(--tron-void);border-top:1px solid rgba(0,229,255,.25);position:relative;overflow:hidden;">
    <div style="position:absolute;left:0;right:0;top:0;height:1px;background:linear-gradient(90deg,transparent,var(--tron-cyan),var(--tron-gold),var(--tron-cyan),transparent);"></div>
    <div style="max-width:1200px;margin:0 auto;padding:64px 32px 32px;">
        <div style="display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:48px;margin-bottom:48px;" class="retro-footer-grid">
            <div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <span class="tron-seal-ring" style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;color:var(--tron-gold);font-size:20px;">X</span>
                    <div>
                        <div style="font-family:var(--font-display);font-size:22px;letter-spacing:.06em;color:var(--fg-1);">XMAN <span class="tron-gold-foil">STUDIO</span></div>
                        <div style="font-family:var(--font-ui);font-size:9px;letter-spacing:.3em;color:var(--fg-3);margin-top:2px;">EST · MMXVIII · BANGKOK</div>
                    </div>
                </div>
                <p style="font-family:var(--font-serif);font-style:italic;color:var(--fg-2);font-size:15px;line-height:1.6;max-width:320px;">
                    "ผู้ผลิตซอฟต์แวร์และนวัตกรรมดิจิทัล สำหรับพันธมิตรผู้แสวงหาความแตกต่าง"
                </p>
            </div>
            @foreach([
                ['Services', ['Blockchain', 'Web Development', 'Mobile Apps', 'AI & ML', 'IoT Solutions']],
                ['Studio',   ['Manifesto', 'Archives', 'The Team', 'Metal-X Records']],
                ['Uplink',   ['080-6038-278', 'xjanovax@gmail.com', 'LINE @xmanstudio', 'Bangkok, Thailand']],
            ] as $col)
                <div>
                    <div style="font-family:var(--font-ui);font-size:10px;letter-spacing:.3em;color:var(--tron-gold);text-transform:uppercase;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid rgba(212,175,55,.25);">{{ $col[0] }}</div>
                    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;">
                        @foreach($col[1] as $it)
                            <li style="font-family:var(--font-serif);font-size:14px;color:var(--fg-2);cursor:pointer;">{{ $it }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div style="border-top:1px solid rgba(0,229,255,.15);padding-top:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;font-family:var(--font-mono);font-size:11px;color:var(--fg-3);letter-spacing:.1em;">
            <div>© MMXXVI · XMAN STUDIO · ALL TRANSMISSIONS RESERVED</div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="tron-pulse" style="width:6px;height:6px;background:var(--tron-green);border-radius:50%;"></span>
                <span>UPLINK ACTIVE</span>
            </div>
        </div>
    </div>
    <style>@media(max-width:860px){.retro-footer-grid{grid-template-columns:1fr 1fr !important;}}</style>
</footer>
