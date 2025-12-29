# 🚀 วิธีตั้งค่าแบบไม่ต้องใช้ SSH (Alternative Setup)

## สำหรับกรณีที่ SSH ค้าง หรือเชื่อมต่อไม่ได้

---

## วิธีที่ 1: ใช้ File Manager ใน cPanel/DirectAdmin (แนะนำ!)

### ขั้นตอนที่ 1: สร้าง SSH Key บนเครื่องของคุณ
```bash
ssh-keygen -t ed25519 -C "github-actions@xmanstudio" -f ~/.ssh/github-actions-xman -N ""
```

### ขั้นตอนที่ 2: ดู Public Key
```bash
cat ~/.ssh/github-actions-xman.pub
```

**ตัวอย่างผลลัพธ์:**
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI... github-actions@xmanstudio
```

**→ Copy ทั้งหมด**

### ขั้นตอนที่ 3: เข้า Control Panel

**สำหรับ cPanel:**
1. เข้า cPanel (เช่น https://xman4289.com:2083)
2. หา **File Manager**
3. คลิก Go
4. ไปที่โฟลเดอร์ `.ssh` ในโฟลเดอร์ home

**สำหรับ DirectAdmin:**
1. เข้า DirectAdmin
2. คลิก **File Manager**
3. ไปที่โฟลเดอร์ `.ssh`

### ขั้นตอนที่ 4: เพิ่ม Public Key ผ่าน File Manager

**ถ้ามีไฟล์ `authorized_keys` อยู่แล้ว:**
1. คลิกขวาที่ไฟล์ `authorized_keys`
2. เลือก **Edit**
3. ไปบรรทัดสุดท้าย กด Enter
4. Paste public key ที่ copy มา
5. คลิก **Save**

**ถ้ายังไม่มีไฟล์:**
1. คลิก **+ File**
2. ตั้งชื่อไฟล์: `authorized_keys`
3. คลิก **Create**
4. คลิกขวาที่ไฟล์ → **Edit**
5. Paste public key
6. คลิก **Save**

### ขั้นตอนที่ 5: ตั้งค่า Permissions

**ถ้ามี Terminal ใน Control Panel:**
1. เปิด Terminal
2. รันคำสั่ง:
```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

**ถ้าไม่มี Terminal (ใช้ File Manager):**
1. คลิกขวาที่โฟลเดอร์ `.ssh`
2. เลือก **Permissions** หรือ **Change Permissions**
3. ตั้งเป็น `0700` (rwx------)
4. คลิก OK

5. คลิกขวาที่ไฟล์ `authorized_keys`
6. เลือก **Permissions**
7. ตั้งเป็น `0600` (rw-------)
8. คลิก OK

### ขั้นตอนที่ 6: ทดสอบ SSH จากเครื่องของคุณ
```bash
ssh -i ~/.ssh/github-actions-xman admin@xman4289.com
```

---

## วิธีที่ 2: ขอให้ Hosting Support ช่วยเพิ่ม SSH Key

### Template อีเมลติดต่อ Support

```
เรื่อง: ขอความช่วยเหลือเพิ่ม SSH Public Key

สวัสดีครับ,

ผมต้องการเพิ่ม SSH public key เพื่อใช้ในการ deploy อัตโนมัติ
รบกวนช่วยเพิ่ม public key นี้ลงในไฟล์ ~/.ssh/authorized_keys
ของ user: admin

Public Key:
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI... github-actions@xmanstudio

และรบกวนตั้งค่า permissions:
- chmod 700 ~/.ssh
- chmod 600 ~/.ssh/authorized_keys

ขอบคุณครับ
```

**แทนที่ public key ด้วย key ของคุณจากคำสั่ง:**
```bash
cat ~/.ssh/github-actions-xman.pub
```

---

## วิธีที่ 3: ใช้ FTP Deploy แทน SSH Deploy

### เปลี่ยนจาก SSH Deploy เป็น FTP Deploy

ถ้า SSH ใช้งานไม่ได้เลย เราสามารถใช้ FTP deploy แทนได้

### ขั้นตอนที่ 1: หา FTP Credentials

**ใน cPanel:**
1. ไปที่ **FTP Accounts**
2. ดู FTP credentials หรือสร้างใหม่

**จะได้:**
- FTP Host: ftp.xman4289.com (หรือ xman4289.com)
- FTP Username: admin หรือ admin@xman4289.com
- FTP Password: (รหัสผ่าน FTP)
- FTP Port: 21 (หรือ 22 สำหรับ SFTP)

