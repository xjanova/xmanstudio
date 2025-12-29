<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->paginate(12);

        $categories = Category::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }

    public function services()
    {
        $services = [
            [
                'slug' => 'blockchain',
                'name' => 'Blockchain Development',
                'description' => 'พัฒนาโซลูชั่น Blockchain, Smart Contracts, DApp และ Cryptocurrency',
                'icon' => '🔗',
                'features' => [
                    'Smart Contract Development',
                    'DApp Development',
                    'Cryptocurrency Development',
                    'NFT Platform',
                    'Blockchain Consulting',
                ],
            ],
            [
                'slug' => 'web',
                'name' => 'เว็บไซต์สมัยใหม่',
                'description' => 'ออกแบบและพัฒนาเว็บไซต์ที่ทันสมัย Responsive รองรับทุกอุปกรณ์',
                'icon' => '🌐',
                'features' => [
                    'Responsive Web Design',
                    'E-commerce Website',
                    'Corporate Website',
                    'Web Application',
                    'CMS Development',
                ],
            ],
            [
                'slug' => 'mobile',
                'name' => 'แอพพลิเคชัน',
                'description' => 'พัฒนาแอพ iOS และ Android ด้วย Flutter',
                'icon' => '📱',
                'features' => [
                    'iOS App Development',
                    'Android App Development',
                    'Cross-platform with Flutter',
                    'App UI/UX Design',
                    'App Maintenance & Support',
                ],
            ],
            [
                'slug' => 'iot',
                'name' => 'IoT Solutions',
                'description' => 'ออกแบบและพัฒนาระบบ Internet of Things',
                'icon' => '⚡',
                'features' => [
                    'IoT Device Development',
                    'Sensor Integration',
                    'IoT Platform Development',
                    'Smart Home Solutions',
                    'Industrial IoT',
                ],
            ],
            [
                'slug' => 'network-security',
                'name' => 'Network & IT Security',
                'description' => 'ออกแบบ ติดตั้งระบบ Network และ IT Security',
                'icon' => '🔒',
                'features' => [
                    'Network Design & Setup',
                    'Firewall Configuration',
                    'Security Audit',
                    'Penetration Testing',
                    'IT Infrastructure',
                ],
            ],
            [
                'slug' => 'custom-software',
                'name' => 'Custom Software',
                'description' => 'เขียนโปรแกรมเฉพาะธุรกิจตามความต้องการ',
                'icon' => '💻',
                'features' => [
                    'Business Software Development',
                    'ERP Systems',
                    'CRM Systems',
                    'Inventory Management',
                    'Custom Solutions',
                ],
            ],
            [
                'slug' => 'ai',
                'name' => 'AI Services',
                'description' => 'บริการด้าน AI: วีดีโอ สื่อโฆษณา เพลง และอื่นๆ',
                'icon' => '🤖',
                'features' => [
                    'AI Video Generation',
                    'AI Advertising Content',
                    'AI Music Generation',
                    'Machine Learning Solutions',
                    'AI Consulting',
                ],
            ],
            [
                'slug' => 'flutter',
                'name' => 'Flutter & Android Studio',
                'description' => 'พัฒนาแอพด้วย Flutter บน Android Studio',
                'icon' => '📲',
                'features' => [
                    'Flutter Development',
                    'Android Studio Setup',
                    'Cross-platform Apps',
                    'Flutter Training',
                    'App Publishing Support',
                ],
            ],
        ];

        return view('services.index', compact('services'));
    }

    public function serviceDetail($slug)
    {
        $services = $this->getServicesData();
        $service = collect($services)->firstWhere('slug', $slug);

        if (!$service) {
            abort(404);
        }

        return view('services.show', compact('service'));
    }

    private function getServicesData()
    {
        return [
            [
                'slug' => 'blockchain',
                'name' => 'Blockchain Development',
                'description' => 'พัฒนาโซลูชั่น Blockchain, Smart Contracts, DApp และ Cryptocurrency',
                'icon' => '🔗',
                'features' => [
                    'Smart Contract Development',
                    'DApp Development',
                    'Cryptocurrency Development',
                    'NFT Platform',
                    'Blockchain Consulting',
                ],
            ],
            [
                'slug' => 'web',
                'name' => 'เว็บไซต์สมัยใหม่',
                'description' => 'ออกแบบและพัฒนาเว็บไซต์ที่ทันสมัย Responsive รองรับทุกอุปกรณ์',
                'icon' => '🌐',
                'features' => [
                    'Responsive Web Design',
                    'E-commerce Website',
                    'Corporate Website',
                    'Web Application',
                    'CMS Development',
                ],
            ],
        ];
    }
}
