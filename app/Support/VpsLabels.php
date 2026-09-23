<?php

namespace App\Support;

/**
 * Words for things upstream only names in its own vocabulary: the actions in
 * a machine's history, and the control panel a template installs.
 */
class VpsLabels
{
    /**
     * Upstream action names are internal ("ct_restart", "vps_set_ptr"…) and
     * not documented, so they are matched by the words inside them. Order
     * matters: "restore snapshot" must not read as "create snapshot".
     *
     * @var array<int,array{0:array<int,string>,1:string,2:string}>
     */
    protected const ACTIONS = [
        [['recovery'], 'โหมดกู้ระบบ', 'Recovery mode'],
        [['rescue'], 'โหมดกู้ระบบ', 'Recovery mode'],
        [['snapshot', 'restore'], 'กู้คืนจากสแนปช็อต', 'Snapshot restored'],
        [['snapshot', 'delete'], 'ลบสแนปช็อต', 'Snapshot deleted'],
        [['snapshot'], 'สร้างสแนปช็อต', 'Snapshot taken'],
        [['backup', 'restore'], 'กู้คืนจากแบ็กอัป', 'Backup restored'],
        [['backup'], 'สำรองข้อมูล', 'Backup'],
        [['recreate'], 'ติดตั้งระบบใหม่', 'Operating system reinstalled'],
        [['reinstall'], 'ติดตั้งระบบใหม่', 'Operating system reinstalled'],
        [['panel', 'password'], 'ตั้งรหัสผ่านแผงควบคุม', 'Panel password set'],
        [['password'], 'ตั้งรหัสผ่าน root', 'Root password set'],
        [['hostname'], 'เปลี่ยนชื่อโฮสต์', 'Hostname changed'],
        [['ptr'], 'ตั้ง Reverse DNS', 'Reverse DNS set'],
        [['nameserver'], 'ตั้ง DNS resolver', 'DNS resolvers set'],
        [['firewall'], 'อัปเดตไฟร์วอลล์', 'Firewall updated'],
        [['public_key'], 'เพิ่ม SSH key', 'SSH key added'],
        [['key'], 'เพิ่ม SSH key', 'SSH key added'],
        [['monarx'], 'ตัวสแกนมัลแวร์', 'Malware scanner'],
        [['malware'], 'ตัวสแกนมัลแวร์', 'Malware scanner'],
        [['restart'], 'รีสตาร์ท', 'Restarted'],
        [['reboot'], 'รีสตาร์ท', 'Restarted'],
        [['start'], 'เปิดเครื่อง', 'Started'],
        [['stop'], 'ปิดเครื่อง', 'Stopped'],
        [['shutdown'], 'ปิดเครื่อง', 'Stopped'],
        [['setup'], 'ติดตั้งเครื่องครั้งแรก', 'First setup'],
        [['create'], 'สร้างเครื่อง', 'Created'],
        [['unsuspend'], 'เปิดใช้งานอีกครั้ง', 'Unsuspended'],
        [['suspend'], 'ระงับการใช้งาน', 'Suspended'],
    ];

    /**
     * @return array{th:string,en:string}
     */
    public static function action(string $name): array
    {
        $lower = strtolower($name);

        foreach (self::ACTIONS as [$words, $th, $en]) {
            $all = true;

            foreach ($words as $word) {
                if (! str_contains($lower, $word)) {
                    $all = false;
                    break;
                }
            }

            if ($all) {
                return ['th' => $th, 'en' => $en];
            }
        }

        // Unknown: readable, and never the supplier's name.
        $plain = trim((string) preg_replace(['/^(ct|vps|vm)_/i', '/[_\-]+/', '/hostinger/i'], ['', ' ', ''], $name));

        return ['th' => $plain !== '' ? $plain : 'รายการอื่น', 'en' => $plain !== '' ? ucfirst($plain) : 'Other'];
    }

    /**
     * @return array{th:string,en:string,classes:string}
     */
    public static function actionState(string $state): array
    {
        return match (strtolower($state)) {
            'success' => ['th' => 'สำเร็จ', 'en' => 'Done', 'classes' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300'],
            'error' => ['th' => 'ไม่สำเร็จ', 'en' => 'Failed', 'classes' => 'bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300'],
            'delayed' => ['th' => 'รอคิว', 'en' => 'Queued', 'classes' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300'],
            default => ['th' => 'กำลังทำ', 'en' => 'In progress', 'classes' => 'bg-sky-100 dark:bg-sky-500/20 text-sky-800 dark:text-sky-300'],
        };
    }

    /**
     * The control panel a template installs, and where its login page is.
     * Null for a plain operating system.
     *
     * @return array{name:string,url:?string}|null
     */
    public static function panel(?string $templateName, ?string $ip): ?array
    {
        $lower = strtolower((string) $templateName);

        $known = [
            'cloudpanel' => ['CloudPanel', 'https://%s:8443'],
            'cyberpanel' => ['CyberPanel', 'https://%s:8090'],
            'hestiacp' => ['HestiaCP', 'https://%s:8083'],
            'aapanel' => ['aaPanel', null],
            'webmin' => ['Webmin', 'https://%s:10000'],
            'virtualmin' => ['Virtualmin', 'https://%s:10000'],
            'cpanel' => ['cPanel / WHM', 'https://%s:2087'],
            'plesk' => ['Plesk', 'https://%s:8443'],
            'directadmin' => ['DirectAdmin', 'https://%s:2222'],
            'coolify' => ['Coolify', 'http://%s:8000'],
            'dokploy' => ['Dokploy', 'http://%s:3000'],
            'easypanel' => ['Easypanel', 'http://%s:3000'],
            'webuzo' => ['Webuzo', 'https://%s:2005'],
            'tinycp' => ['TinyCP', null],
            'fastpanel' => ['FASTPANEL', 'https://%s:8888'],
            'panel' => ['Control panel', null],
        ];

        foreach ($known as $needle => [$name, $url]) {
            if (str_contains($lower, $needle)) {
                return [
                    'name' => $name,
                    'url' => $url && $ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? sprintf($url, $ip) : null,
                ];
            }
        }

        return null;
    }
}
