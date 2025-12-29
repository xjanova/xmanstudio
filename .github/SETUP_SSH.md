# 🔑 วิธีตั้งค่า SSH Key สำหรับ GitHub Actions Deploy

## ขั้นตอนที่ 1: สร้าง SSH Key บนเครื่องของคุณ

```bash
# สร้าง SSH key ใหม่
ssh-keygen -t ed25519 -C "github-actions@xmanstudio" -f ~/.ssh/github-actions-xman -N ""
```

**คำอธิบาย:**
- `-N ""` = ไม่ใส่ passphrase (กด Enter ผ่านเลย)
- จะได้ไฟล์ 2 ไฟล์:
  - `~/.ssh/github-actions-xman` (private key - ใส่ใน GitHub)
  - `~/.ssh/github-actions-xman.pub` (public key - ใส่ใน Server)

---

## ขั้นตอนที่ 2: Copy PUBLIC KEY ไปใส่บน Server

### 2.1 แสดง Public Key
```bash
cat ~/.ssh/github-actions-xman.pub
```

**คุณจะเห็นข้อความคล้ายนี้:**
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIAbCdEfGhIjKlMnOpQrStUvWxYz01234567890ABC github-actions@xmanstudio
```

### 2.2 Copy ทั้งหมด (ทั้งบรรทัด)

### 2.3 SSH เข้า Server ของคุณ
```bash
ssh admin@xman4289.com
```

### 2.4 เพิ่ม Public Key ใน authorized_keys
```bash
# บน Server - รันคำสั่งนี้
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "PASTE_PUBLIC_KEY_ตรงนี้" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

**ตัวอย่าง:**
```bash
echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIAbCdEfGhIjKlMnOpQrStUvWxYz01234567890ABC github-actions@xmanstudio" >> ~/.ssh/authorized_keys
```

### 2.5 ออกจาก Server
```bash
exit
```

---

## ขั้นตอนที่ 3: ทดสอบ SSH Connection

```bash
# ทดสอบจากเครื่องของคุณ
ssh -i ~/.ssh/github-actions-xman admin@xman4289.com
```

**ถ้าเข้าได้โดยไม่ต้องใส่รหัสผ่าน = สำเร็จ!**

ออกจาก server:
```bash
exit
```

---

## ขั้นตอนที่ 4: Copy PRIVATE KEY สำหรับ GitHub

### 4.1 แสดง Private Key
```bash
cat ~/.ssh/github-actions-xman
```

**คุณจะเห็นข้อความคล้ายนี้:**
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtz
c2gtZWQyNTUxOQAAACAAQgxEXwaISoyZTiOnkFKy1VL1sWM9NdEw01Ifo/tAAA
AIgL8vYYC/L2GAAAALc3NoLWVkMjU1MTkAAAAgAEIMRF8GiErMmU4jp5BSstVS
...อีกหลายบรรทัด...
-----END OPENSSH PRIVATE KEY-----
```

### 4.2 Copy ทั้งหมด (รวม -----BEGIN และ -----END)
- **สำคัญ:** ต้อง copy ทั้งหมดทุกบรรทัด
- รวม `-----BEGIN OPENSSH PRIVATE KEY-----`
- รวม `-----END OPENSSH PRIVATE KEY-----`

---

## ขั้นตอนที่ 5: ตั้งค่า Secrets บน GitHub

### 5.1 ไปที่หน้า Secrets
```
https://github.com/xjanova/xmanstudio/settings/secrets/actions
```

### 5.2 เพิ่ม Secrets ทีละตัว

#### Secret 1: SSH_HOST
```
คลิก: New repository secret

Name: SSH_HOST
Value: xman4289.com

คลิก: Add secret
```

#### Secret 2: SSH_USER
```
คลิก: New repository secret

Name: SSH_USER
Value: admin

คลิก: Add secret
```

#### Secret 3: SSH_PORT
```
คลิก: New repository secret

Name: SSH_PORT
Value: 22

