# 🚀 XMAN Studio - Quick Start Guide

## เริ่มต้นอย่างรวดเร็ว

### 📋 สิ่งที่คุณต้องมี

- ✅ PHP 8.2+ (8.3 recommended)
- ✅ Composer
- ✅ Node.js 20+
- ✅ MySQL 8.0+ หรือ SQLite
- ✅ Git
- ✅ GitHub account

---

## ⚡ Setup ใน 5 นาที

### 1. Clone Repository (30 วินาที)

```bash
git clone https://github.com/xjanova/xmanstudio.git
cd xmanstudio
```

### 2. รัน Automated Setup (2 นาที)

```bash
# ตั้งค่าทุกอย่างอัตโนมัติ
./setup-automation.sh
```

**Script นี้จะทำ:**
- ✅ ติดตั้ง Git hooks (pre-commit, pre-push, commit-msg)
- ✅ สร้าง GitHub issue templates
- ✅ สร้าง Pull request template
- ✅ ตั้งค่า Dependabot
- ✅ เตรียม development environment
- ✅ สร้าง version bump scripts

### 3. ติดตั้ง Dependencies (2 นาที)

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Setup environment
cp .env.example .env
php artisan key:generate
```

### 4. Setup Database (30 วินาที)

```bash
# SQLite (ง่ายที่สุด)
touch database/database.sqlite
php artisan migrate

# หรือ MySQL
# แก้ไข .env:
# DB_CONNECTION=mysql
# DB_DATABASE=xmanstudio
# DB_USERNAME=root
# DB_PASSWORD=your_password

php artisan migrate
```

### 5. Start Development Server (10 วินาที)

```bash
# Terminal 1 - Laravel
php artisan serve

# Terminal 2 - Vite (Assets)
npm run dev
```

**เปิดเบราว์เซอร์:** http://localhost:8000

---

## 🔧 GitHub Setup (15 นาที)

### ทำตามนี้ทีละขั้น:

1. **Push code ไป GitHub:**
   ```bash
   git add .
   git commit -m "chore: initial setup"
   git push origin main
   ```

2. **ตั้งค่า GitHub Actions:**
   - อ่านไฟล์: `.github/MANUAL_SETUP.md`
   - ทำตาม checklist ทีละข้อ
   - ใช้เวลา ~15 นาที

3. **ทดสอบว่าใช้งานได้:**
   ```bash
   # Push อะไรก็ได้
   git commit --allow-empty -m "test: trigger CI"
   git push

   # ไปดูที่: https://github.com/[your-username]/xmanstudio/actions
   # ต้องเห็น CI workflow กำลังรัน
   ```

---

## 🎯 Automated Workflows

หลังจาก setup เสร็จ คุณได้:

### 1. Automatic Testing
```bash
# ทุกครั้งที่ push หรือ PR
git push
# → รัน tests อัตโนมัติ
# → ตรวจ code quality
# → Build assets
```

### 2. Easy Versioning
```bash
# สร้าง release version ใหม่
# ไปที่ GitHub Actions → Release & Versioning → Run workflow
# เลือก: patch / minor / major
# → สร้าง release อัตโนมัติ
```

### 3. One-Click Deployment
```bash
# Deploy ไปที่ production
# ไปที่ GitHub Actions → Deploy to Production → Run workflow
# → Deploy อัตโนมัติ
# → Health check
```

---

## 📁 Project Structure

```
xmanstudio/
├── app/
│   ├── Http/Controllers/    # Controllers
│   └── Models/              # Eloquent models
├── database/
│   └── migrations/          # Database migrations
├── resources/
│   ├── views/              # Blade templates
│   ├── css/                # Styles
│   └── js/                 # JavaScript
├── routes/
│   └── web.php             # Web routes
├── .github/
│   ├── workflows/          # GitHub Actions
│   ├── MANUAL_SETUP.md     # คู่มือตั้งค่า GitHub
│   └── WORKFLOWS.md        # คู่มือ workflows
├── .claude/
│   ├── DEVELOPMENT_GUIDE.md  # คู่มือพัฒนา
│   └── CODING_STANDARDS.md   # มาตรฐานโค้ด
├── setup-automation.sh      # Setup script (รันสิ!)
├── deploy.sh               # Deployment script
└── QUICKSTART.md           # ไฟล์นี้
```

---

## 🔑 Important Commands

### Development
```bash
# Start dev server
php artisan serve

# Start vite
npm run dev

# Run tests
php artisan test

# Fix code style
./vendor/bin/pint

# Clear caches
php artisan optimize:clear
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh migrate (ลบทุกอย่าง)
php artisan migrate:fresh

# Rollback
php artisan migrate:rollback
```

### Git & Version
```bash
# Bump version
./scripts/bump-version.sh patch  # 1.0.0 → 1.0.1
./scripts/bump-version.sh minor  # 1.0.0 → 1.1.0
./scripts/bump-version.sh major  # 1.0.0 → 2.0.0

# Commit (จะถูกตรวจโดย hook)
git commit -m "feat(cart): add quantity update"

# Pre-commit hook จะรัน:
# → Laravel Pint (code style)
# → Tests
```

---

## 🆘 Common Issues

### 1. "Permission denied" on scripts
```bash
chmod +x setup-automation.sh
chmod +x deploy.sh
chmod +x fix-route-error.sh
chmod +x scripts/*.sh
```

### 2. "vite: not found"
```bash
npm install
npm run dev
```

### 3. "Class not found"
```bash
composer dump-autoload
php artisan optimize:clear
```

### 4. Migration errors
```bash
php artisan migrate:fresh
# หรือ
./deploy.sh  # มี auto-repair
```

### 5. Route not working
```bash
./fix-route-error.sh
```

---

## 📚 Next Steps

1. **อ่านเอกสาร:**
   - `.github/MANUAL_SETUP.md` - Setup GitHub
   - `.claude/DEVELOPMENT_GUIDE.md` - พัฒนาต่อ
   - `.claude/CODING_STANDARDS.md` - มาตรฐานโค้ด

2. **ทดสอบ workflows:**
   - Push code → ดู CI run
   - สร้าง release
   - Deploy to staging

3. **พัฒนา features:**
   - สร้าง branch ใหม่
   - เขียนโค้ดตาม standards
   - สร้าง PR
   - Merge → Auto deploy

---

## 🎉 You're Ready!

ตอนนี้คุณมี:
- ✅ Development environment พร้อม
- ✅ Git hooks ทำงานอัตโนมัติ
- ✅ CI/CD pipeline พร้อมใช้
- ✅ Automated testing
- ✅ Automated versioning
- ✅ One-click deployment

**Happy Coding! 🚀**

---

## 📞 Need Help?

- **Documentation:** `.claude/` and `.github/` directories
- **Issues:** https://github.com/xjanova/xmanstudio/issues
- **Laravel Docs:** https://laravel.com/docs/11.x

---

**Version:** 1.0.0
**Last Updated:** 2025-12-29
