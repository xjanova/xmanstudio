<?php

return [
    'bank' => ['code' => 'SCB', 'name' => 'ธนาคารไทยพาณิชย์', 'account' => '411-148476-9', 'holder' => 'บริษัท เอ็กซ์แมน เอนเตอร์ไพรส์ จำกัด'],
    'hub_origin' => env('GAME_SUPPORT_HUB_ORIGIN', 'https://xmangameshub.online'),
    // the hub's old home, still serving the same files while players move across (App\Support\HubOrigin)
    'legacy_hub_origins' => array_filter(explode(',', (string) env('GAME_SUPPORT_LEGACY_HUB_ORIGINS', 'https://xgameshub.xman4289.com'))),
    // logos on the hub (hub_origin + path); every other game uses /art/logos/<slug>.webp
    'logos' => [
        'hive-breach' => '/art/corewar-logo.webp',
        'breaker' => '/art/breaker/logo.webp',
    ],
    // key art on the hub (hub_origin + path); every other game uses /art/<slug>.webp
    'art' => [
        'hive-breach' => '/art/hive-corewar.webp',
        'breaker' => '/art/breaker/argus.webp',
        'chanthra' => '/art/hero/chanthra.webp',
        'tetrisvs' => '/art/hero/tetrisvs.webp',
        'snake' => '/art/hero/snake.webp',
        '8ball' => '/art/hero/8ball.webp',
        'snooker' => '/art/hero/snooker.webp',
        'tetris' => '/art/hero/tetris.webp',
        'space-shooter' => '/art/hero/space-shooter.webp',
    ],
    'tiers' => [
        ['minimum' => 100, 'name' => 'SPARK', 'rewards' => ['ตราผู้สนับสนุน SPARK บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
        ['minimum' => 300, 'name' => 'SALVAGER', 'rewards' => ['ตราผู้สนับสนุน SALVAGER บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
        ['minimum' => 1000, 'name' => 'WINGMATE', 'rewards' => ['ตราผู้สนับสนุน WINGMATE บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
        ['minimum' => 3000, 'name' => 'PATHFINDER', 'rewards' => ['ตราผู้สนับสนุน PATHFINDER บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
    ],
];