คลิก: Add secret
```

#### Secret 4: SSH_PRIVATE_KEY
```
คลิก: New repository secret

Name: SSH_PRIVATE_KEY
Value: (paste private key ที่ copy จากขั้นตอนที่ 4)

คลิก: Add secret
```

**สำคัญ:** Private key ต้อง paste ทั้งหมดรวม:
```
-----BEGIN OPENSSH PRIVATE KEY-----
...ทุกบรรทัด...
-----END OPENSSH PRIVATE KEY-----
```

#### Secret 5: DEPLOY_PATH
```
คลิก: New repository secret

Name: DEPLOY_PATH
Value: /home/admin/domains/xman4289.com/public_html

คลิก: Add secret
```

#### Secret 6: APP_URL
```
คลิก: New repository secret

Name: APP_URL
Value: https://xman4289.com

คลิก: Add secret
```

---

## ขั้นตอนที่ 6: ตรวจสอบ Secrets

ไปที่: https://github.com/xjanova/xmanstudio/settings/secrets/actions

**ต้องมี 6 secrets:**
- ✅ SSH_HOST
- ✅ SSH_USER
- ✅ SSH_PORT
- ✅ SSH_PRIVATE_KEY
- ✅ DEPLOY_PATH
- ✅ APP_URL

---

## 🧪 ทดสอบ Deployment

### ขั้นตอนที่ 7: ทดสอบ Deploy Workflow

1. ไปที่: https://github.com/xjanova/xmanstudio/actions
2. คลิก: **Deploy to Production**
3. คลิก: **Run workflow**
4. เลือก Branch: `main` หรือ `claude/create-xman-studio-site-zwWVD`
5. เลือก Environment: `staging` (ทดสอบก่อน)
6. คลิก: **Run workflow** (ปุ่มเขียว)

### ดู Log

- คลิกที่ workflow run ที่เพิ่งสร้าง
- ดู step **Deploy to server**
- ถ้า SSH connection สำเร็จ จะเห็น:
  ```
  ✅ Connected to server
  ✅ Deploying to staging environment
  ```

---

## ❌ แก้ปัญหา

### ปัญหา: SSH connection failed

**ตรวจสอบ:**
1. ทดสอบ SSH จากเครื่องของคุณ:
   ```bash
   ssh -i ~/.ssh/github-actions-xman admin@xman4289.com
   ```
   - ถ้าเข้าไม่ได้ = Public key บน server ผิด
   - ลอง add public key ใหม่ (ขั้นตอนที่ 2)

2. ตรวจสอบ Private Key ใน GitHub Secrets:
   - ต้องมี `-----BEGIN` และ `-----END`
   - ต้องไม่มีช่องว่างหรือบรรทัดเพิ่มเติม

3. ตรวจสอบ Secrets ชื่อถูกต้อง:
   - `SSH_PRIVATE_KEY` (ไม่ใช่ SSH_KEY)
   - Case-sensitive

### ปัญหา: Permission denied

**บน Server:**
```bash
ssh admin@xman4289.com
ls -la ~/.ssh/
```

**ต้องเป็น:**
```
drwx------ .ssh/              (700)
-rw------- authorized_keys    (600)
```

**แก้ไข:**
```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

---

## 📝 Checklist

- [ ] สร้าง SSH key แล้ว
- [ ] Copy public key ใส่ใน server แล้ว (`~/.ssh/authorized_keys`)
- [ ] ทดสอบ SSH connection สำเร็จ (ไม่ต้องใส่รหัสผ่าน)
- [ ] Copy private key แล้ว
- [ ] เพิ่ม Secrets ครบ 6 ตัวใน GitHub
- [ ] ทดสอบ Deploy workflow

---

## ⏱️ ใช้เวลา

- ขั้นตอนที่ 1-4: **5 นาที**
- ขั้นตอนที่ 5-6: **5 นาที**
- ขั้นตอนที่ 7: **2 นาที**
- **รวม: 12 นาที**

---

**Last Updated:** 2025-12-29
