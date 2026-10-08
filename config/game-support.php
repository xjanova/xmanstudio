<?php

return [
    'bank' => ['code' => 'SCB', 'name' => 'ธนาคารไทยพาณิชย์', 'account' => '411-148476-9', 'holder' => 'บริษัท เอ็กซ์แมน เอนเตอร์ไพรส์ จำกัด'],
    'hub_origin' => env('GAME_SUPPORT_HUB_ORIGIN', 'https://xgameshub.xman4289.com'),
    'tiers' => [
        ['minimum' => 100, 'name' => 'SPARK', 'rewards' => ['ตราผู้สนับสนุน SPARK บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
        ['minimum' => 300, 'name' => 'SALVAGER', 'rewards' => ['ตราผู้สนับสนุน SALVAGER บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
        ['minimum' => 1000, 'name' => 'WINGMATE', 'rewards' => ['ตราผู้สนับสนุน WINGMATE บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
        ['minimum' => 3000, 'name' => 'PATHFINDER', 'rewards' => ['ตราผู้สนับสนุน PATHFINDER บนหน้าโครงการ', 'รายนามผู้สนับสนุนตามความสมัครใจ']],
    ],
];
