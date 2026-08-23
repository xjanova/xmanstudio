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
(`index.html` → `home.js`, `products.js`; `brainx.html`, `wiki.html` → ของตัวเอง)

## เว็บที่มีตอนนี้

| โฟลเดอร์ | หน้าเว็บ | เนื้อหา |
|---|---|---|
| `product.xman4289.com/` | `index.html` | หน้าแรก — กลุ่มดาวผลิตภัณฑ์ 16 ตัว วาดบน canvas 2D แบบ project เอง ไม่มี lib |
| | `brainx.html` | หน้าขาย BrainX |
| | `wiki.html` | wiki |

catalogue ของทั้งกลุ่มดาวและ grid อ่านจาก `assets/js/products.js` ที่เดียว
เพิ่มสินค้า = เพิ่มหนึ่งแถวในนั้น ไม่ต้องแตะ layout
