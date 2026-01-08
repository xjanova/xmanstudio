<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use App\Services\LineNotifyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    /**
     * Service packages with detailed categorized options
     */
    protected array $servicePackages = [
        'blockchain' => [
            'name' => 'Blockchain Development',
            'name_th' => 'พัฒนา Blockchain',
            'icon' => '🔗',
            'color' => 'purple',
            'base_price' => 150000,
            'categories' => [
                'smart_contract' => [
                    'name' => 'Smart Contract',
                    'name_th' => 'Smart Contract',
                    'icon' => '📜',
                    'options' => [
                        'sc_erc20' => ['name' => 'ERC-20 Token Contract', 'name_th' => 'Smart Contract ERC-20 Token', 'price' => 50000],
                        'sc_erc721' => ['name' => 'ERC-721 NFT Contract', 'name_th' => 'Smart Contract NFT ERC-721', 'price' => 80000],
                        'sc_erc1155' => ['name' => 'ERC-1155 Multi-Token', 'name_th' => 'Smart Contract Multi-Token ERC-1155', 'price' => 100000],
                        'sc_staking' => ['name' => 'Staking Contract', 'name_th' => 'Smart Contract Staking', 'price' => 120000],
                        'sc_vesting' => ['name' => 'Token Vesting Contract', 'name_th' => 'Smart Contract Vesting', 'price' => 80000],
                        'sc_governance' => ['name' => 'DAO Governance Contract', 'name_th' => 'Smart Contract DAO/Governance', 'price' => 150000],
                        'sc_custom' => ['name' => 'Custom Smart Contract', 'name_th' => 'Smart Contract แบบกำหนดเอง', 'price' => 100000],
                    ],
                ],
                'defi' => [
                    'name' => 'DeFi Solutions',
                    'name_th' => 'DeFi โซลูชัน',
                    'icon' => '💰',
                    'options' => [
                        'defi_dex' => ['name' => 'DEX (Decentralized Exchange)', 'name_th' => 'DEX ระบบแลกเปลี่ยนกระจายศูนย์', 'price' => 500000],
                        'defi_amm' => ['name' => 'AMM (Automated Market Maker)', 'name_th' => 'AMM ระบบสร้างสภาพคล่อง', 'price' => 400000],
                        'defi_lending' => ['name' => 'Lending/Borrowing Protocol', 'name_th' => 'ระบบกู้ยืม Crypto', 'price' => 450000],
                        'defi_yield' => ['name' => 'Yield Farming Platform', 'name_th' => 'ระบบ Yield Farming', 'price' => 350000],
                        'defi_bridge' => ['name' => 'Cross-chain Bridge', 'name_th' => 'Bridge ข้ามเชน', 'price' => 600000],
                    ],
                ],
                'nft' => [
                    'name' => 'NFT Solutions',
                    'name_th' => 'NFT โซลูชัน',
                    'icon' => '🎨',
                    'options' => [
                        'nft_marketplace' => ['name' => 'NFT Marketplace', 'name_th' => 'ตลาด NFT Marketplace', 'price' => 350000],
                        'nft_minting' => ['name' => 'NFT Minting Platform', 'name_th' => 'ระบบ Mint NFT', 'price' => 150000],
                        'nft_launchpad' => ['name' => 'NFT Launchpad', 'name_th' => 'NFT Launchpad', 'price' => 250000],
                        'nft_generative' => ['name' => 'Generative Art Engine', 'name_th' => 'ระบบสร้าง Generative NFT', 'price' => 100000],
                        'nft_gaming' => ['name' => 'NFT for Gaming', 'name_th' => 'NFT สำหรับเกม', 'price' => 200000],
                    ],
                ],
                'token' => [
                    'name' => 'Token & Crypto',
                    'name_th' => 'Token & Crypto',
                    'icon' => '🪙',
                    'options' => [
                        'token_create' => ['name' => 'Custom Token Creation', 'name_th' => 'สร้าง Token แบบกำหนดเอง', 'price' => 80000],
                        'token_ico' => ['name' => 'ICO/IDO Platform', 'name_th' => 'ระบบ ICO/IDO', 'price' => 300000],
                        'token_presale' => ['name' => 'Token Presale Platform', 'name_th' => 'ระบบ Presale Token', 'price' => 200000],
                        'token_airdrop' => ['name' => 'Airdrop System', 'name_th' => 'ระบบ Airdrop Token', 'price' => 50000],
                    ],
                ],
                'wallet' => [
                    'name' => 'Wallet & Security',
                    'name_th' => 'Wallet & Security',
                    'icon' => '👛',
                    'options' => [
                        'wallet_web3' => ['name' => 'Web3 Wallet Integration', 'name_th' => 'เชื่อมต่อ Web3 Wallet', 'price' => 50000],
                        'wallet_custom' => ['name' => 'Custom Crypto Wallet', 'name_th' => 'กระเป๋า Crypto แบบกำหนดเอง', 'price' => 250000],
                        'wallet_multisig' => ['name' => 'Multi-signature Wallet', 'name_th' => 'กระเป๋า Multi-sig', 'price' => 150000],
                        'wallet_audit' => ['name' => 'Smart Contract Audit', 'name_th' => 'ตรวจสอบ Smart Contract', 'price' => 80000],
                    ],
                ],
            ],
        ],
        'web' => [
            'name' => 'Web Development',
            'name_th' => 'พัฒนาเว็บไซต์',
            'icon' => '🌐',
            'color' => 'blue',
            'base_price' => 30000,
            'categories' => [
                'website' => [
                    'name' => 'Website',
                    'name_th' => 'เว็บไซต์',
                    'icon' => '🏠',
                    'options' => [
                        'web_landing' => ['name' => 'Landing Page', 'name_th' => 'Landing Page (1-5 หน้า)', 'price' => 15000],
                        'web_corporate' => ['name' => 'Corporate Website', 'name_th' => 'เว็บไซต์องค์กร', 'price' => 45000],
                        'web_portfolio' => ['name' => 'Portfolio Website', 'name_th' => 'เว็บไซต์ Portfolio', 'price' => 25000],
                        'web_blog' => ['name' => 'Blog/News Website', 'name_th' => 'เว็บบล็อก/ข่าว', 'price' => 35000],
                        'web_multilang' => ['name' => 'Multi-language Website', 'name_th' => 'เว็บไซต์หลายภาษา', 'price' => 60000],
                    ],
                ],
                'ecommerce' => [
                    'name' => 'E-commerce',
                    'name_th' => 'ร้านค้าออนไลน์',
                    'icon' => '🛒',
                    'options' => [
                        'ecom_basic' => ['name' => 'Basic E-commerce', 'name_th' => 'ร้านค้าออนไลน์พื้นฐาน', 'price' => 80000],
                        'ecom_advanced' => ['name' => 'Advanced E-commerce', 'name_th' => 'ร้านค้าออนไลน์ขั้นสูง', 'price' => 150000],
                        'ecom_marketplace' => ['name' => 'Marketplace Platform', 'name_th' => 'ระบบ Marketplace', 'price' => 300000],
                        'ecom_subscription' => ['name' => 'Subscription Commerce', 'name_th' => 'ระบบสมาชิกรายเดือน', 'price' => 120000],
                        'ecom_booking' => ['name' => 'Booking System', 'name_th' => 'ระบบจองบริการ', 'price' => 100000],
                    ],
                ],
                'webapp' => [
                    'name' => 'Web Application',
                    'name_th' => 'เว็บแอปพลิเคชัน',
                    'icon' => '⚡',
                    'options' => [
                        'webapp_spa' => ['name' => 'SPA (Single Page App)', 'name_th' => 'SPA เว็บแอป', 'price' => 100000],
                        'webapp_pwa' => ['name' => 'PWA (Progressive Web App)', 'name_th' => 'PWA ติดตั้งได้', 'price' => 120000],
                        'webapp_dashboard' => ['name' => 'Admin Dashboard', 'name_th' => 'Dashboard ผู้ดูแล', 'price' => 80000],
                        'webapp_crm' => ['name' => 'Web-based CRM', 'name_th' => 'CRM บนเว็บ', 'price' => 200000],
                        'webapp_custom' => ['name' => 'Custom Web App', 'name_th' => 'เว็บแอปตามสั่ง', 'price' => 150000],
                    ],
                ],
                'backend' => [
                    'name' => 'Backend & API',
                    'name_th' => 'Backend & API',
                    'icon' => '🔧',
                    'options' => [
                        'api_rest' => ['name' => 'REST API Development', 'name_th' => 'พัฒนา REST API', 'price' => 60000],
                        'api_graphql' => ['name' => 'GraphQL API', 'name_th' => 'พัฒนา GraphQL API', 'price' => 80000],
                        'api_integration' => ['name' => 'Third-party Integration', 'name_th' => 'เชื่อมต่อ API ภายนอก', 'price' => 40000],
                        'backend_microservice' => ['name' => 'Microservices Architecture', 'name_th' => 'สถาปัตยกรรม Microservices', 'price' => 200000],
                        'backend_serverless' => ['name' => 'Serverless Backend', 'name_th' => 'Backend แบบ Serverless', 'price' => 100000],
                    ],
                ],
            ],
        ],
        'mobile' => [
            'name' => 'Mobile Application',
            'name_th' => 'แอปพลิเคชันมือถือ',
            'icon' => '📱',
            'color' => 'green',
            'base_price' => 80000,
            'categories' => [
                'native' => [
                    'name' => 'Native Development',
                    'name_th' => 'พัฒนาแบบ Native',
                    'icon' => '📲',
                    'options' => [
                        'native_ios' => ['name' => 'iOS Native (Swift)', 'name_th' => 'แอป iOS (Swift)', 'price' => 180000],
                        'native_android' => ['name' => 'Android Native (Kotlin)', 'name_th' => 'แอป Android (Kotlin)', 'price' => 150000],
                        'native_both' => ['name' => 'iOS + Android Native', 'name_th' => 'iOS + Android Native', 'price' => 300000],
                    ],
                ],
                'crossplatform' => [
                    'name' => 'Cross-platform',
                    'name_th' => 'ข้ามแพลตฟอร์ม',
                    'icon' => '🔄',
                    'options' => [
                        'cross_flutter' => ['name' => 'Flutter (iOS+Android)', 'name_th' => 'Flutter (iOS+Android)', 'price' => 200000],
                        'cross_reactnative' => ['name' => 'React Native', 'name_th' => 'React Native (iOS+Android)', 'price' => 180000],
                        'cross_kotlin' => ['name' => 'Kotlin Multiplatform', 'name_th' => 'Kotlin Multiplatform', 'price' => 220000],
                    ],
                ],
                'features' => [
                    'name' => 'App Features',
                    'name_th' => 'ฟีเจอร์แอป',
                    'icon' => '✨',
                    'options' => [
                        'feat_push' => ['name' => 'Push Notifications', 'name_th' => 'ระบบแจ้งเตือน Push', 'price' => 20000],
                        'feat_chat' => ['name' => 'In-app Chat', 'name_th' => 'ระบบแชทในแอป', 'price' => 50000],
                        'feat_payment' => ['name' => 'In-app Payment', 'name_th' => 'ระบบชำระเงินในแอป', 'price' => 40000],
                        'feat_map' => ['name' => 'Maps & Location', 'name_th' => 'แผนที่และ GPS', 'price' => 30000],
                        'feat_camera' => ['name' => 'Camera & AR Features', 'name_th' => 'กล้องและ AR', 'price' => 60000],
                        'feat_offline' => ['name' => 'Offline Mode', 'name_th' => 'โหมดออฟไลน์', 'price' => 35000],
                    ],
                ],
                'services' => [
                    'name' => 'App Services',
                    'name_th' => 'บริการแอป',
                    'icon' => '🛠️',
                    'options' => [
                        'svc_publish' => ['name' => 'App Store Publishing', 'name_th' => 'Publish ขึ้น Store', 'price' => 15000],
                        'svc_maintenance' => ['name' => 'App Maintenance/Year', 'name_th' => 'ดูแลรักษาแอป/ปี', 'price' => 48000],
                        'svc_analytics' => ['name' => 'Analytics Integration', 'name_th' => 'ระบบ Analytics', 'price' => 20000],
                    ],
                ],
            ],
        ],
        'ai' => [
            'name' => 'AI Solutions',
            'name_th' => 'บริการ AI',
            'icon' => '🤖',
            'color' => 'indigo',
            'base_price' => 50000,
            'categories' => [
                'chatbot' => [
                    'name' => 'AI Chatbot',
                    'name_th' => 'Chatbot อัจฉริยะ',
                    'icon' => '💬',
                    'options' => [
                        'chat_basic' => ['name' => 'Basic Chatbot', 'name_th' => 'Chatbot พื้นฐาน', 'price' => 50000],
                        'chat_gpt' => ['name' => 'GPT-powered Chatbot', 'name_th' => 'Chatbot ด้วย GPT', 'price' => 100000],
                        'chat_voice' => ['name' => 'Voice Assistant', 'name_th' => 'ผู้ช่วยเสียง AI', 'price' => 120000],
                        'chat_multi' => ['name' => 'Multi-channel Bot', 'name_th' => 'Bot หลายช่องทาง', 'price' => 150000],
                        'chat_custom' => ['name' => 'Custom AI Agent', 'name_th' => 'AI Agent แบบกำหนดเอง', 'price' => 200000],
                    ],
                ],
                'generative' => [
                    'name' => 'Generative AI',
                    'name_th' => 'Generative AI',
                    'icon' => '🎨',
                    'options' => [
                        'gen_image' => ['name' => 'AI Image Generation', 'name_th' => 'สร้างภาพด้วย AI', 'price' => 80000],
                        'gen_video' => ['name' => 'AI Video Generation', 'name_th' => 'สร้างวิดีโอด้วย AI', 'price' => 150000],
                        'gen_text' => ['name' => 'AI Content Writing', 'name_th' => 'เขียนเนื้อหาด้วย AI', 'price' => 60000],
                        'gen_avatar' => ['name' => 'AI Avatar/Character', 'name_th' => 'สร้าง Avatar ด้วย AI', 'price' => 100000],
                    ],
                ],
                'music_ai' => [
                    'name' => 'AI Music Generation',
                    'name_th' => 'สร้างเพลงด้วย AI',
                    'icon' => '🎵',
                    'options' => [
                        'music_basic' => ['name' => 'AI Background Music', 'name_th' => 'เพลงประกอบ AI (Basic)', 'price' => 50000],
                        'music_custom' => ['name' => 'Custom AI Music Track', 'name_th' => 'สร้างเพลง AI แบบกำหนดเอง', 'price' => 80000],
                        'music_album' => ['name' => 'AI Music Album (10 tracks)', 'name_th' => 'อัลบั้มเพลง AI (10 เพลง)', 'price' => 500000],
                        'music_voice' => ['name' => 'AI Voice Synthesis', 'name_th' => 'สังเคราะห์เสียงร้อง AI', 'price' => 100000],
                        'music_cover' => ['name' => 'AI Music Cover/Remix', 'name_th' => 'ปรับแต่งเพลงด้วย AI', 'price' => 60000],
                        'music_genre' => ['name' => 'Multi-Genre AI Music', 'name_th' => 'เพลง AI หลายแนว', 'price' => 90000],
                        'music_commercial' => ['name' => 'Commercial Music License', 'name_th' => 'ลิขสิทธิ์เพลงเชิงพาณิชย์', 'price' => 150000],
                        'music_compose' => ['name' => 'AI Music Composition System', 'name_th' => 'ระบบแต่งเพลง AI', 'price' => 300000],
                        'music_mastering' => ['name' => 'AI Audio Mastering', 'name_th' => 'มาสเตอร์เสียงด้วย AI', 'price' => 40000],
                        'music_stem' => ['name' => 'AI Stem Separation', 'name_th' => 'แยกแทร็กเพลงด้วย AI', 'price' => 35000],
                    ],
                ],
                'ml' => [
                    'name' => 'Machine Learning',
                    'name_th' => 'Machine Learning',
                    'icon' => '🧠',
                    'options' => [
                        'ml_prediction' => ['name' => 'Predictive Analytics', 'name_th' => 'วิเคราะห์เชิงทำนาย', 'price' => 200000],
                        'ml_classification' => ['name' => 'Classification Model', 'name_th' => 'โมเดลจำแนกประเภท', 'price' => 150000],
                        'ml_nlp' => ['name' => 'NLP/Text Analysis', 'name_th' => 'วิเคราะห์ข้อความ NLP', 'price' => 180000],
                        'ml_vision' => ['name' => 'Computer Vision', 'name_th' => 'Computer Vision', 'price' => 250000],
                        'ml_recommendation' => ['name' => 'Recommendation System', 'name_th' => 'ระบบแนะนำ', 'price' => 180000],
                        'ml_custom' => ['name' => 'Custom ML Model', 'name_th' => 'โมเดล ML แบบกำหนดเอง', 'price' => 300000],
                    ],
                ],
            ],
        ],
        'iot' => [
            'name' => 'IoT Solutions',
            'name_th' => 'โซลูชัน IoT',
            'icon' => '⚡',
            'color' => 'orange',
            'base_price' => 100000,
            'categories' => [
                'smart_home' => [
                    'name' => 'Smart Home',
                    'name_th' => 'บ้านอัจฉริยะ',
                    'icon' => '🏠',
                    'options' => [
                        'home_automation' => ['name' => 'Home Automation System', 'name_th' => 'ระบบอัตโนมัติในบ้าน', 'price' => 150000],
                        'home_security' => ['name' => 'Smart Security System', 'name_th' => 'ระบบรักษาความปลอดภัย', 'price' => 120000],
                        'home_energy' => ['name' => 'Energy Management', 'name_th' => 'ระบบจัดการพลังงาน', 'price' => 100000],
                        'home_lighting' => ['name' => 'Smart Lighting', 'name_th' => 'ระบบไฟอัจฉริยะ', 'price' => 60000],
                    ],
                ],
                'smart_farm' => [
                    'name' => 'Smart Farm',
                    'name_th' => 'ฟาร์มอัจฉริยะ',
                    'icon' => '🌱',
                    'options' => [
                        'farm_monitoring' => ['name' => 'Crop Monitoring System', 'name_th' => 'ระบบติดตามพืช', 'price' => 180000],
                        'farm_irrigation' => ['name' => 'Smart Irrigation', 'name_th' => 'ระบบรดน้ำอัจฉริยะ', 'price' => 150000],
                        'farm_greenhouse' => ['name' => 'Greenhouse Control', 'name_th' => 'ควบคุมโรงเรือน', 'price' => 200000],
                        'farm_livestock' => ['name' => 'Livestock Monitoring', 'name_th' => 'ติดตามปศุสัตว์', 'price' => 180000],
                    ],
                ],
                'industrial' => [
                    'name' => 'Industrial IoT',
                    'name_th' => 'IoT อุตสาหกรรม',
                    'icon' => '🏭',
                    'options' => [
                        'iiot_monitoring' => ['name' => 'Industrial Monitoring', 'name_th' => 'ระบบติดตามโรงงาน', 'price' => 350000],
                        'iiot_predictive' => ['name' => 'Predictive Maintenance', 'name_th' => 'ซ่อมบำรุงเชิงทำนาย', 'price' => 400000],
                        'iiot_asset' => ['name' => 'Asset Tracking', 'name_th' => 'ติดตามทรัพย์สิน', 'price' => 200000],
                        'iiot_quality' => ['name' => 'Quality Control System', 'name_th' => 'ระบบควบคุมคุณภาพ', 'price' => 300000],
                    ],
                ],
                'platform' => [
                    'name' => 'IoT Platform',
                    'name_th' => 'แพลตฟอร์ม IoT',
                    'icon' => '📊',
                    'options' => [
                        'platform_dashboard' => ['name' => 'IoT Dashboard', 'name_th' => 'Dashboard แสดงผล IoT', 'price' => 80000],
                        'platform_cloud' => ['name' => 'Cloud IoT Platform', 'name_th' => 'แพลตฟอร์ม IoT บน Cloud', 'price' => 250000],
                        'platform_edge' => ['name' => 'Edge Computing', 'name_th' => 'Edge Computing', 'price' => 200000],
                        'platform_hardware' => ['name' => 'Custom Hardware Design', 'name_th' => 'ออกแบบฮาร์ดแวร์', 'price' => 150000],
                    ],
                ],
            ],
        ],
        'security' => [
            'name' => 'Network & IT Security',
            'name_th' => 'ระบบเครือข่ายและความปลอดภัย',
            'icon' => '🔒',
            'color' => 'red',
            'base_price' => 50000,
            'categories' => [
                'network' => [
                    'name' => 'Network Setup',
                    'name_th' => 'ระบบเครือข่าย',
                    'icon' => '🌐',
                    'options' => [
                        'net_design' => ['name' => 'Network Design & Setup', 'name_th' => 'ออกแบบและติดตั้งเครือข่าย', 'price' => 100000],
                        'net_wireless' => ['name' => 'Enterprise WiFi', 'name_th' => 'WiFi องค์กร', 'price' => 80000],
                        'net_vpn' => ['name' => 'VPN Setup', 'name_th' => 'ติดตั้ง VPN', 'price' => 40000],
                        'net_sd_wan' => ['name' => 'SD-WAN Solution', 'name_th' => 'ระบบ SD-WAN', 'price' => 200000],
                    ],
                ],
                'security' => [
                    'name' => 'Security Services',
                    'name_th' => 'บริการความปลอดภัย',
                    'icon' => '🛡️',
                    'options' => [
                        'sec_firewall' => ['name' => 'Firewall Configuration', 'name_th' => 'ติดตั้ง Firewall', 'price' => 60000],
                        'sec_waf' => ['name' => 'Web Application Firewall', 'name_th' => 'WAF ป้องกันเว็บ', 'price' => 80000],
                        'sec_siem' => ['name' => 'SIEM Implementation', 'name_th' => 'ระบบ SIEM', 'price' => 250000],
                        'sec_dlp' => ['name' => 'Data Loss Prevention', 'name_th' => 'ป้องกันข้อมูลรั่วไหล', 'price' => 150000],
                    ],
                ],
                'audit' => [
                    'name' => 'Security Audit',
                    'name_th' => 'ตรวจสอบความปลอดภัย',
                    'icon' => '🔍',
                    'options' => [
                        'audit_pentest' => ['name' => 'Penetration Testing', 'name_th' => 'ทดสอบเจาะระบบ', 'price' => 120000],
                        'audit_vuln' => ['name' => 'Vulnerability Assessment', 'name_th' => 'ประเมินช่องโหว่', 'price' => 80000],
                        'audit_code' => ['name' => 'Source Code Review', 'name_th' => 'ตรวจสอบซอร์สโค้ด', 'price' => 100000],
                        'audit_compliance' => ['name' => 'Compliance Audit', 'name_th' => 'ตรวจสอบมาตรฐาน', 'price' => 150000],
                    ],
                ],
                'managed' => [
                    'name' => 'Managed Services',
                    'name_th' => 'บริการดูแลระบบ',
                    'icon' => '👨‍💻',
                    'options' => [
                        'managed_monitoring' => ['name' => '24/7 Monitoring/Year', 'name_th' => 'ดูแลระบบ 24/7/ปี', 'price' => 150000],
                        'managed_soc' => ['name' => 'SOC as a Service/Year', 'name_th' => 'SOC as a Service/ปี', 'price' => 300000],
                        'managed_incident' => ['name' => 'Incident Response', 'name_th' => 'รับมือเหตุการณ์', 'price' => 100000],
                        'managed_backup' => ['name' => 'Backup & DR Setup', 'name_th' => 'ระบบสำรองข้อมูล', 'price' => 80000],
                    ],
                ],
            ],
        ],
        'software' => [
            'name' => 'Custom Software',
            'name_th' => 'ซอฟต์แวร์เฉพาะทาง',
            'icon' => '💻',
            'color' => 'teal',
            'base_price' => 150000,
            'categories' => [
                'erp' => [
                    'name' => 'ERP Systems',
                    'name_th' => 'ระบบ ERP',
                    'icon' => '🏢',
                    'options' => [
                        'erp_basic' => ['name' => 'Basic ERP', 'name_th' => 'ERP พื้นฐาน', 'price' => 500000],
                        'erp_enterprise' => ['name' => 'Enterprise ERP', 'name_th' => 'ERP องค์กรใหญ่', 'price' => 1500000],
                        'erp_module' => ['name' => 'ERP Module Add-on', 'name_th' => 'เพิ่ม Module ERP', 'price' => 200000],
                        'erp_integration' => ['name' => 'ERP Integration', 'name_th' => 'เชื่อมต่อ ERP เดิม', 'price' => 300000],
                    ],
                ],
                'crm' => [
                    'name' => 'CRM Systems',
                    'name_th' => 'ระบบ CRM',
                    'icon' => '👥',
                    'options' => [
                        'crm_sales' => ['name' => 'Sales CRM', 'name_th' => 'CRM การขาย', 'price' => 250000],
                        'crm_service' => ['name' => 'Service CRM', 'name_th' => 'CRM บริการลูกค้า', 'price' => 200000],
                        'crm_marketing' => ['name' => 'Marketing CRM', 'name_th' => 'CRM การตลาด', 'price' => 220000],
                        'crm_custom' => ['name' => 'Custom CRM', 'name_th' => 'CRM แบบกำหนดเอง', 'price' => 350000],
                    ],
                ],
                'business' => [
                    'name' => 'Business Software',
                    'name_th' => 'ซอฟต์แวร์ธุรกิจ',
                    'icon' => '📊',
                    'options' => [
                        'biz_pos' => ['name' => 'POS System', 'name_th' => 'ระบบ POS', 'price' => 100000],
                        'biz_inventory' => ['name' => 'Inventory Management', 'name_th' => 'ระบบคลังสินค้า', 'price' => 150000],
                        'biz_hr' => ['name' => 'HR Management', 'name_th' => 'ระบบ HR', 'price' => 200000],
                        'biz_accounting' => ['name' => 'Accounting System', 'name_th' => 'ระบบบัญชี', 'price' => 250000],
                        'biz_project' => ['name' => 'Project Management', 'name_th' => 'ระบบจัดการโปรเจค', 'price' => 180000],
                    ],
                ],
            ],
        ],
        'flutter' => [
            'name' => 'Flutter & Training',
            'name_th' => 'Flutter และอบรม',
            'icon' => '📲',
            'color' => 'cyan',
            'base_price' => 30000,
            'categories' => [
                'training' => [
                    'name' => 'Flutter Training',
                    'name_th' => 'อบรม Flutter',
                    'icon' => '📚',
                    'options' => [
                        'train_basic' => ['name' => 'Flutter Basic (2 days)', 'name_th' => 'Flutter เบื้องต้น (2 วัน)', 'price' => 15000],
                        'train_intermediate' => ['name' => 'Flutter Intermediate (3 days)', 'name_th' => 'Flutter ระดับกลาง (3 วัน)', 'price' => 25000],
                        'train_advanced' => ['name' => 'Flutter Advanced (3 days)', 'name_th' => 'Flutter ขั้นสูง (3 วัน)', 'price' => 35000],
                        'train_state' => ['name' => 'State Management Workshop', 'name_th' => 'Workshop State Management', 'price' => 20000],
                    ],
                ],
                'consulting' => [
                    'name' => 'Consulting',
                    'name_th' => 'ที่ปรึกษา',
                    'icon' => '💼',
                    'options' => [
                        'consult_hour' => ['name' => 'Consulting (per hour)', 'name_th' => 'ที่ปรึกษา (รายชั่วโมง)', 'price' => 3000],
                        'consult_day' => ['name' => 'Consulting (per day)', 'name_th' => 'ที่ปรึกษา (รายวัน)', 'price' => 20000],
                        'consult_month' => ['name' => 'Monthly Mentoring', 'name_th' => 'Mentor รายเดือน', 'price' => 50000],
                        'consult_code' => ['name' => 'Code Review Session', 'name_th' => 'ตรวจสอบโค้ด', 'price' => 10000],
                    ],
                ],
                'workshop' => [
                    'name' => 'Workshop',
                    'name_th' => 'Workshop',
                    'icon' => '🎓',
                    'options' => [
                        'ws_team' => ['name' => 'Team Workshop (5-10 pax)', 'name_th' => 'Workshop ทีม (5-10 คน)', 'price' => 80000],
                        'ws_corporate' => ['name' => 'Corporate Training', 'name_th' => 'อบรมองค์กร', 'price' => 150000],
                        'ws_bootcamp' => ['name' => '1-Week Bootcamp', 'name_th' => 'Bootcamp 1 สัปดาห์', 'price' => 100000],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Additional options available for all services
     */
    protected array $additionalOptions = [
        'support' => [
            'name' => 'Support & Maintenance',
            'name_th' => 'ซัพพอร์ตและดูแลรักษา',
            'icon' => '🛠️',
            'options' => [
                'priority' => ['name' => 'Priority Support', 'name_th' => 'ซัพพอร์ตเร่งด่วน 24/7', 'price' => 30000, 'icon' => '⚡'],
                'warranty_1y' => ['name' => '1 Year Warranty', 'name_th' => 'รับประกัน 1 ปี', 'price' => 30000, 'icon' => '🛡️'],
                'warranty_2y' => ['name' => '2 Year Warranty', 'name_th' => 'รับประกัน 2 ปี', 'price' => 50000, 'icon' => '🛡️'],
                'maintenance' => ['name' => 'Annual Maintenance', 'name_th' => 'ดูแลระบบรายปี', 'price' => 60000, 'icon' => '🔧'],
            ],
        ],
        'delivery' => [
            'name' => 'Delivery & Docs',
            'name_th' => 'ส่งมอบและเอกสาร',
            'icon' => '📦',
            'options' => [
                'source_code' => ['name' => 'Full Source Code', 'name_th' => 'Source Code ทั้งหมด', 'price' => 50000, 'icon' => '💾'],
                'documentation' => ['name' => 'Technical Documentation', 'name_th' => 'เอกสารเทคนิคครบถ้วน', 'price' => 25000, 'icon' => '📝'],
                'training' => ['name' => 'User Training (8 hrs)', 'name_th' => 'อบรมการใช้งาน 8 ชม.', 'price' => 20000, 'icon' => '👨‍🏫'],
                'video_guide' => ['name' => 'Video User Guide', 'name_th' => 'วิดีโอสอนการใช้งาน', 'price' => 15000, 'icon' => '🎬'],
            ],
        ],
        'hosting' => [
            'name' => 'Hosting & Domain',
            'name_th' => 'Hosting และโดเมน',
            'icon' => '☁️',
            'options' => [
                'hosting_basic' => ['name' => 'Cloud Hosting Basic/Year', 'name_th' => 'Cloud Hosting พื้นฐาน/ปี', 'price' => 12000, 'icon' => '🌐'],
                'hosting_pro' => ['name' => 'Cloud Hosting Pro/Year', 'name_th' => 'Cloud Hosting Pro/ปี', 'price' => 36000, 'icon' => '🚀'],
                'ssl' => ['name' => 'SSL Certificate', 'name_th' => 'ใบรับรอง SSL', 'price' => 3000, 'icon' => '🔐'],
                'domain' => ['name' => 'Domain Registration', 'name_th' => 'จดโดเมน 1 ปี', 'price' => 500, 'icon' => '🌍'],
                'email' => ['name' => 'Business Email/Year', 'name_th' => 'อีเมลธุรกิจ/ปี', 'price' => 6000, 'icon' => '📧'],
            ],
        ],
    ];

    /**
     * Show the quotation form
     */
    public function index()
    {
        $categories = QuotationCategory::with('activeOptions')
            ->active()
            ->ordered()
            ->get();

        // Format data for view
        $formattedCategories = [];

        // If database has data, use it
        if ($categories->count() > 0) {
            foreach ($categories as $category) {
                $options = [];
                foreach ($category->activeOptions as $option) {
                    $options[$option->key] = [
                        'name' => $option->name,
                        'name_th' => $option->name_th ?? $option->name,
                        'price' => (float) $option->price,
                        'description' => $option->description,
                        'description_th' => $option->description_th,
                    ];
                }

                // Wrap options in a 'categories' structure to match the expected format
                $formattedCategories[$category->key] = [
                    'name' => $category->name,
                    'name_th' => $category->name_th ?? $category->name,
                    'icon' => $category->icon,
                    'description' => $category->description,
                    'description_th' => $category->description_th,
                    'categories' => [
                        'main' => [
                            'name' => $category->name,
                            'name_th' => $category->name_th ?? $category->name,
                            'icon' => $category->icon,
                            'options' => $options,
                        ],
                    ],
                ];
            }
        } else {
            // Fallback to hardcoded data if database is empty
            $formattedCategories = $this->servicePackages;
        }

        return view('support.index', [
            'services' => $formattedCategories,
            'additionalOptions' => $this->additionalOptions ?? [],
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

        $filename = 'XMAN-Quotation-'.$quotation['quote_number'].'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Submit order (payment or quotation request)
     */
    public function submitOrder(Request $request)
    {
        $validated = $this->validateRequest($request);
        $validated['action_type'] = $request->input('action_type', 'quotation');
        $validated['payment_method'] = $request->input('payment_method');

        $quotationData = $this->calculateQuotation($validated);

        // Save quotation to database
        $quotation = Quotation::create([
            'quote_number' => $quotationData['quote_number'],
            'user_id' => auth()->id(),
            'customer_name' => $quotationData['customer']['name'],
            'customer_company' => $quotationData['customer']['company'],
            'customer_email' => $quotationData['customer']['email'],
            'customer_phone' => $quotationData['customer']['phone'],
            'customer_address' => $quotationData['customer']['address'],
            'service_type' => $validated['service_type'],
            'service_name' => $quotationData['service']['name_th'],
            'service_options' => $quotationData['items'],
            'additional_options' => $validated['additional_options'] ?? [],
            'project_description' => $quotationData['project_description'],
            'timeline' => $quotationData['timeline'],
            'subtotal' => $quotationData['subtotal'],
            'discount' => $quotationData['discount'],
            'discount_percent' => $quotationData['discount_percent'],
            'rush_fee' => $quotationData['rush_fee'],
            'vat' => $quotationData['vat'],
            'grand_total' => $quotationData['grand_total'],
            'status' => 'draft',
            'action_type' => $validated['action_type'],
            'payment_method' => $validated['payment_method'],
            'valid_until' => now()->addDays(30),
        ]);

        // Send Line notification
        $lineNotify = new LineNotifyService;

        if ($validated['action_type'] === 'order') {
            $quotation->markAsSent();
            $lineNotify->notifyNewOrder($quotationData, $validated['payment_method']);

            return response()->json([
                'success' => true,
                'message' => 'ได้รับคำสั่งซื้อแล้ว ทีมงานจะติดต่อกลับภายใน 24 ชั่วโมง',
                'quote_number' => $quotation->quote_number,
                'action' => 'order',
            ]);
        } else {
            $quotation->markAsSent();
            $lineNotify->notifyNewQuotation($quotationData);

            return response()->json([
                'success' => true,
                'message' => 'ส่งคำขอใบเสนอราคาแล้ว ทีมงานจะติดต่อกลับภายใน 24 ชั่วโมง',
                'quote_number' => $quotation->quote_number,
                'action' => 'quotation',
            ]);
        }
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
            'service_type' => 'required|string|in:'.implode(',', array_keys($this->servicePackages)),
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

        // Flatten all options from all categories
        $allOptions = [];
        foreach ($service['categories'] as $category) {
            foreach ($category['options'] as $key => $option) {
                $allOptions[$key] = $option;
            }
        }

        // Add selected service options
        foreach ($data['service_options'] as $optionKey) {
            if (isset($allOptions[$optionKey])) {
                $option = $allOptions[$optionKey];
                $items[] = [
                    'name' => $option['name'],
                    'name_th' => $option['name_th'],
                    'price' => $option['price'],
                    'type' => 'service',
                ];
                $subtotal += $option['price'];
            }
        }

        // Flatten all additional options
        $allAdditionalOptions = [];
        foreach ($this->additionalOptions as $category) {
            foreach ($category['options'] as $key => $option) {
                $allAdditionalOptions[$key] = $option;
            }
        }

        // Add additional options
        if (! empty($data['additional_options'])) {
            foreach ($data['additional_options'] as $optionKey) {
                if (isset($allAdditionalOptions[$optionKey])) {
                    $option = $allAdditionalOptions[$optionKey];
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
            'quote_number' => 'QT-'.date('Ymd').'-'.strtoupper(Str::random(4)),
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
        $categories = QuotationCategory::with('activeOptions')
            ->active()
            ->ordered()
            ->get();

        // Format data for frontend
        $formattedCategories = [];
        foreach ($categories as $category) {
            $options = [];
            foreach ($category->activeOptions as $option) {
                $options[$option->key] = [
                    'name' => $option->name,
                    'name_th' => $option->name_th ?? $option->name,
                    'price' => (float) $option->price,
                    'description' => $option->description,
                    'description_th' => $option->description_th,
                ];
            }

            $formattedCategories[$category->key] = [
                'name' => $category->name,
                'name_th' => $category->name_th ?? $category->name,
                'icon' => $category->icon,
                'description' => $category->description,
                'description_th' => $category->description_th,
                'options' => $options,
            ];
        }

        return response()->json([
            'services' => $formattedCategories,
            'additional_options' => $this->additionalOptions ?? [],
        ]);
    }

    /**
     * Show service detail page
     */
    public function serviceDetail($categoryKey, $optionKey)
    {
        // Find category
        $category = QuotationCategory::where('key', $categoryKey)
            ->where('is_active', true)
            ->first();

        if (! $category) {
            abort(404, 'Service category not found');
        }

        // Find option
        $option = QuotationOption::where('quotation_category_id', $category->id)
            ->where('key', $optionKey)
            ->where('is_active', true)
            ->first();

        if (! $option) {
            abort(404, 'Service not found');
        }

        // Get related services in the same category
        $relatedServices = QuotationOption::where('quotation_category_id', $category->id)
            ->where('id', '!=', $option->id)
            ->where('is_active', true)
            ->ordered()
            ->limit(3)
            ->get();

        return view('services.detail', compact('category', 'option', 'relatedServices'));
    }
}
