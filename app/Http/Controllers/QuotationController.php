<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    /**
     * Service packages with detailed options
     */
    protected array $servicePackages = [
        'blockchain' => [
            'name' => 'Blockchain Development',
            'name_th' => 'พัฒนา Blockchain',
            'icon' => '🔗',
            'base_price' => 150000,
            'options' => [
                'smart_contract' => ['name' => 'Smart Contract Development', 'name_th' => 'พัฒนา Smart Contract', 'price' => 80000],
                'token' => ['name' => 'Token/Cryptocurrency', 'name_th' => 'สร้าง Token/Cryptocurrency', 'price' => 120000],
                'nft_marketplace' => ['name' => 'NFT Marketplace', 'name_th' => 'ระบบ NFT Marketplace', 'price' => 250000],
                'defi' => ['name' => 'DeFi Protocol', 'name_th' => 'ระบบ DeFi', 'price' => 350000],
                'wallet' => ['name' => 'Crypto Wallet', 'name_th' => 'กระเป๋า Crypto', 'price' => 180000],
                'audit' => ['name' => 'Smart Contract Audit', 'name_th' => 'ตรวจสอบ Smart Contract', 'price' => 50000],
            ],
        ],
        'web' => [
            'name' => 'Web Development',
            'name_th' => 'พัฒนาเว็บไซต์',
            'icon' => '🌐',
            'base_price' => 30000,
            'options' => [
                'landing' => ['name' => 'Landing Page', 'name_th' => 'Landing Page (1-5 หน้า)', 'price' => 15000],
                'corporate' => ['name' => 'Corporate Website', 'name_th' => 'เว็บไซต์องค์กร', 'price' => 45000],
                'ecommerce' => ['name' => 'E-commerce Platform', 'name_th' => 'ระบบ E-commerce', 'price' => 120000],
                'webapp' => ['name' => 'Web Application', 'name_th' => 'เว็บแอปพลิเคชัน', 'price' => 80000],
                'cms' => ['name' => 'Custom CMS', 'name_th' => 'ระบบจัดการเนื้อหา', 'price' => 60000],
                'api' => ['name' => 'REST API Development', 'name_th' => 'พัฒนา REST API', 'price' => 50000],
            ],
        ],
        'mobile' => [
            'name' => 'Mobile Application',
            'name_th' => 'แอปพลิเคชันมือถือ',
            'icon' => '📱',
            'base_price' => 80000,
            'options' => [
                'ios' => ['name' => 'iOS Native App', 'name_th' => 'แอป iOS (Swift)', 'price' => 150000],
                'android' => ['name' => 'Android Native App', 'name_th' => 'แอป Android (Kotlin)', 'price' => 130000],
                'flutter' => ['name' => 'Flutter Cross-platform', 'name_th' => 'Flutter (iOS+Android)', 'price' => 180000],
                'react_native' => ['name' => 'React Native', 'name_th' => 'React Native (iOS+Android)', 'price' => 170000],
                'maintenance' => ['name' => 'App Maintenance/Year', 'name_th' => 'ดูแลรักษาแอป/ปี', 'price' => 36000],
                'publish' => ['name' => 'App Store Publishing', 'name_th' => 'Publish ขึ้น Store', 'price' => 15000],
            ],
        ],
        'ai' => [
            'name' => 'AI Solutions',
            'name_th' => 'บริการ AI',
            'icon' => '🤖',
            'base_price' => 50000,
            'options' => [
                'chatbot' => ['name' => 'AI Chatbot', 'name_th' => 'Chatbot อัจฉริยะ', 'price' => 80000],
                'video' => ['name' => 'AI Video Generation', 'name_th' => 'สร้างวีดีโอ AI', 'price' => 25000],
                'music' => ['name' => 'AI Music Generation', 'name_th' => 'สร้างเพลง AI', 'price' => 15000],
                'image' => ['name' => 'AI Image Generation', 'name_th' => 'สร้างภาพ AI', 'price' => 20000],
                'voice' => ['name' => 'AI Voice/TTS', 'name_th' => 'เสียง AI/Text-to-Speech', 'price' => 30000],
                'ml_model' => ['name' => 'Custom ML Model', 'name_th' => 'สร้างโมเดล ML เฉพาะ', 'price' => 200000],
            ],
        ],
        'iot' => [
            'name' => 'IoT Solutions',
            'name_th' => 'โซลูชัน IoT',
            'icon' => '⚡',
            'base_price' => 100000,
            'options' => [
                'smart_home' => ['name' => 'Smart Home System', 'name_th' => 'ระบบ Smart Home', 'price' => 150000],
                'smart_farm' => ['name' => 'Smart Farm', 'name_th' => 'ระบบ Smart Farm', 'price' => 200000],
                'industrial' => ['name' => 'Industrial IoT', 'name_th' => 'IoT โรงงาน', 'price' => 350000],
                'sensor' => ['name' => 'Sensor Integration', 'name_th' => 'เชื่อมต่อเซ็นเซอร์', 'price' => 50000],
                'dashboard' => ['name' => 'IoT Dashboard', 'name_th' => 'Dashboard แสดงผลข้อมูล', 'price' => 80000],
                'hardware' => ['name' => 'Custom Hardware', 'name_th' => 'ออกแบบฮาร์ดแวร์', 'price' => 120000],
            ],
        ],
        'security' => [
            'name' => 'Network & IT Security',
            'name_th' => 'ระบบเครือข่ายและความปลอดภัย',
            'icon' => '🔒',
            'base_price' => 50000,
            'options' => [
                'network_setup' => ['name' => 'Network Design & Setup', 'name_th' => 'ออกแบบและติดตั้งเครือข่าย', 'price' => 80000],
                'firewall' => ['name' => 'Firewall Configuration', 'name_th' => 'ติดตั้ง Firewall', 'price' => 45000],
                'pentest' => ['name' => 'Penetration Testing', 'name_th' => 'ทดสอบเจาะระบบ', 'price' => 100000],
                'audit' => ['name' => 'Security Audit', 'name_th' => 'ตรวจสอบความปลอดภัย', 'price' => 60000],
                'monitoring' => ['name' => '24/7 Monitoring/Year', 'name_th' => 'ดูแลระบบ 24/7 ต่อปี', 'price' => 120000],
                'vpn' => ['name' => 'VPN Setup', 'name_th' => 'ติดตั้ง VPN องค์กร', 'price' => 35000],
            ],
        ],
        'software' => [
            'name' => 'Custom Software',
            'name_th' => 'ซอฟต์แวร์เฉพาะทาง',
            'icon' => '💻',
            'base_price' => 150000,
            'options' => [
                'erp' => ['name' => 'ERP System', 'name_th' => 'ระบบ ERP', 'price' => 500000],
                'crm' => ['name' => 'CRM System', 'name_th' => 'ระบบ CRM', 'price' => 250000],
                'pos' => ['name' => 'POS System', 'name_th' => 'ระบบ POS', 'price' => 80000],
                'inventory' => ['name' => 'Inventory Management', 'name_th' => 'ระบบจัดการสินค้าคงคลัง', 'price' => 120000],
                'hr' => ['name' => 'HR Management', 'name_th' => 'ระบบบริหารทรัพยากรบุคคล', 'price' => 180000],
                'accounting' => ['name' => 'Accounting System', 'name_th' => 'ระบบบัญชี', 'price' => 200000],
            ],
        ],
        'flutter' => [
            'name' => 'Flutter & Training',
            'name_th' => 'Flutter และอบรม',
            'icon' => '📲',
            'base_price' => 30000,
            'options' => [
                'training_basic' => ['name' => 'Flutter Basic Training', 'name_th' => 'อบรม Flutter เบื้องต้น', 'price' => 15000],
                'training_advanced' => ['name' => 'Flutter Advanced', 'name_th' => 'อบรม Flutter ขั้นสูง', 'price' => 25000],
                'consulting' => ['name' => 'Flutter Consulting', 'name_th' => 'ที่ปรึกษา Flutter', 'price' => 5000],
                'code_review' => ['name' => 'Code Review', 'name_th' => 'ตรวจสอบโค้ด', 'price' => 10000],
                'mentoring' => ['name' => 'Monthly Mentoring', 'name_th' => 'Mentor รายเดือน', 'price' => 20000],
                'workshop' => ['name' => 'Team Workshop', 'name_th' => 'Workshop สำหรับทีม', 'price' => 50000],
            ],
        ],
    ];

    /**
     * Additional options available for all services
     */
    protected array $additionalOptions = [
        'priority' => ['name' => 'Priority Support', 'name_th' => 'ซัพพอร์ตเร่งด่วน', 'price' => 30000],
        'source_code' => ['name' => 'Full Source Code', 'name_th' => 'Source Code ทั้งหมด', 'price' => 50000],
        'documentation' => ['name' => 'Technical Documentation', 'name_th' => 'เอกสารเทคนิค', 'price' => 20000],
        'training' => ['name' => 'User Training', 'name_th' => 'อบรมการใช้งาน', 'price' => 15000],
        'warranty_1y' => ['name' => '1 Year Warranty', 'name_th' => 'รับประกัน 1 ปี', 'price' => 25000],
        'warranty_2y' => ['name' => '2 Year Warranty', 'name_th' => 'รับประกัน 2 ปี', 'price' => 45000],
        'hosting_1y' => ['name' => 'Cloud Hosting 1 Year', 'name_th' => 'Hosting 1 ปี', 'price' => 12000],
        'ssl' => ['name' => 'SSL Certificate', 'name_th' => 'ใบรับรอง SSL', 'price' => 3000],
        'domain' => ['name' => 'Domain Registration', 'name_th' => 'จดโดเมน 1 ปี', 'price' => 500],
    ];

    /**
     * Show the quotation form
     */
    public function index()
    {
        return view('support.index', [
            'services' => $this->servicePackages,
            'additionalOptions' => $this->additionalOptions,
        ]);
    }

    /**
     * Generate quotation preview
     */
    public function preview(Request $request)
    {
        $validated = $this->validateRequest($request);
        $quotation = $this->calculateQuotation($validated);

        return response()->json($quotation);
    }

    /**
     * Generate and download PDF quotation
     */
    public function generatePdf(Request $request)
    {
        $validated = $this->validateRequest($request);
        $quotation = $this->calculateQuotation($validated);

        $pdf = Pdf::loadView('quotation.pdf', [
            'quotation' => $quotation,
            'companyInfo' => $this->getCompanyInfo(),
        ])->setPaper('a4', 'portrait');

        $filename = 'XMAN-Quotation-' . $quotation['quote_number'] . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Validate request data
     */
    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_company' => 'nullable|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'service_type' => 'required|string|in:' . implode(',', array_keys($this->servicePackages)),
            'service_options' => 'required|array|min:1',
            'service_options.*' => 'string',
            'additional_options' => 'nullable|array',
            'additional_options.*' => 'string',
            'project_description' => 'nullable|string|max:2000',
            'timeline' => 'nullable|string|in:urgent,normal,flexible',
            'budget_range' => 'nullable|string',
        ]);
    }

    /**
     * Calculate quotation details
     */
    protected function calculateQuotation(array $data): array
    {
        $service = $this->servicePackages[$data['service_type']];
        $items = [];
        $subtotal = 0;

        // Add selected service options
        foreach ($data['service_options'] as $optionKey) {
            if (isset($service['options'][$optionKey])) {
                $option = $service['options'][$optionKey];
                $items[] = [
                    'name' => $option['name'],
                    'name_th' => $option['name_th'],
                    'price' => $option['price'],
                    'type' => 'service',
                ];
                $subtotal += $option['price'];
            }
        }

        // Add additional options
        if (!empty($data['additional_options'])) {
            foreach ($data['additional_options'] as $optionKey) {
                if (isset($this->additionalOptions[$optionKey])) {
                    $option = $this->additionalOptions[$optionKey];
                    $items[] = [
                        'name' => $option['name'],
                        'name_th' => $option['name_th'],
                        'price' => $option['price'],
                        'type' => 'additional',
                    ];
                    $subtotal += $option['price'];
                }
            }
        }

        // Calculate discount for large projects
        $discount = 0;
        $discountPercent = 0;
        if ($subtotal >= 1000000) {
            $discountPercent = 15;
        } elseif ($subtotal >= 500000) {
            $discountPercent = 10;
        } elseif ($subtotal >= 200000) {
            $discountPercent = 5;
        }
        $discount = $subtotal * ($discountPercent / 100);

        // Rush fee for urgent timeline
        $rushFee = 0;
        if (($data['timeline'] ?? '') === 'urgent') {
            $rushFee = $subtotal * 0.25;
        }

        $total = $subtotal - $discount + $rushFee;
        $vat = $total * 0.07;
        $grandTotal = $total + $vat;

        return [
            'quote_number' => 'QT-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'quote_date' => now()->format('d/m/Y'),
            'valid_until' => now()->addDays(30)->format('d/m/Y'),
            'customer' => [
                'name' => $data['customer_name'],
                'company' => $data['customer_company'] ?? '',
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone'],
                'address' => $data['customer_address'] ?? '',
            ],
            'service' => [
                'name' => $service['name'],
                'name_th' => $service['name_th'],
                'icon' => $service['icon'],
            ],
            'items' => $items,
            'project_description' => $data['project_description'] ?? '',
            'timeline' => $data['timeline'] ?? 'normal',
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount' => $discount,
            'rush_fee' => $rushFee,
            'total_before_vat' => $total,
            'vat' => $vat,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Get company information
     */
    protected function getCompanyInfo(): array
    {
        return [
            'name' => 'XMAN STUDIO',
            'tagline' => 'IT Solutions & Software Development',
            'address' => 'กรุงเทพมหานคร ประเทศไทย',
            'email' => 'info@xmanstudio.com',
            'phone' => '+66 XX XXX XXXX',
            'website' => 'www.xmanstudio.com',
            'line' => '@xmanstudio',
            'tax_id' => 'X-XXXX-XXXXX-XX-X',
        ];
    }

    /**
     * Get service packages (for API)
     */
    public function getServices()
    {
        return response()->json([
            'services' => $this->servicePackages,
            'additional_options' => $this->additionalOptions,
        ]);
    }
}
