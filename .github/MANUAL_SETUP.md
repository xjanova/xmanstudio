# ✅ Manual Setup Checklist for GitHub

This guide shows **exactly what you need to do** on GitHub's website.

## ⚠️ คุณต้องทำเอง - Things You MUST Do Manually

### 1. Enable GitHub Actions (Required)

**Location:** `Repository → Settings → Actions → General`

**Steps:**
1. ไปที่ GitHub repository: https://github.com/xjanova/xmanstudio
2. คลิก **Settings** (ขวาบน)
3. คลิก **Actions** (เมนูซ้าย)
4. คลิก **General**
5. เลือก: **Allow all actions and reusable workflows**
6. ใน **Workflow permissions**:
   - เลือก: ✅ **Read and write permissions**
   - เลือก: ✅ **Allow GitHub Actions to create and approve pull requests**
7. คลิก **Save**

---

### 2. Configure Secrets (Required for Deployment)

**Location:** `Settings → Secrets and variables → Actions`

**Steps:**
1. ไปที่ https://github.com/xjanova/xmanstudio/settings/secrets/actions
2. คลิก **New repository secret**
3. เพิ่ม secrets ทีละตัว:

#### SSH Secrets
```
Name: SSH_HOST
Value: xman4289.com
```

```
Name: SSH_USER
Value: admin
```

```
Name: SSH_PORT
Value: 22
```

```
Name: SSH_PRIVATE_KEY
Value: [ใส่ SSH private key - ดูวิธีสร้างด้านล่าง]
```

#### Deployment Secrets
```
Name: DEPLOY_PATH
Value: /home/admin/domains/xman4289.com/public_html
```

```
Name: APP_URL
Value: https://xman4289.com
```

---

### 3. Generate SSH Key for GitHub Actions (Required)

**⚠️ คำแนะนำ:** ถ้า `ssh-copy-id` ค้างหรือมีปัญหา ให้ใช้วิธีก๊อปวางแทน

**📖 คู่มือละเอียดแบบก๊อปวาง:** อ่านที่ `.github/SETUP_SSH.md`

**สรุปสั้น ๆ:**
```bash
# 1. สร้าง SSH key
ssh-keygen -t ed25519 -C "github-actions@xmanstudio" -f ~/.ssh/github-actions-xman -N ""

# 2. แสดง PUBLIC key (ไปใส่บน server)
cat ~/.ssh/github-actions-xman.pub

# 3. SSH เข้า server และเพิ่ม public key
ssh admin@xman4289.com
echo "PASTE_PUBLIC_KEY_ตรงนี้" >> ~/.ssh/authorized_keys
exit

# 4. ทดสอบ
ssh -i ~/.ssh/github-actions-xman admin@xman4289.com
exit

# 5. แสดง PRIVATE key (ไปใส่ใน GitHub Secrets)
cat ~/.ssh/github-actions-xman
```

**จากนั้น:**
- Copy private key ทั้งหมด (รวม `-----BEGIN` และ `-----END`)
- ไปที่ GitHub Secrets
- สร้าง secret ชื่อ `SSH_PRIVATE_KEY`
- Paste ค่าที่ copy มา
- Save

**ดูขั้นตอนละเอียดทีละขั้น:** `.github/SETUP_SSH.md`

---

### 4. Setup Environments (Optional but Recommended)

**Location:** `Settings → Environments`

**Steps:**
1. ไปที่ https://github.com/xjanova/xmanstudio/settings/environments
2. คลิก **New environment**
3. ตั้งชื่อ: `production`
4. คลิก **Configure environment**
5. (Optional) เพิ่ม **Required reviewers** ถ้าต้องการ approval ก่อน deploy
6. คลิก **Save protection rules**

**Repeat for staging environment:**
- ชื่อ: `staging`
- ไม่ต้อง required reviewers

---

### 5. Branch Protection Rules (Highly Recommended)

**Location:** `Settings → Branches → Add rule`

**Steps:**
1. ไปที่ https://github.com/xjanova/xmanstudio/settings/branches
2. คลิก **Add rule** (ถ้ามี) หรือ **Add branch protection rule**
3. ตั้งค่าดังนี้:

#### Branch name pattern:
```
main
```

