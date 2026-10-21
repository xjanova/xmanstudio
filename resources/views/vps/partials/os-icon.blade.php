{{--
    ไอคอนระบบปฏิบัติการ / แอป / แผงควบคุม สำหรับหน้าสั่งเช่า VPS

    @include('vps.partials.os-icon', ['name' => $templateName, 'class' => 'w-8 h-8'])

    เลือกจากคำในชื่อเทมเพลต (ตัวพิมพ์เล็ก) เทมเพลตแบบ "X with Y" ดูที่ Y ก่อน
    เพราะสิ่งที่ติดตั้งมาให้คือสิ่งที่ลูกค้าเลือก ไม่ใช่ OS ฐาน — Y ที่ไม่รู้จัก
    ได้ไอคอนกล่องแอป ส่วน OS ที่ไม่รู้จักได้ไอคอนเซิร์ฟเวอร์ ไม่เดาเป็น OS อื่น

    รูปเป็นสัญลักษณ์แบบย่อที่วาดเอง ไม่ใช่โลโก้จริง ไม่มี id ภายใน SVG จึงวางซ้ำ
    กี่ครั้งก็ได้ ข้อความในป้ายมาจากตารางด้านล่างเท่านั้น ไม่เคยมาจากชื่อเทมเพลต
    คลาสทุกตัวเขียนเต็มไว้ในไฟล์นี้ (Tailwind ไม่เห็นคลาสที่ต่อสตริงขึ้นมา)
--}}
@php
    $iconName = mb_strtolower(trim((string) ($name ?? '')));
    $iconClass = $class ?? 'w-8 h-8';
    $withAt = strpos($iconName, ' with ');
    $iconExtra = $withAt !== false ? substr($iconName, $withAt + 6) : '';

    // [รูปแบบ regex, คลาสพื้นป้าย, รูป, ตัวอักษร (เฉพาะรูป mono)]
    $appRules = [
        ['/docker/', 'from-[#2EA8F7] to-[#1D63ED]', 'docker', null],
        ['/kubernetes|\bk3s\b|\bk8s\b|microk8s/', 'from-[#4F86F0] to-[#2451B8]', 'wheel', null],
        ['/portainer/', 'from-[#22C7F9] to-[#0B8FC7]', 'layers', null],
        ['/coolify|dokploy|caprover|dokku/', 'from-[#9B6BFA] to-[#6D28D9]', 'layers', null],
        ['/n8n/', 'from-[#F0597F] to-[#C21D4D]', 'nodes', null],
        ['/wordpress|woocommerce/', 'from-[#3A8FC7] to-[#1E5F85]', 'wordpress', null],
        ['/ollama|claude|gemini|codex|\bgrok\b|deepseek|herdr|nemoclaw|\bmcp\b|anaconda|open ?webui|\bllm\b|\bai\b/', 'from-[#8B5CF6] via-[#D946EF] to-[#F97316]', 'spark', null],
        ['/game|minecraft|pterodactyl|palworld|valheim|\bcs2\b/', 'from-[#7C5CFA] to-[#4338CA]', 'game', null],
        ['/cpanel|\bwhm\b/', 'from-[#FF7A3D] to-[#E4501A]', 'panel', null],
        ['/plesk/', 'from-[#5CC4EE] to-[#2E83C5]', 'panel', null],
        ['/directadmin/', 'from-[#3F7BC4] to-[#1D4E8C]', 'panel', null],
        ['/webmin|virtualmin/', 'from-[#3470B8] to-[#153F74]', 'panel', null],
        ['/cloudpanel/', 'from-[#2F8BF7] to-[#0550B5]', 'panel', null],
        ['/cyberpanel/', 'from-[#6763F0] to-[#2F2BB8]', 'panel', null],
        ['/hestia/', 'from-[#F0654E] to-[#B02A15]', 'panel', null],
        ['/aapanel/', 'from-[#34BF52] to-[#15802B]', 'panel', null],
        ['/panel|webuzo|tinycp|adminbolt|kusanagi|cloudron|cosmos|ispconfig|froxlor/', 'from-[#6D7CF5] to-[#4338CA]', 'panel', null],
        ['/nextcloud/', 'from-[#1A9AE0] to-[#00639A]', 'rings', null],
        ['/owncloud|seafile|filebrowser/', 'from-[#3B82F6] to-[#1D4ED8]', 'cloud', null],
        ['/plex/', 'from-[#F2B51C] to-[#CC7B19]', 'chevron', null],
        ['/jellyfin|emby|media/', 'from-[#B26BD1] to-[#0A8FD0]', 'play', null],
        ['/grafana|prometheus|zabbix|netdata|uptime|monitor/', 'from-[#F7892D] to-[#D9480F]', 'chart', null],
        ['/gitlab|gitea|forgejo|jenkins|\bgit\b/', 'from-[#FC8A4F] to-[#E24329]', 'branch', null],
        ['/wireguard|openvpn|\bvpn\b|pritunl|tailscale|headscale/', 'from-[#E0453C] to-[#8F1D19]', 'shield', null],
        ['/mail/', 'from-[#38BDF8] to-[#2563EB]', 'mail', null],
        ['/mastodon|mattermost|rocket\.?chat|matrix|synapse|discourse|chat/', 'from-[#7B7CFF] to-[#563ACC]', 'chat', null],
        ['/postgres|mysql|mariadb|mongo|redis|database|clickhouse/', 'from-[#4F83CC] to-[#1E4C8F]', 'database', null],
        ['/supabase|appwrite|pocketbase/', 'from-[#4ADE9B] to-[#16A34A]', 'bolt', null],
        ['/litespeed/', 'from-[#FBBF24] to-[#D97706]', 'globe', null],
        ['/nginx/', 'from-[#22B04F] to-[#006B28]', 'globe', null],
        ['/\blamp\b|\blemp\b|apache|web ?server/', 'from-[#38BDF8] to-[#0369A1]', 'globe', null],
        ['/node\.?js|\bnode\b/', 'from-[#6CB55A] to-[#3E7F33]', 'hexagon', null],
        ['/laravel/', 'from-[#FF5A4F] to-[#C81E14]', 'mono', 'L'],
        ['/django/', 'from-[#1A7A55] to-[#0C4B33]', 'mono', 'dj'],
        ['/rails|ruby/', 'from-[#E0322F] to-[#A00000]', 'mono', 'Rb'],
        ['/python/', 'from-[#3776AB] to-[#1E4F7A]', 'mono', 'Py'],
        ['/golang|\bgo\b/', 'from-[#29BEE0] to-[#007D9C]', 'mono', 'Go'],
        ['/\bphp\b/', 'from-[#8C8FC9] to-[#5A5E9A]', 'mono', 'php'],
        ['/magento/', 'from-[#F58A4F] to-[#D4531A]', 'mono', 'M'],
        ['/prestashop/', 'from-[#EC3F8C] to-[#B8004F]', 'mono', 'P'],
        ['/odoo/', 'from-[#8F6285] to-[#5A3A52]', 'mono', 'O'],
        ['/joomla/', 'from-[#5FA0DD] to-[#2F6CA8]', 'mono', 'J'],
        ['/drupal/', 'from-[#2A95DA] to-[#05588C]', 'mono', 'D'],
        ['/ghost/', 'from-[#475569] to-[#15171A]', 'mono', 'G'],
        ['/moodle/', 'from-[#FA9A3B] to-[#D96C00]', 'mono', 'm'],
        ['/erpnext|frappe/', 'from-[#3B9BFF] to-[#0060C7]', 'mono', 'E'],
        ['/matomo|plausible|umami|analytics/', 'from-[#5B7BE0] to-[#2F4BA8]', 'chart', null],
    ];

    $osRules = [
        ['/ubuntu/', 'from-[#F7853B] to-[#DD4814]', 'ubuntu', null],
        ['/debian/', 'from-[#E4245E] to-[#A80030]', 'debian', null],
        ['/alma/', 'from-[#16507F] to-[#0A2C47]', 'alma', null],
        ['/rocky/', 'from-[#22C38E] to-[#07875F]', 'rocky', null],
        ['/centos/', 'from-[#4B3A8F] to-[#262577]', 'centos', null],
        ['/fedora/', 'from-[#5AAEE6] to-[#294172]', 'fedora', null],
        ['/\barch\b/', 'from-[#2BA3E0] to-[#0F6DA0]', 'arch', null],
        ['/alpine/', 'from-[#1A74A3] to-[#083B55]', 'alpine', null],
        ['/kali/', 'from-[#4B8BF5] to-[#1E3F8F]', 'mono', 'K'],
        ['/suse/', 'from-[#7FC43A] to-[#2F8F5B]', 'mono', 'oS'],
        ['/nixos/', 'from-[#7EBAE4] to-[#4467B8]', 'snow', null],
        ['/cloudlinux/', 'from-[#23A8E6] to-[#005C9E]', 'cloud', null],
        ['/oracle/', 'from-[#E0563F] to-[#A8321F]', 'mono', 'OL'],
        ['/freebsd|openbsd/', 'from-[#C8413D] to-[#8C1D1A]', 'mono', 'BSD'],
        ['/windows/', 'from-[#1C8FE8] to-[#005A9E]', 'windows', null],
    ];

    $pick = function (string $haystack, array $rules): ?array {
        foreach ($rules as $rule) {
            if ($haystack !== '' && preg_match($rule[0], $haystack)) {
                return $rule;
            }
        }

        return null;
    };

    if ($iconExtra !== '') {
        $iconRule = $pick($iconExtra, $appRules) ?? [null, 'from-slate-500 to-slate-700', 'cube', null];
    } else {
        $iconRule = $pick($iconName, $osRules) ?? $pick($iconName, $appRules) ?? [null, 'from-slate-500 to-slate-700', 'server', null];
    }

    [, $iconTile, $iconGlyph, $iconMono] = $iconRule;
    $monoSize = match (mb_strlen((string) $iconMono)) {
        1 => '13',
        2 => '10.5',
        default => '8.2',
    };
