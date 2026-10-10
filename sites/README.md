# sites/ — เว็บ static ของซับโดเมนอื่น

โฟลเดอร์นี้เก็บ **source จริง** ของเว็บ static ที่อยู่คนละซับโดเมนกับตัว Laravel
แต่ใช้ repo เดียวกัน เพื่อให้ทุกอย่างของ XMAN Studio ตามรอยได้จากที่เดียว

ก่อนหน้านี้ `product.xman4289.com` ไม่มี repo เลย — source ของจริงอยู่บนเซิร์ฟเวอร์
อย่างเดียว ใครแก้อะไรไปก็ไม่มีประวัติ และถ้าเผลอแก้จากสำเนาเก่าก็ทับงานคนอื่นได้

## กติกา

- **ชื่อโฟลเดอร์ = ชื่อโดเมน** เพราะ deploy ใช้ชื่อนี้หา `public_html` ปลายทางตรง ๆ
  (`sites/<domain>/` → `/home/admin/domains/<domain>/public_html/`)
  ตั้งชื่อผิด = ไม่มีอะไรขึ้น ไม่ใช่ deploy ผิดที่
- เว็บพวกนี้ **ไม่มี build step** ไม่มี dependency ไม่มี CDN — ไฟล์ในนี้คือไฟล์ที่
  เสิร์ฟจริงแบบ 1:1 แก้แล้วเห็นผลเลย
- อย่าแก้ไฟล์บนเซิร์ฟเวอร์โดยตรง แก้ที่นี่แล้ว push การ deploy จะ `rsync` ทับให้

## Deploy

ขึ้นอัตโนมัติกับ `.github/workflows/auto-deploy.yml` — push เข้า main, CI ผ่าน,
Laravel deploy เสร็จ แล้วขั้น "Syncing static sites" จะ `rsync` ทุกโฟลเดอร์ในนี้
ไปยัง `public_html` ของโดเมนที่ชื่อตรงกัน

สองข้อที่ต้องรู้:

1. **ไม่ได้ใช้ `--delete`** — ไฟล์ที่ลบออกจาก repo จะยังค้างอยู่บนเซิร์ฟเวอร์
   ตั้งใจให้เป็นแบบนี้ เผื่อมีไฟล์ที่วางมือไว้บนเซิร์ฟเวอร์แล้วไม่ได้เข้า repo
   ถ้าจะลบจริงต้องเข้าไปลบเอง
2. ขั้นตอนนี้ **non-fatal** — ถ้า sync พัง Laravel deploy ยังนับว่าสำเร็จ
   ต้องไปอ่าน log ของ workflow ถึงจะเห็น อย่าเชื่อว่าเขียว = ขึ้นแล้ว

deploy มือ (เมื่อจำเป็น) จากเครื่องตัวเอง:

```bash
rsync -av -e "ssh -i ~/.ssh/thaiprompt_admin" sites/product.xman4289.com/ admin@123.253.62.251:/home/admin/domains/product.xman4289.com/public_html/
```

## ⚠️ `?v=` ใน HTML ต้องบัมพ์ทุกครั้งที่แก้ CSS/JS

`.htaccess` ของเว็บพวกนี้ตั้ง asset เป็น `Cache-Control: public, max-age=31536000, immutable`
และมี Cloudflare นั่งหน้าอีกชั้น **URL คือ cache key** — ถ้าแก้ `assets/js/home.js`
แล้วไม่บัมพ์ `?v=` ใน HTML ที่อ้างถึงมัน ทั้งเบราว์เซอร์และ Cloudflare จะเสิร์ฟของเก่า
ต่อไปอีกหนึ่งปี โดยที่ไฟล์ใหม่ขึ้นเซิร์ฟเวอร์เรียบร้อยแล้ว — เคยโดนมาแล้ว
(เจอ `cf-cache-status: HIT` กับ `Age: 39221`)

ส่วน HTML ตั้ง `no-cache, must-revalidate` ไว้ จึงไม่ต้องบัมพ์อะไร

