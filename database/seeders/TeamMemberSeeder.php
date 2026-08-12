<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

/**
 * The two founders.
 *
 * Copy here is written from facts supplied by the owner — it deliberately does
 * not invent awards, employers, or dates, because this page exists to be
 * checked by prospective clients. `name` drives the portrait lookup in
 * TeamMember::getPhotoUrlAttribute() (slug of name -> artwork/team/<slug>.webp),
 * so renaming a member means renaming the file too.
 */
class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'name' => 'Entony',
                'name_th' => 'อาจารย์ Entony (บุญณราช อุปเสน)',
                'position' => 'Founder · IT, AI & Blockchain',
                'position_th' => 'ผู้ก่อตั้ง · IT, AI และ Blockchain',
                'bio' => 'Founder of XMAN Studio. Works hands-on across machine learning, smart contracts, decentralised systems and data science — and has helped pioneer Blockchain projects in Thailand while bringing AI to bear on real business problems. Also teaches and mentors the next generation of Thai developers.',
                'bio_th' => 'ผู้ก่อตั้ง XMAN Studio ลงมือทำเองตั้งแต่ Machine Learning, Smart Contract, ระบบกระจายศูนย์ ไปจนถึง Data Science เป็นหนึ่งในผู้บุกเบิกโปรเจค Blockchain ของไทย และนำ AI มาใช้แก้โจทย์ธุรกิจจริง ควบคู่กับบทบาทผู้สอนและพี่เลี้ยงให้นักพัฒนารุ่นใหม่',
                'department' => 'executive',
                'skills' => 'AI & Machine Learning, Blockchain, Smart Contracts, Decentralized Systems, Data Science, Software Architecture',
                'is_leader' => true,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Koranipa',
                'name_th' => 'กรณิภา ศิลปโกมล',
                'position' => 'Online Marketing Lead',
                'position_th' => 'หัวหน้าฝ่ายการตลาดออนไลน์',
                'bio' => 'Started from nothing, back when the Thai internet was still finding its feet. No university degree — she taught herself, and went on to reach number one across several direct-sales companies and build revenue at the hundred-million level. Today she leads online marketing at XMAN Studio, and created the "Mae Mor Chantra" brand, which passed 50,000 followers within three months.',
                'bio_th' => 'เริ่มจากศูนย์ในยุคที่อินเทอร์เน็ตไทยยังไม่เฟื่องฟู ไม่ได้จบปริญญาตรี แต่เรียนรู้เพิ่มด้วยตัวเองมาตลอด จนขึ้นเป็นอันดับหนึ่งของค่ายขายตรงมาแล้วหลายค่าย และเคยสร้างรายได้ระดับร้อยล้าน ปัจจุบันดูแลการตลาดออนไลน์ให้ XMAN Studio และเป็นผู้สร้างแบรนด์ "แม่หมอจันทรา" ที่มีผู้ติดตามทะลุ 50,000 คนภายในเวลาเพียง 3 เดือน',
                'department' => 'executive',
                'skills' => 'การตลาดออนไลน์, สร้างแบรนด์, Content Strategy, Social Media Growth, Direct Sales, ทีมขาย',
                'is_leader' => true,
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($members as $member) {
            TeamMember::updateOrCreate(['name' => $member['name']], $member);
        }
    }
}
