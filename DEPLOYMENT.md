# 🚀 XMAN Studio - Deployment Guide

คู่มือการติดตั้งและ Deploy XMAN Studio อย่างครบถ้วน

## 📋 สารบัญ

- [ติดตั้งครั้งแรก](#ติดตั้งครั้งแรก)
- [การ Deploy](#การ-deploy)
- [Scripts ที่มีให้ใช้](#scripts-ที่มีให้ใช้)
- [การแก้ปัญหา](#การแก้ปัญหา)
- [Best Practices](#best-practices)

---

## ติดตั้งครั้งแรก

### 1. Installation Wizard (แนะนำ)

สำหรับการติดตั้งครั้งแรกแบบ Interactive:

```bash
./install.sh
```

**คุณสมบัติ:**
- ✅ ตรวจสอบ System Requirements อัตโนมัติ
- ✅ Wizard แบบ Step-by-Step
- ✅ ตั้งค่า Database, Mail, และอื่นๆ
- ✅ Run Migrations และ Seeders
- ✅ Build Frontend Assets
- ⏱ เวลาติดตั้ง: ~10-15 นาที

### 2. Quick Install (รวดเร็ว)

สำหรับ Development และ Testing:

```bash
./quick-install.sh
```

**คุณสมบัติ:**
- ⚡ ใช้ค่า Default ทั้งหมด
- ⚡ SQLite Database
- ⚡ ติดตั้ง Demo Data
- ⚡ ไม่ต้อง Input อะไร
- ⏱ เวลาติดตั้ง: ~5 นาที

### 3. Manual Installation

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Edit .env file
nano .env

# 3. Install dependencies
composer install
npm install

# 4. Generate key
php artisan key:generate

# 5. Create database (MySQL)
mysql -u root -p
CREATE DATABASE xmanstudio;
exit

# 6. Run migrations
php artisan migrate --seed

# 7. Build assets
npm run build

# 8. Fix permissions
chmod -R 775 storage bootstrap/cache
php artisan storage:link

# 9. Start server
php artisan serve
```

---

## การ Deploy

### Production Deployment

```bash
./deploy.sh
```

**ขั้นตอนที่ Script จะทำ:**

1. ✓ ตรวจสอบ Environment
2. ✓ เปิด Maintenance Mode
3. ✓ สำรอง Database, .env, และ Storage
4. ✓ Pull Code จาก Git
5. ✓ อัพเดท Dependencies (Composer & NPM)
6. ✓ Run Database Migrations
7. ✓ Build Frontend Assets
8. ✓ Clear & Optimize Cache
9. ✓ แก้ไข File Permissions
10. ✓ Restart Queue Workers
11. ✓ ปิด Maintenance Mode
12. ✓ Health Check

### Deploy with Options

```bash
# Deploy specific branch
./deploy.sh main
./deploy.sh feature/new-feature

# Skip backup (not recommended)
./deploy.sh --skip-backup

# Deploy specific branch without backup
./deploy.sh main --skip-backup
```

### Staging Deployment

```bash
# Set environment to staging in .env
APP_ENV=staging

# Then deploy
./deploy.sh staging-branch
```

---

## Scripts ที่มีให้ใช้

### 1. `install.sh` - Installation Wizard

```bash
./install.sh
```

Interactive wizard สำหรับติดตั้งครั้งแรก

### 2. `quick-install.sh` - Quick Installation

```bash
./quick-install.sh
```

ติดตั้งแบบเร็วด้วยค่า Default

### 3. `deploy.sh` - Automated Deployment

```bash
./deploy.sh [branch] [--skip-backup]
```

Deploy อัตโนมัติพร้อม backup และ optimization

### 4. `clear-cache.sh` - Clear All Caches

```bash
./clear-cache.sh
```

ล้าง Cache ทั้งหมด (config, route, view, application)

### 5. `fix-permissions.sh` - Fix File Permissions

```bash
./fix-permissions.sh [user]
```

แก้ไข Permissions ของ storage และ cache

### 6. `run-migrations.sh` - Migration Management

```bash
./run-migrations.sh
```

จัดการ Database Migrations แบบ Interactive

**ตัวเลือก:**
- Run migrations
- Run migrations with seed
- Rollback last migration
- Reset all migrations
- Fresh migration
- Fresh migration with seed

### 7. `rollback.sh` - Deployment Rollback

```bash
./rollback.sh
```

Rollback กลับไปยัง Backup ล่าสุด

---

## การแก้ปัญหา

### ❌ Permission Denied

```bash
chmod +x install.sh deploy.sh quick-install.sh
chmod +x clear-cache.sh fix-permissions.sh run-migrations.sh rollback.sh
```

### ❌ Database Connection Error

```bash
# ตรวจสอบ .env
cat .env | grep DB_

# Test database connection
php artisan db:show
```

### ❌ 500 Error After Deployment

```bash
# Clear all caches
./clear-cache.sh

# Fix permissions
./fix-permissions.sh

# Check logs
tail -f storage/logs/laravel.log
```

### ❌ Assets Not Loading

```bash
# Rebuild assets
npm run build

# Create symlink
php artisan storage:link

# Clear cache
./clear-cache.sh
```

### ❌ Queue Jobs Not Running

```bash
# Restart queue workers
php artisan queue:restart

# Check queue status
php artisan queue:work --once
```

### 🔄 Rollback Failed Deployment

```bash
./rollback.sh
```

---

## Best Practices

### 1. ก่อน Deploy

- [ ] Test ใน Local Environment
- [ ] Test ใน Staging Environment
- [ ] Backup Database ล่าสุด
- [ ] แจ้งทีมงานก่อน Deploy
- [ ] เตรียม Rollback Plan

### 2. การ Deploy

```bash
# ✅ Good - Deploy ในเวลาที่มี Traffic น้อย
./deploy.sh main

# ⚠️ Caution - Production deployment
APP_ENV=production ./deploy.sh main

# ❌ Bad - Skip backup in production
./deploy.sh main --skip-backup  # Don't do this!
```

### 3. หลัง Deploy

- [ ] ตรวจสอบ Application ใช้งานได้
- [ ] ตรวจสอบ Error Logs
- [ ] Test ฟีเจอร์หลัก
- [ ] ตรวจสอบ Queue Jobs
- [ ] แจ้งทีมงานว่า Deploy เสร็จแล้ว

### 4. การสำรอง Backup

```bash
# Backups จะถูกเก็บที่
ls -lh backups/

# เก็บ Backup อย่างน้อย 7 วัน
# ลบ Backup เก่าอัตโนมัติ
find backups/ -type f -mtime +7 -delete
```

### 5. Security Checklist

- [ ] `APP_DEBUG=false` ใน Production
- [ ] `APP_ENV=production`
- [ ] ใช้ HTTPS
- [ ] กำหนด Strong DB Password
- [ ] เก็บ `.env` ไว้นอก Public Directory
- [ ] อัพเดท Dependencies เป็นประจำ
- [ ] ใช้ Rate Limiting
- [ ] เปิด CSRF Protection

---

## Production Server Setup

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name xmanstudio.com;
    root /var/www/xmanstudio/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName xmanstudio.com
    DocumentRoot /var/www/xmanstudio/public

    <Directory /var/www/xmanstudio/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/xmanstudio-error.log
    CustomLog ${APACHE_LOG_DIR}/xmanstudio-access.log combined
</VirtualHost>
```

### Supervisor Configuration (Queue Worker)

```ini
[program:xmanstudio-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/xmanstudio/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/xmanstudio/storage/logs/worker.log
stopwaitsecs=3600
```

### Cron Jobs

```bash
# Add to crontab
crontab -e

# Laravel Scheduler
* * * * * cd /var/www/xmanstudio && php artisan schedule:run >> /dev/null 2>&1
```

**On DirectAdmin** (Advanced Features → Cron Jobs) the entry must be every minute too:
Minute `*`, Hour `*`, Day of Month `*`, Month `*`, Day of Week `*`, command
`cd /path/to/xmanstudio && php artisan schedule:run >> /dev/null 2>&1`.
Do not use `*/5` — the scheduler only runs what is due at the minute it is called, so a
five-minute cron silently turns every "every minute" task into "every five minutes" and skips
tasks pinned to a minute it never lands on.

GPUxMINE depends on two of those tasks (`routes/console.php`):

| Task | Schedule | What stops if it does not run |
|------|----------|-------------------------------|
| `gpuxmine:sync-nodes` | every minute (`* * * * *`) | Machines never reach aixman, suspensions and removals are not passed on, stuck retirements are not retried |
| `gpuxmine:settle-earnings` | hourly (`0 * * * *`) | Earnings stay `pending` forever and never reach the owners' XMAN wallets |

Check it after every deploy, and whenever someone says their machine gets no work or their
earnings are not arriving:

```bash
php artisan gpuxmine:doctor          # exit 1 when something is broken
php artisan gpuxmine:doctor --strict # also exit 1 on warnings
```

It checks the relay and aixman settings (`GPUXMINE_RELAY_URL`, `GPUXMINE_RELAY_ADMIN_KEY`,
`AIXMAN_API_BASE`, `AIXMAN_WEBHOOK_SECRET` — which must equal aixman's `XMAN_WEBHOOK_SECRET`),
that the relay answers with the admin key, that aixman accepts the secret (without writing
anything there), that the migrations have run, that both tasks are scheduled and that the cron
really ran them recently, and whether any earnings are stuck past the hold or waiting for an
admin at `/admin/gpuxmine/earnings`. It never prints a secret.

**GPUxMINE deploy order — aixman first (required), then this app, then the relay token split.**

1. Deploy aixman's `feat/sharing-complete` first. This app sends aixman its machines every
   ten minutes even when nothing changed, and whenever a machine's `accepting` flag flips — that
   is what brings back a machine aixman dropped. The aixman on `main` rewrites a machine that is
   rendering to `warming` every time it hears about it, and its reaper then kills warming workers
   older than an hour, so it must not receive those pushes. This app tells the two apart by the
   `worker.lastError` key in aixman's reply (only the new one sends it) and holds back the timed
   and `accepting` pushes until it sees it, but that is a safety net: the intended order is aixman
   first.
2. Deploy this app and run the migrations.
3. The relay (GpuXmine) can go before or after. Until it is upgraded: suspending a machine cannot
   cut its relay connection (aixman is told the machine is offline and suspended, so no work is
   sent), and a removed or banned machine's worker cannot be deleted at the relay — those rows
   are marked `awaiting-relay` and `gpuxmine:sync-nodes` deletes them once the relay has the
   command. `php artisan gpuxmine:doctor` counts them.
4. Last, split the tokens. Set `Relay__IssueTunnelTokens=true` on the relay and restart it, then
   give every machine enrolled before the split its own aixman tunnel token:

   ```bash
   php artisan gpuxmine:rotate-tunnel-tokens --dry-run   # what would change
   php artisan gpuxmine:rotate-tunnel-tokens             # rotate, store, push to aixman
   ```

   Use this command, not `POST /admin/workers/{id}/rotate?only=tunnel` by hand: from the moment
   the relay answers, the old token no longer opens the tunnel, and only this command stores the
   new one and hands it to aixman in the same step. It skips machines rendering for a customer
   (`--include-busy` to override) and is safe to run again until nothing is left.

---

## Performance Optimization

### 1. OPcache

```bash
# Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### 2. Database

```bash
# Add indexes
php artisan db:monitor

# Optimize queries
php artisan telescope:prune  # if using Telescope
```

### 3. Caching

```bash
# Use Redis for cache (recommended)
# In .env:
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 4. CDN

- ใช้ CDN สำหรับ Static Assets
- Optimize Images
- Minify CSS/JS (อัตโนมัติใน production build)

---

## Monitoring

### Application Logs

```bash
# Real-time logs
tail -f storage/logs/laravel.log

# Error logs only
tail -f storage/logs/laravel.log | grep ERROR
```

### Server Monitoring

```bash
# Check disk space
df -h

# Check memory usage
free -m

# Check CPU usage
top

# Check processes
ps aux | grep php
```

---

## Emergency Procedures

### 🚨 Site Down

```bash
# 1. Enable maintenance mode
php artisan down

# 2. Check logs
tail -100 storage/logs/laravel.log

# 3. Rollback if needed
./rollback.sh

# 4. Bring site back up
php artisan up
```

### 🚨 Database Issues

```bash
# 1. Backup current state
./deploy.sh --skip-backup  # Don't run deployment

# 2. Check database
php artisan db:show

# 3. Run repairs if needed
php artisan migrate:status
```

### 🚨 High Server Load

```bash
# 1. Check processes
top
ps aux | grep php

# 2. Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# 3. Clear cache
./clear-cache.sh
```

---

## ติดต่อ & สนับสนุน

- 📞 โทรศัพท์: 080-6038278 (คุณกรณิภา)
- 📧 Email: xjanovax@gmail.com
- 📘 Facebook: https://www.facebook.com/xmanenterprise/
- 💬 Line OA: @xmanstudio
- 📱 Website: https://xmanstudio.com
- 📖 Documentation: README_XMANSTUDIO.md

---

**ถูกสร้างด้วย ❤️ โดย XMAN Studio**
