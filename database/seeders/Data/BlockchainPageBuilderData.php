<?php

namespace Database\Seeders\Data;

class BlockchainPageBuilderData
{
    /**
     * Get Smart Contract Development content
     */
    public static function getSmartContractContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'Smart Contract มืออาชีพ ปลอดภัย และ Audit แล้ว',
                ],
                [
                    'type' => 'text',
                    'content' => 'พัฒนา Smart Contract คุณภาพสูงบน Ethereum, BNB Chain, Polygon และ Blockchain อื่นๆ ด้วยทีมผู้เชี่ยวชาญที่มีประสบการณ์ในโปรเจค DeFi, NFT และ DAO',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องใช้บริการเรา?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Security First',
                    'content' => 'ทุก Contract ผ่านการ Audit และทดสอบความปลอดภัยอย่างละเอียด ป้องกัน Vulnerabilities',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'title' => 'Gas Optimized',
                    'content' => 'เขียน Contract ที่ประหยัด Gas Fee ลดต้นทุน Transaction ให้ผู้ใช้',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'Battle-tested',
                    'content' => 'ใช้ Patterns และ Libraries ที่ผ่านการทดสอบจากโปรเจคชั้นนำ (OpenZeppelin)',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ประเภท Smart Contract',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Token Contracts (ERC-20, ERC-721, ERC-1155)',
                    'description' => 'สร้าง Fungible Tokens, NFTs และ Multi-Token Standards สำหรับทุก Use Case',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'content' => 'DeFi Protocols',
                    'description' => 'DEX, Lending, Staking, Yield Farming และ Liquidity Mining',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#0ea5e9',
                    'content' => 'DAO & Governance',
                    'description' => 'ระบบ Voting, Proposal และ Treasury Management บน Chain',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'NFT Marketplace',
                    'description' => 'Minting, Trading, Auction และ Royalty Distribution',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'lg',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'Blockchain ที่รองรับ',
                ],
                [
                    'type' => 'list',
                    'style' => 'check',
                    'items' => [
                        'Ethereum (Mainnet, Sepolia, Goerli)',
                        'BNB Smart Chain (BSC)',
                        'Polygon (Matic)',
                        'Avalanche',
                        'Arbitrum',
                        'Optimism',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'สิ่งที่รวมในบริการ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'title' => 'Solidity Development',
                    'content' => 'เขียน Contract ด้วย Solidity เวอร์ชันล่าสุด ตาม Best Practices',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Security Audit',
                    'content' => 'ตรวจสอบช่องโหว่ Reentrancy, Integer Overflow, Access Control และอื่นๆ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'Unit Tests 100%',
                    'content' => 'Test Coverage เต็ม 100% รันทุก Scenario และ Edge Cases',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Deployment',
                    'content' => 'Deploy บน Testnet และ Mainnet พร้อม Verification บน Etherscan',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'Documentation',
                    'content' => 'เอกสาร NatSpec, Technical Docs และ Integration Guide',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#8b5cf6',
                    'title' => '3 เดือน Support',
                    'content' => 'Bug Fixes, Upgrades และ Technical Support หลัง Launch',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'กระบวนการพัฒนา Smart Contract',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. Requirements Analysis',
                    'description' => 'วิเคราะห์ Business Logic, Token Economics และ Security Requirements',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. Architecture Design',
                    'description' => 'ออกแบบ Contract Architecture, State Variables และ Function Interfaces',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Development',
                    'description' => 'เขียน Contract ด้วย Solidity, OpenZeppelin และ Hardhat/Foundry',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => '4. Testing',
                    'description' => 'Unit Tests, Integration Tests และ Fuzzing เพื่อ Coverage 100%',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#f59e0b',
                    'content' => '5. Security Audit',
                    'description' => 'ตรวจสอบความปลอดภัยด้วย Static Analysis และ Manual Review',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '6. Deployment',
                    'description' => 'Deploy บน Testnet ทดสอบ และ Deploy บน Mainnet พร้อม Verify',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get NFT Platform content
     */
    public static function getNFTPlatformContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'แพลตฟอร์ม NFT ครบวงจร',
                ],
                [
                    'type' => 'text',
                    'content' => 'สร้างแพลตฟอร์ม NFT ของคุณเอง ตั้งแต่ Minting, Marketplace ไปจนถึง Secondary Sales และ Royalty Distribution เหมาะสำหรับ Art, Gaming, Music และ Collectibles',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องมีแพลตฟอร์ม NFT เอง?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'trophy',
                    'iconColor' => '#f59e0b',
                    'title' => 'ควบคุม Brand Experience',
                    'content' => 'ออกแบบ UX/UI ตาม Brand ไม่ต้องแข่งกับ NFT อื่นบนแพลตฟอร์มใหญ่',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'ไม่ต้องจ่าย Platform Fee',
                    'content' => 'OpenSea หัก 2.5% ทุก Transaction แพลตฟอร์มของคุณ หักเท่าที่คุณต้องการ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'title' => 'เป็นเจ้าของ Community',
                    'content' => 'Build Community รอบ Brand ของคุณโดยตรง ไม่ผ่านตัวกลาง',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ฟีเจอร์หลัก',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => 'Lazy Minting',
                    'description' => 'สร้าง NFT โดยไม่ต้องจ่าย Gas ล่วงหน้า จ่ายเมื่อมีคนซื้อ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Generative Art',
                    'description' => 'สร้าง Collection 10,000 ชิ้นจาก Traits ที่กำหนด พร้อม Rarity',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'Marketplace',
                    'description' => 'ระบบซื้อขาย Fixed Price, Auction, Make Offer และ Bundle Sales',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => 'Royalty System',
                    'description' => 'Creator ได้ Royalty ทุก Secondary Sale แบบอัตโนมัติ',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'lg',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'เหมาะสำหรับ',
                ],
                [
                    'type' => 'list',
                    'style' => 'check',
                    'items' => [
                        'Artists และ Digital Creators',
                        'Game Studios สำหรับ In-Game NFTs',
                        'Musicians และ Record Labels',
                        'Sports Teams และ Entertainment Brands',
                        'Collectible Brands และ IP Owners',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ฟีเจอร์แพลตฟอร์ม NFT',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#6366f1',
                    'title' => 'Minting Engine',
                    'content' => 'รองรับ ERC-721 และ ERC-1155, Lazy Minting, Batch Minting และ Generative Art',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'title' => 'Marketplace',
                    'content' => 'Fixed Price, Auctions, Offers, Collections, Filtering และ Search',
                    'style' => ['bgColor' => '#fef2f2'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'title' => 'User Profiles',
                    'content' => 'Wallet Login, Profile Pages, Collections, Activity History และ Favorites',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Royalty Distribution',
                    'content' => 'On-chain Royalty ทุก Secondary Sale พร้อม Split Payments',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Multi-Chain',
                    'content' => 'รองรับ Ethereum, Polygon, BSC และ Cross-chain Bridge',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Admin Dashboard',
                    'content' => 'จัดการ Collections, Featured Items, Users และ Revenue Reports',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการพัฒนา NFT Platform',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. วางแผนและออกแบบ',
                    'description' => 'วิเคราะห์ Business Model, Target Users และออกแบบ Features',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '2. Smart Contract Development',
                    'description' => 'พัฒนา NFT Contract, Marketplace Contract และ Royalty System',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '3. Frontend Development',
                    'description' => 'สร้าง Web App ด้วย React/Next.js พร้อม Web3 Integration',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#10b981',
                    'content' => '4. Backend & Indexer',
                    'description' => 'สร้าง API และ Blockchain Indexer เพื่อ Query Data ได้รวดเร็ว',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'content' => '5. Testing',
                    'description' => 'ทดสอบ Smart Contracts, Frontend และ E2E บน Testnet',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '6. Launch',
                    'description' => 'Deploy บน Mainnet, Marketing Launch และ Community Building',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Default Blockchain content
     */
    public static function getDefaultBlockchainContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'บริการพัฒนา Blockchain Solutions',
                ],
                [
                    'type' => 'text',
                    'content' => 'พัฒนา Blockchain Applications ครบวงจร ตั้งแต่ Smart Contracts, DApps, DeFi ไปจนถึง NFT Platforms ด้วยทีมผู้เชี่ยวชาญที่มีประสบการณ์',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'title' => 'Smart Contract Development',
                    'content' => 'พัฒนา Smart Contracts ที่ปลอดภัย Audit แล้ว และ Gas Optimized',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'DApp Development',
                    'content' => 'สร้าง Decentralized Applications พร้อม Web3 Integration',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Security Audit',
                    'content' => 'ตรวจสอบความปลอดภัยของ Smart Contracts อย่างละเอียด',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'บริการที่เรามี',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Token Development',
                    'description' => 'ERC-20, ERC-721, ERC-1155 และ Custom Token Standards',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'content' => 'DeFi Protocols',
                    'description' => 'DEX, Lending, Staking, Yield Farming และ Liquidity Pools',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'NFT Solutions',
                    'description' => 'Marketplaces, Minting Engines และ Generative Art',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#f59e0b',
                    'content' => 'DAO Development',
                    'description' => 'Governance Systems, Voting และ Treasury Management',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