@endphp
<span class="{{ $iconClass }} relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-[28%] bg-gradient-to-br {{ $iconTile }} text-white shadow-sm shadow-black/20 ring-1 ring-inset ring-white/15" aria-hidden="true">
    <span class="absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/20 to-transparent"></span>
    <svg class="relative w-[62%] h-[62%]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        @switch($iconGlyph)
            @case('ubuntu')
                <path d="M12.94 17.32A5.4 5.4 0 0 1 6.93 13.85M6.93 10.15A5.4 5.4 0 0 1 12.94 6.68M16.14 8.53A5.4 5.4 0 0 1 16.14 15.47" stroke-width="2.1"/>
                <circle cx="4.5" cy="12" r="2.05" fill="currentColor" stroke="none"/>
                <circle cx="15.75" cy="5.5" r="2.05" fill="currentColor" stroke="none"/>
                <circle cx="15.75" cy="18.5" r="2.05" fill="currentColor" stroke="none"/>
                @break
            @case('debian')
                <path d="M17.4 7.1C15.6 5.1 12.7 4.5 10.2 5.4 6.7 6.7 4.7 10.5 5.7 14.1c1 3.6 5 5.6 8.4 4.5 3.1-1 4.8-4.3 4-7.3-.7-2.6-3.3-4-5.7-3.4-2.3.6-3.7 3-3 5.2.6 1.8 2.6 2.8 4.4 2.1" stroke-width="2"/>
                @break
            @case('alma')
                <circle cx="12" cy="6.6" r="2.35" fill="#FF4649" stroke="none"/>
                <circle cx="17.14" cy="10.33" r="2.35" fill="#FFCB12" stroke="none"/>
                <circle cx="15.18" cy="16.37" r="2.35" fill="#86DA2F" stroke="none"/>
                <circle cx="8.82" cy="16.37" r="2.35" fill="#24C2FF" stroke="none"/>
                <circle cx="6.86" cy="10.33" r="2.35" fill="#FF8A3D" stroke="none"/>
                <circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/>
                @break
            @case('rocky')
                <circle cx="12" cy="12" r="7.6" stroke-width="2"/>
                <path d="M5.6 15.4l4.3-4.4 2.7 2.7 2-2 3.9 3.8" stroke-width="2"/>
                @break
            @case('centos')
                <path d="M12 3.6l3.6 3.6L12 10.8 8.4 7.2z" fill="#9CCD2A" stroke="none"/>
                <path d="M20.4 12l-3.6 3.6-3.6-3.6 3.6-3.6z" fill="#EFA724" stroke="none"/>
                <path d="M12 20.4l-3.6-3.6 3.6-3.6 3.6 3.6z" fill="#E36AC0" stroke="none"/>
                <path d="M3.6 12l3.6-3.6 3.6 3.6-3.6 3.6z" fill="#8FA2FF" stroke="none"/>
                @break
            @case('fedora')
                <circle cx="12" cy="12" r="7.8"/>
                <path d="M15.3 7.9h-1.4a2.4 2.4 0 0 0-2.4 2.4v7.5M8.9 12.4h5" stroke-width="2.1"/>
                @break
            @case('arch')
                <path d="M12 3.6l8.2 16.4c-2.7-1.8-5-2.7-6.6-3 .3-1.8-.4-3.6-1.6-3.6s-1.9 1.8-1.6 3.6c-1.6.3-3.9 1.2-6.6 3z" fill="currentColor" stroke="none"/>
                @break
            @case('alpine')
                <path d="M2.8 18.2l6.3-9.7 3.4 5.2 2.6-3.6 6.1 8.1z" fill="currentColor" fill-opacity=".25"/>
                <path d="M9.1 8.5l1.9 2.9 1.4-1.2" />
                @break
            @case('snow')
                <path d="M12 3.8v16.4M4.9 7.9l14.2 8.2M4.9 16.1l14.2-8.2M10 4.9l2 1.6 2-1.6M10 19.1l2-1.6 2 1.6" stroke-width="1.9"/>
                @break
            @case('cloud')
                <path d="M7.4 18.2h9.4a3.9 3.9 0 0 0 .5-7.77A5.6 5.6 0 0 0 6.6 11.3a3.45 3.45 0 0 0 .8 6.9z"/>
                @break
            @case('windows')
                <path d="M4.2 4.2h7v7h-7zM12.8 4.2h7v7h-7zM4.2 12.8h7v7h-7zM12.8 12.8h7v7h-7z" fill="currentColor" stroke="none"/>
                @break
            @case('docker')
                <path d="M2.6 12.6h15.6c.8-1.3 2.1-1.8 3.2-1.5-.3 1.1-1.1 1.9-2.1 2.2-1.3 3.6-4.8 5.9-9.1 5.9-4.1 0-7-2.4-7.6-6.6z" fill="currentColor" stroke="none"/>
                <path d="M4.3 9.3h2.5v2.5H4.3zM7.3 9.3h2.5v2.5H7.3zM10.3 9.3h2.5v2.5h-2.5zM13.3 9.3h2.5v2.5h-2.5zM7.3 6.3h2.5v2.5H7.3zM10.3 6.3h2.5v2.5h-2.5zM10.3 3.3h2.5v2.5h-2.5z" fill="currentColor" stroke="none"/>
                @break
            @case('wheel')
                <circle cx="12" cy="12" r="7.8"/>
                <circle cx="12" cy="12" r="2.1"/>
                <path d="M12 9.9V4.2M13.64 10.69l4.46-3.56M14.05 12.47l5.55 1.27M12.91 13.9l2.47 5.13M11.09 13.9l-2.47 5.13M9.95 12.47l-5.55 1.27M10.36 10.69 5.9 7.13"/>
                @break
            @case('layers')
                <path d="M12 3.8l8.4 4.4L12 12.6 3.6 8.2z"/>
                <path d="M3.6 12.1 12 16.5l8.4-4.4M3.6 15.9 12 20.3l8.4-4.4"/>
                @break
            @case('nodes')
                <circle cx="4.8" cy="15.2" r="2.2"/>
                <circle cx="11.6" cy="15.2" r="2.2"/>
                <circle cx="19.2" cy="15.2" r="2.2"/>
                <circle cx="19.2" cy="7.6" r="2.2"/>
                <path d="M7 15.2h2.4M13.8 15.2H17M13 13.5c1.2-3.3 2.3-5.4 4-5.8"/>
                @break
            @case('wordpress')
                <circle cx="12" cy="12" r="8"/>
                <path d="M7.4 9.1l2.5 7.2 2.1-5.8 2.1 5.8 2.5-7.2M6.4 9.1h2.2M15.4 9.1h2.2" stroke-width="1.6"/>
                @break
            @case('spark')
                <path d="M10.6 3.8c.65 4.1 2.55 6.05 6.8 6.8-4.25.75-6.15 2.7-6.8 6.8-.65-4.1-2.55-6.05-6.8-6.8 4.25-.75 6.15-2.7 6.8-6.8z" fill="currentColor" stroke="none"/>
                <path d="M18.2 14.6c.28 1.55.98 2.25 2.5 2.5-1.52.25-2.22.95-2.5 2.5-.28-1.55-.98-2.25-2.5-2.5 1.52-.25 2.22-.95 2.5-2.5z" fill="currentColor" stroke="none"/>
                @break
            @case('game')
                <path d="M7.6 7.6h8.8a4 4 0 0 1 3.9 3.1l.95 4.2a2.4 2.4 0 0 1-4.1 2.2l-1.5-1.6H8.35l-1.5 1.6a2.4 2.4 0 0 1-4.1-2.2l.95-4.2a4 4 0 0 1 3.9-3.1z"/>
                <path d="M8 10.4v3.2M6.4 12h3.2"/>
                <circle cx="15.6" cy="11" r=".9" fill="currentColor" stroke="none"/>
                <circle cx="17.4" cy="13" r=".9" fill="currentColor" stroke="none"/>
                @break
            @case('panel')
                <rect x="3.4" y="4.4" width="17.2" height="15.2" rx="2.4"/>
                <path d="M3.4 8.6h17.2M8.6 8.6v11"/>
                <path d="M11.6 12.2h5.6M11.6 15.8h3.4"/>
                @break
            @case('rings')
                <circle cx="12" cy="12" r="3.9" stroke-width="2"/>
                <circle cx="4.9" cy="12" r="2.2" stroke-width="2"/>
                <circle cx="19.1" cy="12" r="2.2" stroke-width="2"/>
                @break
            @case('chevron')
                <path d="M8.2 4.6h4.6l5.4 7.4-5.4 7.4H8.2l5.4-7.4z" fill="currentColor" stroke="none"/>
                @break
            @case('play')
                <circle cx="12" cy="12" r="8"/>
                <path d="M10.2 8.6v6.8l5.6-3.4z" fill="currentColor"/>
                @break
            @case('chart')
                <path d="M4 19.6h16"/>
                <path d="M5.6 15.6l3.9-4.6 3.1 2.8 5.4-7"/>
                <path d="M14.7 6.6h3.3v3.3"/>
                @break
            @case('branch')
                <circle cx="7" cy="5.8" r="2.1"/>
                <circle cx="7" cy="18.2" r="2.1"/>
                <circle cx="17" cy="8.2" r="2.1"/>
                <path d="M7 7.9v8.2M17 10.3c0 3.6-3.6 4.6-8.5 6.3"/>
                @break
            @case('shield')
                <path d="M12 3.6l7 2.8v5.2c0 4.4-2.9 7.9-7 9-4.1-1.1-7-4.6-7-9V6.4z"/>
                <path d="M9.2 12.1l2 2 3.7-3.9"/>
                @break
            @case('mail')
                <rect x="3.4" y="5.6" width="17.2" height="12.8" rx="2.2"/>
                <path d="M4 7.2l8 5.9 8-5.9"/>
                @break
            @case('chat')
                <path d="M5.2 5h13.6a1.8 1.8 0 0 1 1.8 1.8v8.4a1.8 1.8 0 0 1-1.8 1.8h-8.3l-4.3 3.3V17h-1a1.8 1.8 0 0 1-1.8-1.8V6.8A1.8 1.8 0 0 1 5.2 5z"/>
                <path d="M8.4 11h.01M12 11h.01M15.6 11h.01" stroke-width="2.4"/>
                @break
            @case('database')
                <ellipse cx="12" cy="6.4" rx="7" ry="2.8"/>
                <path d="M5 6.4v11.2c0 1.55 3.13 2.8 7 2.8s7-1.25 7-2.8V6.4M5 12c0 1.55 3.13 2.8 7 2.8s7-1.25 7-2.8"/>
                @break
            @case('bolt')
                <path d="M13.6 3.2 5.4 13.6h6.1l-1.1 7.2 8.2-10.4h-6.1z" fill="currentColor" stroke="none"/>
                @break
            @case('globe')
                <circle cx="12" cy="12" r="8"/>
                <path d="M4 12h16M12 4c2.2 2.3 3.3 5 3.3 8s-1.1 5.7-3.3 8c-2.2-2.3-3.3-5-3.3-8S9.8 6.3 12 4z"/>
                @break
            @case('hexagon')
                <path d="M12 3.2l7.6 4.4v8.8L12 20.8l-7.6-4.4V7.6z"/>
                <path d="M12 8.6v6.8M9.1 10.3l2.9 1.7 2.9-1.7"/>
                @break
            @case('mono')
                <text x="12" y="12" dy=".36em" text-anchor="middle" font-size="{{ $monoSize }}" font-weight="800" letter-spacing="-.2" fill="currentColor" stroke="none" font-family="Inter, ui-sans-serif, system-ui, sans-serif">{{ $iconMono }}</text>
                @break
            @case('cube')
                <path d="M12 3.4l7.6 4.3v8.6L12 20.6l-7.6-4.3V7.7z"/>
                <path d="M4.4 7.7 12 12l7.6-4.3M12 12v8.6"/>
                @break
            @default
                <rect x="4.4" y="4.6" width="15.2" height="6" rx="1.6"/>
                <rect x="4.4" y="13.4" width="15.2" height="6" rx="1.6"/>
                <path d="M8 7.6h.01M8 16.4h.01M11.4 7.6h4.4M11.4 16.4h4.4" stroke-width="2"/>
        @endswitch
    </svg>
</span>
