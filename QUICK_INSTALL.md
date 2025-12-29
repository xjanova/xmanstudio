# ⚡ Quick Installation Guide - XMAN Studio

ติดตั้ง XMAN Studio ภายใน 5 นาที!

## 🚀 วิธีที่เร็วที่สุด

```bash
./quick-install.sh
```

จบ! 🎉

---

## 📋 ข้อกำหนดเบื้องต้น

- PHP >= 8.2
- Composer
- Node.js & NPM (Optional)

---

## 🔧 สิ่งที่ Script จะทำ

1. ✅ คัดลอก `.env.example` → `.env`
2. ✅ ตั้งค่า SQLite Database
3. ✅ ติดตั้ง PHP Dependencies
4. ✅ ติดตั้ง Node.js Dependencies
5. ✅ สร้าง Application Key
6. ✅ สร้างฐานข้อมูล
7. ✅ Run Migrations พร้อม Demo Data
8. ✅ แก้ไข Permissions
9. ✅ Build Frontend Assets

---

## ▶️ เริ่มใช้งาน

### 1. รันเซิร์ฟเวอร์

```bash
php artisan serve
```

### 2. เปิดเว็บเบราว์เซอร์

```
http://localhost:8000
```

---

## 🎯 Demo Data

Quick Install จะสร้าง:

- ✅ Categories ตัวอย่าง
- ✅ Products ตัวอย่าง
- ✅ Demo User Account

---

## 🔄 ต้องการติดตั้งแบบ Custom?

ใช้ Installation Wizard:

```bash
./install.sh
```

**คุณสมบัติ:**
- เลือก Database Type (MySQL/SQLite)
- กำหนด Mail Settings
- และอื่นๆ อีกมากมาย

---

## 📚 เอกสารเพิ่มเติม

- [Deployment Guide](DEPLOYMENT.md)
- [Full Documentation](README_XMANSTUDIO.md)

---

## 💡 Tips

### เปลี่ยนจาก SQLite เป็น MySQL

1. Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xmanstudio
DB_USERNAME=root
DB_PASSWORD=your_password
```

2. Create database:
```bash
mysql -u root -p
CREATE DATABASE xmanstudio;
exit
```

3. Re-run migrations:
```bash
php artisan migrate:fresh --seed
```

### สร้างข้อมูลเพิ่ม

```bash
php artisan db:seed
```

### ล้าง Cache

```bash
./clear-cache.sh
```

---

## 🆘 ติดปัญหา?

### Error: Permission Denied

```bash
chmod +x quick-install.sh
```

### Error: Composer not found

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Error: Node not found

แบบที่ 1: ไม่ต้องใช้ NPM (Skip Frontend Build)

แบบที่ 2: ติดตั้ง Node.js
```bash
# Ubuntu/Debian
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

---

## ✨ Next Steps

1. ✅ ปรับแต่ง Settings ใน `.env`
2. ✅ เพิ่มข้อมูลสินค้าและบริการ
3. ✅ Customize Design
4. ✅ Deploy to Production

---

**Happy Coding! 🚀**

*XMAN Studio - IT Solutions & Software Development*
