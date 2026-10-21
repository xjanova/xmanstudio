<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DGX Spark bundle (owner's decision, 2026-10-09)
    |--------------------------------------------------------------------------
    |
    | One NVIDIA DGX Spark (Leadtek, 128 GB, 4 TB, Thai warranty via the
    | distributor) + a lifetime CluadeX license + a lifetime BrainX Cloud
    | license, sold only at https://xman4289.com/dgx-spark (the CluadeX app
    | links to that exact path).
    |
    | Price rule: the JIB.co.th price + a fixed markup. The JIB price moves (it
    | was ฿182,900 earlier in 2026), so the reference price, the date it was
    | checked and the markup are edited at /admin/campaigns/dgx-spark — the
    | values below are only the defaults until the owner saves that page.
    | The bundle price is ALWAYS reference + markup: change the JIB price and
    | the bundle price follows, and the "+฿20,000" the page explains stays true.
    |
    | The customer pays first, by bank transfer or PromptPay only (no card —
    | gateway fees on ฿265,000 would eat ~฿8,000 of a ฿20,000 margin); the owner
    | buys the unit from the distributor after the money has cleared.
    |
    | App\Support\DgxSparkCampaign reads everything here.
    |
    */
    'dgx_spark' => [
        // Sets on offer. Remaining = cap − paid − reservations still inside their hold.
        'cap' => (int) env('DGX_CAMPAIGN_CAP', 20),

        // Defaults for the admin-editable values (Setting keys in DgxSparkCampaign).
        'reference_price' => 245000,
        'reference_checked_at' => '2026-10-09',
        'markup' => 20000,

        // ราคาที่แสดงรวม VAT แล้ว: ราคา JIB เป็นราคารวม VAT ส่วนต่าง ฿20,000 จึงเทียบกันได้ตรง ๆ
        // false = บวก VAT ทีหลังแบบตะกร้าของเว็บ (ลูกค้าจ่ายเกินราคาที่ประกาศอีก 7%)
        'price_includes_vat' => true,

        'reference' => [
            'store' => 'JIB',
            'url' => 'https://www.jib.co.th/web/product/readProduct/82239',
            'item' => '940-54242-0007-000',
            'title' => 'AI COMPUTER LEADTEK NVIDIA DGX SPARK',
        ],

        // A reservation is held this long for the transfer + slip; after that it is cancelled
        // and its set goes back on offer.
        'hold_hours' => (int) env('DGX_CAMPAIGN_HOLD_HOURS', 48),

        // Estimated delivery after the payment is confirmed (text shown to the customer).
        'delivery_estimate' => '7–14 วันทำการ',

        // The product row the order line points at (inactive: never listed, never in the cart).
        'product_slug' => 'dgx-spark-bundle',
        'product_sku' => 'DGX-SPARK-BUNDLE',

        // Issued when the owner marks the order paid — order lines at ฿0, so the existing
        // LicenseService delivers them exactly as it delivers a cart purchase.
        'licenses' => [
            'cluadex-ai-coding-assistant' => 'lifetime',
            'brainx' => 'lifetime',
        ],

        'payment_methods' => ['bank_transfer', 'promptpay'],

        // Media produced separately. Paths are under the web root (public_html/); a missing file
        // falls back to a CSS gradient (images) or hides the block (video / showcase screens).
        // The video is too big for git: it is uploaded to storage/app/public/videos/dgx-spark/ on the
        // server (served through the public storage link, like the WinXTools intro).
        // screen_* are frames of the promo's real screen recordings, client names already blurred.
        'media' => [
            'hero' => 'images/campaign/dgx-spark/hero-16x9.jpg',
            'square' => 'images/campaign/dgx-spark/square-1x1.jpg',
            'story' => 'images/campaign/dgx-spark/story-9x16.jpg',
            'video' => 'storage/videos/dgx-spark/promo-1080p.mp4',
            'poster' => 'images/campaign/dgx-spark/promo-poster.jpg',
            'screen_cluadex' => 'images/campaign/dgx-spark/screen-cluadex.webp',
            'screen_cluadex_app' => 'images/campaign/dgx-spark/screen-cluadex-app.webp',
            'screen_universe' => 'images/campaign/dgx-spark/screen-universe.webp',
            'screen_dashboard' => 'images/campaign/dgx-spark/screen-dashboard.webp',
            'screen_continue' => 'images/campaign/dgx-spark/screen-continue.webp',
            'screen_cowork' => 'images/campaign/dgx-spark/screen-cowork.webp',
            'screen_cowork_panel' => 'images/campaign/dgx-spark/screen-cowork-panel.webp',
            'gallery_side' => 'images/campaign/dgx-spark/gallery-side.webp',
            'gallery_rear' => 'images/campaign/dgx-spark/gallery-rear.webp',
            'gallery_ports' => 'images/campaign/dgx-spark/gallery-ports.webp',
            'gallery_office' => 'images/campaign/dgx-spark/gallery-office.webp',
            'nova' => 'images/campaign/dgx-spark/nova-closing.webp',
        ],

        // The promo video's chapters (seconds into promo-1080p.mp4) for the chapter list beside it.
        'video_chapters' => [
            [0, 'Nova แนะนำชุด DGX Spark'],
            [9, 'ซูเปอร์คอมพิวเตอร์ขนาดวางบนโต๊ะ'],
            [34, 'รันโมเดลระดับ 120B ในเครื่องเอง'],
            [53, 'พอร์ตครบ ต่อหลายเครื่องได้'],
            [64, 'ในชุดได้อะไร ราคาเท่าไร'],
            [76, 'AI ลืมงานเมื่อวาน? ทำงานซ้ำ?'],
            [83, 'CluadeX — สั่งงานภาษาไทย AI ลงมือเอง'],
            [95, 'BrainX — สมองกลางของ AI'],
            [107, '"ต่องานเมื่อวาน" ไม่ต้องเล่าใหม่'],
            [118, 'ห้อง Cowork — AI ทำงานเป็นทีม'],
            [139, 'บริการหลังการขาย'],
            [154, 'สั่งซื้อที่ไหน'],
            [160, 'Nova ฝากไว้ก่อนจาก'],
        ],

        // After-sales service promised in the promo (p6), spelled out on the page — the video's fine print
        // sends viewers here for the scope and conditions.
        'hot_service' => [
            'price' => 900,       // THB per month, for buyers of this bundle
            'regular' => 3000,    // THB per month, the normal rate
        ],

        // Measured throughput published by others — shown as "ผลทดสอบจากแหล่งอ้างอิง", never as
        // our promise. Single stream, batch 1, decode (token generation). Checked 2026-10-09.
        'benchmarks' => [
            [
                'model' => 'gpt-oss-120b',
                'engine' => 'llama.cpp',
                'quant' => 'MXFP4',
                'tps' => 55.4,
                'source' => 'nvidia-blog',
            ],
            [
                'model' => 'gpt-oss-120b',
                'engine' => 'Ollama',
                'quant' => 'Q4',
                'tps' => 42.1,
                'source' => 'nvidia-forum',
            ],
            [
                'model' => 'gpt-oss-120b',
                'engine' => 'vLLM',
                'quant' => '4-bit',
                'tps' => 60.7,
                'source' => 'nvidia-forum',
            ],
            [
                'model' => 'Qwen3-Coder-Next',
                'engine' => 'Ollama',
                'quant' => 'Q4',
                'tps' => 59.2,
                'source' => 'nvidia-forum',
            ],
            [
                'model' => 'Qwen3-Coder-Next',
                'engine' => 'vLLM',
                'quant' => '4-bit',
                'tps' => 74.8,
                'source' => 'nvidia-forum',
            ],
        ],

        'sources' => [
            'nvidia-blog' => [
                'title' => 'How NVIDIA DGX Spark’s Performance Enables Intensive AI Tasks',
                'publisher' => 'NVIDIA Technical Blog',
                'short' => 'NVIDIA Blog',
                'date' => '2025-10-24',
                'url' => 'https://developer.nvidia.com/blog/how-nvidia-dgx-sparks-performance-enables-intensive-ai-tasks',
            ],
            'nvidia-forum' => [
                'title' => 'Measured inference benchmarks on a single DGX Spark',
                'publisher' => 'NVIDIA Developer Forums (ผู้ใช้ทดสอบเอง)',
                'short' => 'NVIDIA Forums (ผู้ใช้วัดเอง)',
                'date' => '2026-08-10',
                'url' => 'https://forums.developer.nvidia.com/t/measured-inference-benchmarks-on-a-single-dgx-spark-same-harness-across-ollama-llama-cpp-and-vllm-notes-data-published/379766',
            ],
        ],
    ],
];