### ขั้นตอนที่ 2: ตั้งค่า GitHub Secrets สำหรับ FTP

**ไปที่:** https://github.com/xjanova/xmanstudio/settings/secrets/actions

**เพิ่ม Secrets:**
```
FTP_SERVER = ftp.xman4289.com
FTP_USERNAME = admin@xman4289.com
FTP_PASSWORD = (รหัสผ่าน FTP)
FTP_REMOTE_DIR = /domains/xman4289.com/public_html
```

### ขั้นตอนที่ 3: ใช้ FTP Deploy Workflow

**สร้างไฟล์:** `.github/workflows/deploy-ftp.yml`

```yaml
name: Deploy via FTP

on:
  workflow_dispatch:
    inputs:
      environment:
        description: 'Environment to deploy to'
        required: true
        default: 'staging'
        type: choice
        options:
          - staging
          - production

jobs:
  deploy:
    name: Deploy to ${{ inputs.environment }}
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install and build assets
        run: |
          npm ci
          npm run build

      - name: Deploy via FTP
        uses: SamKirkland/FTP-Deploy-Action@v4.3.4
        with:
          server: ${{ secrets.FTP_SERVER }}
          username: ${{ secrets.FTP_USERNAME }}
          password: ${{ secrets.FTP_PASSWORD }}
          server-dir: ${{ secrets.FTP_REMOTE_DIR }}/
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/tests/**
            **/.env
            **/storage/logs/**
```

**บันทึกไฟล์และ push:**
```bash
git add .github/workflows/deploy-ftp.yml
git commit -m "feat: add FTP deployment workflow"
git push
```

---

## วิธีที่ 4: Manual Deploy (ไม่ใช้ GitHub Actions)

ถ้าไม่รีบร้อน สามารถ deploy แบบ manual ก่อนได้

### วิธีที่ 4.1: ใช้ Git Pull บน Server

**ถ้ามี Terminal/SSH access บน server:**
```bash
# เข้า server ผ่าน Web Terminal
cd /home/admin/domains/xman4289.com/public_html
git pull origin main
composer install --no-dev
npm install
npm run build
php artisan migrate --force
php artisan config:cache
```

### วิธีที่ 4.2: Upload ไฟล์ผ่าน FTP Client

**ใช้ FileZilla:**
1. ดาวน์โหลด FileZilla: https://filezilla-project.org/
2. เชื่อมต่อด้วย FTP credentials
3. Upload ไฟล์ทั้งหมดจากโปรเจคไปยัง server
4. ไปที่ Web Terminal รัน:
```bash
cd /home/admin/domains/xman4289.com/public_html
composer install --no-dev
npm install
npm run build
php artisan migrate --force
```

---

## 🎯 สรุป: ทางเลือกที่มี

| วิธี | ความยาก | ความเร็ว | เหมาะกับ |
|------|---------|----------|----------|
| 1. File Manager | ⭐ ง่าย | 🚀 เร็ว | ทุกคน |
| 2. Support Ticket | ⭐ ง่าย | 🐌 ช้า | คนไม่ชำนาญ |
| 3. FTP Deploy | ⭐⭐ ปานกลาง | 🚀 เร็ว | มี FTP access |
| 4. Manual Deploy | ⭐⭐⭐ ยาก | 🐌 ช้า | Testing |

---

## 💡 แนะนำ

**ถ้าคุณมี cPanel/DirectAdmin:**
→ ใช้ **วิธีที่ 1: File Manager** (ง่ายที่สุด ใช้เวลา 5 นาที)

**ถ้าไม่มั่นใจ:**
→ ใช้ **วิธีที่ 2: ติดต่อ Support** (ให้ Support ทำให้)

**ถ้า SSH ใช้ไม่ได้เลย:**
→ ใช้ **วิธีที่ 3: FTP Deploy** (ใช้ FTP แทน SSH)

---

## 🆘 ต้องการความช่วยเหลือ?

**บอกผมว่า:**
1. คุณใช้ hosting อะไร? (cPanel? DirectAdmin? VPS?)
2. มี File Manager ใน control panel ไหม?
3. มี FTP access ไหม?

ผมจะช่วยเลือกวิธีที่เหมาะสมที่สุดให้

---

**Last Updated:** 2025-12-29