```bash
# บัมพ์ทุก stamp ในไฟล์เดียว
sed -i "s/v=[0-9]\{12\}/v=$(date +%Y%m%d%H%M)/g" sites/product.xman4289.com/index.html
```

แต่ละหน้า HTML ถือ stamp ของตัวเอง บัมพ์เฉพาะหน้าที่อ้างถึงไฟล์ที่แก้ก็พอ
(`index.html` → `hub.css` + `assets/js/hub/*` รวม `xph/shaders` ใน importmap;
`classic.html` → `home.js`, `products.js`; `brainx.html`, `wiki.html` → ของตัวเอง)

ไฟล์ที่ JS อ้างเองไม่ได้ผ่าน HTML มี stamp ของตัวเอง:
- รูปสินค้า (`assets/art/`, `assets/shots/`, `assets/img/` ที่ catalog ชี้) → `IMG_V` ใน `assets/js/hub/catalog.js`
- รูปและคลิปน้อง Nova (`assets/nova/`) → `NOVA_V` ใน `assets/js/hub/guide.js`
- three.js อยู่ใน `assets/vendor/three-r186/` — **เวอร์ชันอยู่ในชื่อโฟลเดอร์** อัปเกรดเมื่อไหร่ให้สร้างโฟลเดอร์ใหม่
  แล้วแก้ importmap ใน `index.html` ห้ามเขียนทับไฟล์ชื่อเดิม

## เว็บที่มีตอนนี้

| โฟลเดอร์ | หน้าเว็บ | เนื้อหา |
|---|---|---|
| `product.xman4289.com/` | `index.html` | **Product Hub** — คอนเซปต์เดียวกับ XgamesHub ธีมซอฟต์แวร์: จักรวาล 3D (three.js) หลังหน้า, สปอตไลต์ที่ "หน้าจอโฮโลแกรม" โชว์ภาพของโปรแกรม, คลังโปรแกรม, release log, สตูดิโอ และ**น้อง Nova** ของ xman4289.com เป็นไกด์ |
| | `classic.html` | หน้าแรกแบบเก่า (กลุ่มดาว canvas 2D) — **เลิกใช้แล้วแต่เก็บไว้** ไม่มีลิงก์เข้า ตั้ง `noindex` |
| | `brainx.html` | หน้าขาย BrainX |
| | `wiki.html` | wiki |

### Product Hub (`index.html`)

| ไฟล์ | ทำอะไร |
|---|---|
| `assets/js/hub/catalog.js` | **แหล่งข้อมูลเดียว** — สินค้า 16 ตัว (สถานะ/ราคา/แพลตฟอร์ม/ลิงก์/เวอร์ชัน/คำพูดของ Nova), 5 ตัวในสปอตไลต์, release log |
| `assets/js/hub/app.js` | หน้าเว็บ: สปอตไลต์, ตัวกรอง, การ์ด, กล่องรายละเอียด, ค้นหา (Ctrl K หรือ `/`), ทัวร์ |
| `assets/js/hub/guide.js` | น้อง Nova: ภาพนิ่ง + คลิป VP9 โปร่งใส, ฟองคำพูด, จิ้มแล้วมีปฏิกิริยา, ปุ่ม Motion |
| `assets/js/hub/universe.js` + `shaders.js` | ฉาก 3D: ท้องฟ้าเนบิวลา, ดาวเคราะห์ (มีแบบ "ลายวงจร"), หน้าจอโปรแกรมโค้งที่หมุนได้, วงแหวนอักขระโค้ด, แกน X ของสตูดิโอที่ลากเส้นไปหาทุกโปรแกรม |
| `assets/art/<id>.webp` | คีย์อาร์ตของสินค้า (สำเนาจาก `public_html/artwork/` ของ xmanstudio) |
| `assets/shots/<id>/` | **ภาพหน้าจอจริง** — ตอนนี้มี WinXTools 9 ภาพ, BrainX ใช้ `assets/img/hero-hud.jpg` กับ `crop-galaxy.jpg` |
| `assets/nova/` | สำเนาภาพ/คลิปน้อง Nova จาก `public_html/artwork/universe/guide/` (วิธีทำคลิป: `docs/UNIVERSE_GUIDE_CLIPS.md`) |

