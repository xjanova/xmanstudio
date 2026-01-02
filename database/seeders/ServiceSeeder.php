<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'slug' => 'blockchain',
                'name' => 'Blockchain Development',
                'name_th' => 'พัฒนา Blockchain',
                'description' => 'Build secure and scalable blockchain solutions including Smart Contracts, DApps, and Cryptocurrency platforms.',
                'description_th' => 'พัฒนาโซลูชั่น Blockchain, Smart Contracts, DApp และ Cryptocurrency ที่ปลอดภัยและสามารถขยายได้',
                'icon' => '🔗',
                'features' => [
                    'Smart Contract Development',
                    'DApp Development',
                    'Cryptocurrency Development',
                    'NFT Platform',
                    'Blockchain Consulting',
                ],
                'features_th' => [
                    'พัฒนา Smart Contract',
                    'พัฒนา DApp',
                    'พัฒนา Cryptocurrency',
                    'แพลตฟอร์ม NFT',
                    'ที่ปรึกษา Blockchain',
                ],
                'order' => 1,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'web',
                'name' => 'Web Development',
                'name_th' => 'เว็บไซต์สมัยใหม่',
                'description' => 'Modern and responsive website design and development for all devices.',
                'description_th' => 'ออกแบบและพัฒนาเว็บไซต์ที่ทันสมัย Responsive รองรับทุกอุปกรณ์',
                'icon' => '🌐',
                'features' => [
                    'Responsive Web Design',
                    'E-commerce Website',
                    'Corporate Website',
                    'Web Application',
                    'CMS Development',
                ],
                'features_th' => [
                    'ออกแบบเว็บ Responsive',
                    'เว็บไซต์ E-commerce',
                    'เว็บไซต์องค์กร',
                    'เว็บแอปพลิเคชัน',
                    'พัฒนา CMS',
                ],
                'order' => 2,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'mobile',
                'name' => 'Mobile App Development',
                'name_th' => 'แอพพลิเคชัน',
                'description' => 'iOS and Android mobile application development with Flutter.',
                'description_th' => 'พัฒนาแอพ iOS และ Android ด้วย Flutter',
                'icon' => '📱',
                'features' => [
                    'iOS App Development',
                    'Android App Development',
                    'Cross-platform with Flutter',
                    'App UI/UX Design',
                    'App Maintenance & Support',
                ],
                'features_th' => [
                    'พัฒนาแอพ iOS',
                    'พัฒนาแอพ Android',
                    'Cross-platform ด้วย Flutter',
                    'ออกแบบ UI/UX แอพ',
                    'ดูแลและสนับสนุนแอพ',
                ],
                'order' => 3,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'iot',
                'name' => 'IoT Solutions',
                'name_th' => 'โซลูชั่น IoT',
                'description' => 'Design and develop Internet of Things systems and solutions.',
                'description_th' => 'ออกแบบและพัฒนาระบบ Internet of Things',
                'icon' => '⚡',
                'features' => [
                    'IoT Device Development',
                    'Sensor Integration',
                    'IoT Platform Development',
                    'Smart Home Solutions',
                    'Industrial IoT',
                ],
                'features_th' => [
                    'พัฒนาอุปกรณ์ IoT',
                    'การเชื่อมต่อเซ็นเซอร์',
                    'พัฒนาแพลตฟอร์ม IoT',
                    'โซลูชั่น Smart Home',
                    'IoT สำหรับอุตสาหกรรม',
                ],
                'order' => 4,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'slug' => 'network-security',
                'name' => 'Network & IT Security',
                'name_th' => 'Network & IT Security',
                'description' => 'Design, installation and management of Network and IT Security systems.',
                'description_th' => 'ออกแบบ ติดตั้งระบบ Network และ IT Security',
                'icon' => '🔒',
                'features' => [
                    'Network Design & Setup',
                    'Firewall Configuration',
                    'Security Audit',
                    'Penetration Testing',
                    'IT Infrastructure',
                ],
                'features_th' => [
                    'ออกแบบและติดตั้งระบบเครือข่าย',
                    'ตั้งค่า Firewall',
                    'ตรวจสอบความปลอดภัย',
                    'ทดสอบเจาะระบบ',
                    'โครงสร้างพื้นฐาน IT',
                ],
                'order' => 5,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'slug' => 'custom-software',
                'name' => 'Custom Software',
                'name_th' => 'ซอฟต์แวร์เฉพาะ',
                'description' => 'Custom software development tailored to your business needs.',
                'description_th' => 'เขียนโปรแกรมเฉพาะธุรกิจตามความต้องการ',
                'icon' => '💻',
                'features' => [
                    'Business Software Development',
                    'ERP Systems',
                    'CRM Systems',
                    'Inventory Management',
                    'Custom Solutions',
                ],
                'features_th' => [
                    'พัฒนาซอฟต์แวร์สำหรับธุรกิจ',
                    'ระบบ ERP',
                    'ระบบ CRM',
                    'ระบบจัดการสินค้าคงคลัง',
                    'โซลูชั่นเฉพาะ',
                ],
                'order' => 6,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'slug' => 'ai',
                'name' => 'AI Services',
                'name_th' => 'บริการ AI',
                'description' => 'AI services including video, advertising, music generation and more.',
                'description_th' => 'บริการด้าน AI: วีดีโอ สื่อโฆษณา เพลง และอื่นๆ',
                'icon' => '🤖',
                'features' => [
                    'AI Video Generation',
                    'AI Advertising Content',
                    'AI Music Generation',
                    'Machine Learning Solutions',
                    'AI Consulting',
                ],
                'features_th' => [
                    'สร้างวีดีโอด้วย AI',
                    'สร้างเนื้อหาโฆษณาด้วย AI',
                    'สร้างเพลงด้วย AI',
                    'โซลูชั่น Machine Learning',
                    'ที่ปรึกษา AI',
                ],
                'order' => 7,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'flutter',
                'name' => 'Flutter & Android Studio',
                'name_th' => 'Flutter & Android Studio',
                'description' => 'Mobile app development with Flutter on Android Studio.',
                'description_th' => 'พัฒนาแอพด้วย Flutter บน Android Studio',
                'icon' => '📲',
                'features' => [
                    'Flutter Development',
                    'Android Studio Setup',
                    'Cross-platform Apps',
                    'Flutter Training',
                    'App Publishing Support',
                ],
                'features_th' => [
                    'พัฒนาด้วย Flutter',
                    'ติดตั้ง Android Studio',
                    'แอพ Cross-platform',
                    'อบรม Flutter',
                    'สนับสนุนการ Publish แอพ',
                ],
                'order' => 8,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']],
                $service
            );
        }
    }
}
