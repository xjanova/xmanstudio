# 🚀 คู่มือตั้งค่าด่วน - XMAN Studio

## ✅ ที่ตั้งค่าให้แล้ว (ไม่ต้องทำอะไร)

- ✅ GitHub Actions workflows (CI, Release, Deploy)
- ✅ Git hooks (ตรวจสอบโค้ดก่อน commit)
- ✅ Issue/PR templates
- ✅ Dependabot config
- ✅ Documentation ครบถ้วน

---

## ⚠️ ที่คุณต้องทำเอง (12 นาที)

### ขั้นตอนที่ 1: เปิด GitHub Actions (2 นาที)
```
1. ไปที่: https://github.com/xjanova/xmanstudio/settings/actions
2. เลือก: "Allow all actions and reusable workflows"
3. เลือก: "Read and write permissions"
4. เลือก: "Allow GitHub Actions to create and approve pull requests"
5. คลิก Save
```

### ขั้นตอนที่ 2: ตั้งค่า SSH Key (10 นาที)

**📖 อ่านคู่มือละเอียด:** `.github/SETUP_SSH.md`

**ขั้นตอนย่อ:**
```bash
# 1. สร้าง SSH key
ssh-keygen -t ed25519 -C "github-actions@xmanstudio" -f ~/.ssh/github-actions-xman -N ""

# 2. ดู PUBLIC key
cat ~/.ssh/github-actions-xman.pub
# → Copy ทั้งหมด

# 3. เข้า Server และเพิ่ม public key
ssh admin@xman4289.com
echo "PASTE_PUBLIC_KEY_ตรงนี้" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
exit

# 4. ทดสอบ
ssh -i ~/.ssh/github-actions-xman admin@xman4289.com
exit

# 5. ดู PRIVATE key
cat ~/.ssh/github-actions-xman
# → Copy ทั้งหมด (รวม -----BEGIN และ -----END)

# 6. ไปที่ GitHub Secrets
# https://github.com/xjanova/xmanstudio/settings/secrets/actions

# 7. เพิ่ม Secrets ทั้ง 6 ตัว:
SSH_HOST = xman4289.com
SSH_USER = admin
SSH_PORT = 22
SSH_PRIVATE_KEY = (paste private key)
DEPLOY_PATH = /home/admin/domains/xman4289.com/public_html
APP_URL = https://xman4289.com
```

---

## 🎯 เสร็จแล้ว! ทำอะไรได้บ้าง?

### 1. Push Code = รัน Tests อัตโนมัติ
```bash
git add .
git commit -m "feat: new feature"
git push
# → GitHub Actions จะรัน tests และ style checks
```

### 2. สร้าง Release
```
1. ไปที่: https://github.com/xjanova/xmanstudio/actions
2. คลิก: "Release & Versioning"
3. คลิก: "Run workflow"
4. เลือก: patch (1.0.0 → 1.0.1)
5. คลิก: "Run workflow"
```

### 3. Deploy ไป Production
```
1. ไปที่: https://github.com/xjanova/xmanstudio/actions
2. คลิก: "Deploy to Production"
3. คลิก: "Run workflow"
4. เลือก environment: staging (ลองก่อน)
5. คลิก: "Run workflow"
```

---

## 📚 อ่านเพิ่มเติม

- **คู่มือ SSH แบบละเอียด:** `.github/SETUP_SSH.md`
- **คู่มือ GitHub ทั้งหมด:** `.github/MANUAL_SETUP.md`
- **คู่มือพัฒนา:** `.claude/DEVELOPMENT_GUIDE.md`
- **เริ่มใช้งาน:** `QUICKSTART.md`

---

## 🆘 ปัญหา?

### SSH ไม่เชื่อมต่อ
```bash
# ทดสอบจากเครื่องของคุณ
ssh -i ~/.ssh/github-actions-xman admin@xman4289.com

# ถ้าไม่ได้ = public key ใน server ผิด
# แก้: ทำขั้นตอนที่ 2.3 ใหม่
```

### Workflow ไม่ทำงาน
- ตรวจสอบ Actions enabled (ขั้นตอนที่ 1)
- ตรวจสอบ Secrets ครบ 6 ตัว
- ดู error log ใน Actions tab

---

**Version:** 1.0.0
**Last Updated:** 2025-12-29