- **เพิ่มสินค้า** = เพิ่มหนึ่ง entry ใน `PRODUCTS` + วางคีย์อาร์ตที่ `assets/art/<id>.webp` (16:9)
- **เพิ่มภาพหน้าจอจริง** = วาง `.webp` ใน `assets/shots/<id>/` แล้วใส่ใน `shots` ของสินค้านั้น
  สินค้าที่มี `shots` จะได้ป้าย LIVE CAPTURE, หน้าจอ 3D สลับภาพให้เอง, การ์ดเอาเมาส์ชี้แล้วเห็นภาพจริง, และแกลเลอรีในกล่องรายละเอียด
- กล่องรายละเอียดถาม `https://xman4289.com/api/v1/products/<slug>/version` สด (CORS เปิด `*`)
  แต่ throttle `60/นาที` ของ API นี้ **นับต่อ IP ร่วมกับการเช็กอัปเดตของแอปเดสก์ท็อป** จึงถามเฉพาะตอนเปิดกล่อง
  และจำไว้ 10 นาทีต่อสินค้า — อย่าเปลี่ยนเป็นวนถามทุกการ์ดตอนโหลดหน้า
- ปุ่มดาวน์โหลดชี้ route ของ xman4289.com เท่านั้น (กฎเจ้าของ: ห้ามลิงก์ GitHub) — changelog ที่ดึงมา
  กรองบรรทัดที่มีคำว่า github หรือ URL ออกก่อนแสดง
- เปิดดูในเครื่อง: `.claude/launch.json` มี `product-site` (python http.server port 8091)
  ภาพ 3D ต้องดูผ่าน Chrome ที่มี GPU — Browser pane ในแอปวาด WebGL ไม่ได้

`classic.html` อ่าน catalogue เก่าจาก `assets/js/products.js` — ไม่ต้องอัปเดตแล้ว
แต่ grid สินค้าท้ายหน้า `brainx.html` ยังอ่านสำเนาของตัวเอง — `PRODUCTS` ใน `assets/js/site.js`
แก้ราคา สถานะ "เร็ว ๆ นี้" หรือคำอธิบาย ต้องแก้ทั้ง `catalog.js` และ `site.js`
และบัมพ์ stamp ของหน้าที่เกี่ยว

ราคาให้เทียบกับที่ร้านขายจริง (หน้า `https://xman4289.com/products/<slug>`)
ไม่ใช่ `/api/v1/product/<slug>/pricing` อย่างเดียว — API นั้นคืน 399/2500/5000
เป็นค่า default ให้ทุกสินค้าที่ไม่มีราคากำหนดไว้ใน `ProductLicenseController`
ราคาที่เป็นรายปี/รายเดือนให้บอกหน่วยไว้ในข้อความราคาด้วย

### XgamesHub (`xmangameshub.online`, เดิม `xgameshub.xman4289.com`) ไม่อยู่ที่นี่แล้ว

ย้ายไป `xmangameshub.online` เมื่อ 2026-10-10 — path เดิมของโดเมนเก่าบนเซิร์ฟเวอร์เป็น symlink ไปที่ใหม่ และโดเมนเก่ายังเสิร์ฟไฟล์ชุดเดียวกัน (แอปกรุงศรีบน Android เรียก API ที่นั่น) ห้ามลบโดเมนเก่าใน DirectAdmin

ตั้งแต่ 2026-10-05 repo `xjanova/XgamesHub` deploy ตัวเอง (workflow **Release & Deploy**:
merge เข้า main → build → rsync ด้วยคีย์ที่ล็อก `rrsync` ไว้เฉพาะ web root ของโดเมนนั้น → GitHub Release)
อย่าเพิ่มโฟลเดอร์ `sites/xgameshub.xman4289.com/` กลับมา — deploy ของที่นี่จะเอาสำเนานั้นไปทับเว็บที่ใหม่กว่า
