<?php

return [
    // ปิดไว้เป็นค่าเริ่มต้น — เปิดด้วย DECOY_LOGIN=true เมื่อพร้อม (ปิดอยู่ = path ล่อ 404 ตามปกติ)
    'enabled' => env('DECOY_LOGIN', false),

    // Path ที่บอตชอบยิงหา CMS/เครื่องมือที่เราไม่ได้ใช้ ลูกค้า/ผู้ดูแลของเราไม่มีทางเข้า
    // ห้ามใส่ route จริง เช่น login หรือ admin เด็ดขาด
    'paths' => [
        'wp-login.php',
        'wp-admin',
        'wp-admin/setup-config.php',
        'xmlrpc.php',
        'administrator',
        'administrator/index.php',
        'phpmyadmin',
        'phpMyAdmin',
        'pma',
        'dbadmin',
        'mysql',
    ],

    // IP ที่จะไม่ log และไม่แบนเด็ดขาด (ของเราเอง/ผู้ดูแล) — เผื่อกรณีสุดวิสัย
    'safe_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('DECOY_SAFE_IPS', ''))))),

    // ไบต์สูงสุดของสิ่งที่ผู้โจมตีส่งมาที่จะเก็บเป็นหลักฐาน
    'capture_bytes' => (int) env('DECOY_CAPTURE_BYTES', 512),

    // ไฟล์ที่ Anti X ตามอ่าน (บรรทัดรูปแบบ "<ts> ANTIX-DECOY ip=.. port=.. data=..")
    'log' => env('DECOY_LOG', storage_path('logs/antix-decoy.log')),

    // แบนถาวรทันทีที่ชั้นเว็บด้วย (นอกเหนือจากที่ Anti X จะแบนให้) — ตัดคนยิงออกจาก xman4289 ทันที
    'block' => env('DECOY_BLOCK', true),
];