#### Protection rules:
- ✅ **Require a pull request before merging**
  - ✅ Require approvals: 1
  - ✅ Dismiss stale pull request approvals when new commits are pushed

- ✅ **Require status checks to pass before merging**
  - ✅ Require branches to be up to date before merging
  - เพิ่ม status checks ที่ต้อง pass:
    - `Tests (PHP 8.2)`
    - `Tests (PHP 8.3)`
    - `Code Quality Checks`
    - `Build Assets Check`

- ✅ **Require conversation resolution before merging**

- ✅ **Do not allow bypassing the above settings**

4. คลิก **Create** หรือ **Save changes**

---

### 6. Enable Dependabot (Recommended)

**Location:** `Settings → Code security and analysis`

**Steps:**
1. ไปที่ https://github.com/xjanova/xmanstudio/settings/security_analysis
2. หา **Dependabot**
3. เปิดทั้ง 3 ตัว:
   - ✅ **Dependabot alerts** (Enable)
   - ✅ **Dependabot security updates** (Enable)
   - ✅ **Dependabot version updates** (Enable)

---

### 7. Enable Issues and Discussions (Optional)

**Location:** `Settings → General → Features`

**Steps:**
1. ไปที่ https://github.com/xjanova/xmanstudio/settings
2. หา section **Features**
3. เลือก:
   - ✅ **Issues**
   - ✅ **Discussions** (Optional)
   - ✅ **Projects** (Optional)

---

## 🎯 Checklist Summary

**ต้องทำ (Required):**
- [ ] 1. Enable GitHub Actions
- [ ] 2. Add Secrets (SSH_HOST, SSH_USER, SSH_PRIVATE_KEY, DEPLOY_PATH, APP_URL)
- [ ] 3. Generate and configure SSH key

**แนะนำ (Recommended):**
- [ ] 4. Setup Environments (production, staging)
- [ ] 5. Branch Protection Rules for main branch
- [ ] 6. Enable Dependabot
- [ ] 7. Enable Issues

---

## 🧪 Testing Your Setup

### Test 1: CI Workflow
```bash
# Push code
git push origin main

# Check: https://github.com/xjanova/xmanstudio/actions
# Should see CI workflow running
```

### Test 2: Create Release
```
1. Go to: https://github.com/xjanova/xmanstudio/actions
2. Click: "Release & Versioning"
3. Click: "Run workflow"
4. Select: patch
5. Click: "Run workflow"
6. Wait and check: https://github.com/xjanova/xmanstudio/releases
```

### Test 3: Deploy
```
1. Go to: https://github.com/xjanova/xmanstudio/actions
2. Click: "Deploy to Production"
3. Click: "Run workflow"
4. Select environment: staging (test first!)
5. Click: "Run workflow"
6. Monitor the deployment
```

---

## 🆘 Troubleshooting

### Error: "Resource not accessible by integration"
**Fix:** ไปที่ Actions → General → Workflow permissions → เลือก "Read and write permissions"

### Error: "Secret not found"
**Fix:** ตรวจสอบว่า secret ชื่อถูกต้อง (case-sensitive) และมีค่าใส่แล้ว

### SSH Connection Failed
**Fix:**
1. ทดสอบ SSH key: `ssh -i ~/.ssh/github-actions-xman admin@xman4289.com`
2. ตรวจสอบ public key อยู่ใน `~/.ssh/authorized_keys` บน server
3. ตรวจสอบ private key ใน GitHub Secrets

### Workflow Not Running
**Fix:**
1. ตรวจสอบ Actions enabled: Settings → Actions → General
2. ตรวจสอบ workflow file ไม่มี syntax error
3. ลอง push อีกครั้ง

---

## 📞 Need Help?

**Documentation:**
- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [Secrets Management](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [Branch Protection](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches)

**Project Docs:**
- `.github/WORKFLOWS.md` - Workflow documentation
- `.claude/DEVELOPMENT_GUIDE.md` - Development guide
- `.github/SECRETS_TEMPLATE.md` - Secrets template

---

**Last Updated:** 2025-12-29
**Version:** 1.0.0

---

## ⏱️ Estimated Time

- **Required Setup:** 15-20 minutes
- **Recommended Setup:** 10-15 minutes
- **Total:** 25-35 minutes

**After setup, everything works automatically!** 🚀
