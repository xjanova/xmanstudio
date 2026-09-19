<?php

namespace App\Support\Quotation;

/**
 * What the visitor wants to end up with, as opposed to what we call the service.
 *
 * The old builder opened with seven tiles named after our categories —
 * "พัฒนาเว็บไซต์", "โซลูชัน IoT" — which asks the customer to translate their
 * problem into our vocabulary before they can get a price. An outcome is the
 * same catalogue entered from their side: they say they want to sell things
 * online, and we work out that it means web_development plus a cart, hosting
 * and a way to be found.
 *
 * Two outcomes may resolve to the SAME service category and still be worth
 * keeping apart — a shop and a company site are both web_development, but they
 * want different things suggested, which is the whole point of this layer.
 *
 * `category` must match a QuotationCategory key of type=service, and
 * `suggested_addons` a key of type=addon. Nothing here invents catalogue
 * entries: a key that no longer exists is skipped rather than shown empty.
 */
class Outcomes
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'online_store' => [
                'th' => 'ขายของออนไลน์',
                'en' => 'Sell online',
                'desc_th' => 'ร้านค้าที่ลูกค้าสั่งและจ่ายเงินได้เอง ไม่ต้องทักแชทมาถามทีละคน',
                'desc_en' => 'A shop where customers order and pay by themselves',
                'category' => 'web_development',
                'category_fallback' => 'web',
                'suggested_addons' => ['hosting', 'delivery', 'seo_marketing'],
                'accent' => 'from-blue-500 to-cyan-500',
                'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
            ],
            'company_site' => [
                'th' => 'เว็บบริษัทให้คนค้นเจอ',
                'en' => 'A company site people can find',
                'desc_th' => 'หน้าเว็บที่ดูน่าเชื่อถือ เปิดบนมือถือได้จริง และขึ้นเมื่อลูกค้าค้นชื่อคุณ',
                'desc_en' => 'A credible site that works on phones and shows up in search',
                'category' => 'web_development',
                'category_fallback' => 'web',
                'suggested_addons' => ['seo_marketing', 'hosting', 'design'],
                'accent' => 'from-indigo-500 to-blue-500',
                'icon' => 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418',
            ],
            'mobile_app' => [
                'th' => 'มีแอปของตัวเอง',
                'en' => 'Your own mobile app',
                'desc_th' => 'แอปบน iOS และ Android จากโค้ดชุดเดียว พร้อมขึ้นสโตร์ทั้งสองฝั่ง',
                'desc_en' => 'One codebase on iOS and Android, ready for both stores',
                'category' => 'app_development',
                'category_fallback' => 'mobile',
                'suggested_addons' => ['delivery', 'support', 'design'],
                'accent' => 'from-emerald-500 to-green-500',
                'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
            ],
            'ai_chatbot' => [
                'th' => 'ให้ AI ตอบลูกค้าแทน',
                'en' => 'Let AI answer customers',
                'desc_th' => 'แชทบอทที่ตอบคำถามซ้ำ ๆ ได้เองทั้งวัน แล้วส่งต่อคนจริงเมื่อจำเป็น',
                'desc_en' => 'A bot that handles the repeat questions and hands the rest to a human',
                'category' => 'ai_chatbot',
                'category_fallback' => 'ai',
                'suggested_addons' => ['support', 'hosting'],
                'accent' => 'from-violet-500 to-purple-500',
                'icon' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
            ],
            'ai_content' => [
                'th' => 'ให้ AI ทำคอนเทนต์',
                'en' => 'AI-made content',
                'desc_th' => 'สร้างภาพและงานโฆษณาเป็นชุดได้เอง ไม่ต้องจ้างถ่ายใหม่ทุกแคมเปญ',
                'desc_en' => 'Generate images and campaign assets in batches',
                'category' => 'ai_image',
                'category_fallback' => 'ai',
                'suggested_addons' => ['design', 'seo_marketing'],
                'accent' => 'from-fuchsia-500 to-pink-500',
                'icon' => 'm2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
            ],
            'music_ai' => [
                'th' => 'ทำเพลงด้วย AI',
                'en' => 'AI music',
                'desc_th' => 'เพลงประกอบแบรนด์หรือคลิป ที่ใช้ได้โดยไม่ติดลิขสิทธิ์คนอื่น',
                'desc_en' => 'Original tracks for your brand or videos, free of third-party rights',
                'category' => 'music_ai',
                'category_fallback' => 'ai',
                'suggested_addons' => ['delivery', 'design'],
                'accent' => 'from-rose-500 to-red-500',
                'icon' => 'm9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z',
            ],
            'blockchain' => [
                'th' => 'เหรียญและสัญญาอัจฉริยะ',
                'en' => 'Tokens and smart contracts',
                'desc_th' => 'ออกเหรียญ ทำสัญญาอัจฉริยะ หรือระบบที่ตรวจสอบย้อนหลังได้',
                'desc_en' => 'Tokens, smart contracts, or an auditable on-chain system',
                'category' => 'blockchain',
                'category_fallback' => 'blockchain',
                'suggested_addons' => ['delivery', 'support'],
                'accent' => 'from-amber-500 to-orange-500',
                'icon' => 'M21 7.5V18M15 7.5V18M3 16.811V8.69c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811Z',
            ],
            'iot' => [
                'th' => 'ต่ออุปกรณ์เข้าอินเทอร์เน็ต',
                'en' => 'Connect devices',
                'desc_th' => 'เซ็นเซอร์ เครื่องจักร หรือระบบในอาคาร ที่ดูสถานะและสั่งงานจากมือถือได้',
                'desc_en' => 'Sensors, machines or building systems you can watch and control from a phone',
                'category' => 'iot',
                'category_fallback' => 'iot',
                'suggested_addons' => ['hosting', 'support', 'delivery'],
                'accent' => 'from-teal-500 to-cyan-500',
                'icon' => 'M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z',
            ],
        ];
    }

    /**
     * The outcome a visitor picked, or null when the key is not one of ours.
     *
     * @return array<string, mixed>|null
     */
    public static function find(?string $key): ?array
    {
        if ($key === null) {
            return null;
        }

        return static::all()[$key] ?? null;
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(static::all());
    }

    /**
     * Service category key behind an outcome.
     */
    public static function categoryFor(?string $key): ?string
    {
        return static::find($key)['category'] ?? null;
    }

    /**
     * Add-on category keys we put in front of someone who chose this outcome.
     * Everything else still exists, just folded away.
     *
     * @return array<int, string>
     */
    public static function suggestedAddons(?string $key): array
    {
        return static::find($key)['suggested_addons'] ?? [];
    }

    /**
     * Outcomes that lead to a given service category.
     *
     * Used when an existing quotation has a service_type but no outcome —
     * the 13 issued before this existed — so the builder can still reopen
     * with something sensible selected.
     *
     * @return array<int, string>
     */
    public static function forCategory(string $categoryKey): array
    {
        return array_keys(array_filter(
            static::all(),
            fn (array $o) => $o['category'] === $categoryKey
        ));
    }
}
