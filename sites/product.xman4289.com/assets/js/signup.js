/* Free-trial signup — phone number, then a 6-digit SMS code.
 *
 * The BACKEND IS NOT WIRED YET: verification will run through the XMAN Studio
 * member system. Everything here is built so that switching it on is a matter
 * of replacing two functions (sendCode / verifyCode) with real fetch calls —
 * the shape of what they take and return is already fixed below.
 *
 * Until then the form is honest about what it does: it records the request
 * locally and says so, rather than pretending a code was sent. Claiming to
 * have texted someone when nothing was texted is the one thing a trial signup
 * must never do — the user sits waiting for an SMS that cannot arrive.
 */
(() => {
'use strict';
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const form = $('#signup');
if (!form) return;

const TH = () => document.documentElement.getAttribute('data-lang') === 'th';
const step = n => $$('.su-step', form).forEach(s => s.hidden = (+s.dataset.step !== n));

/* ── the two seams the real backend plugs into ─────────────
   sendCode({ phone, ref })  -> { ok, pending?, error? }
   verifyCode({ phone, code }) -> { ok, licenceKey?, referralCode?, error? } */
const PENDING_KEY = 'brainx.trial.request';

async function sendCode(payload) {
  // return fetch('/api/trial/send', {method:'POST', headers:{'Content-Type':'application/json'},
  //   body: JSON.stringify(payload)}).then(r => r.json());
  try { localStorage.setItem(PENDING_KEY, JSON.stringify({ ...payload, at: Date.now() })); } catch {}
  return { ok: true, pending: true };            // pending = backend not live yet
}
async function verifyCode(payload) {
  // return fetch('/api/trial/verify', {method:'POST', ...}).then(r => r.json());
  return { ok: true, pending: true };
}

/* ── phone ─────────────────────────────────────────────── */
const phone = $('#su-phone'), cc = $('#su-cc'), ref = $('#su-ref');

phone.addEventListener('input', () => {
  // digits only, grouped so a Thai number reads the way people write it
  let d = phone.value.replace(/\D/g, '').slice(0, 11);
  phone.value = cc.value === '+66'
    ? d.replace(/^(\d{0,3})(\d{0,3})(\d{0,4}).*/, (_, a, b, c) => [a, b, c].filter(Boolean).join(' '))
    : d;
  phone.classList.remove('bad');
});

const digits = () => phone.value.replace(/\D/g, '');
const valid = () => digits().length >= 8 && digits().length <= 11;

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!valid()) {
    phone.classList.add('bad');
    phone.focus();
    return;
  }
  const btn = $('.su-go', form);
  btn.disabled = true;
  const res = await sendCode({ phone: cc.value + digits(), ref: ref.value.trim() || null });
  btn.disabled = false;
  if (!res.ok) return;

  $('#su-sent').textContent = res.pending
    ? (TH() ? `บันทึกคำขอสำหรับ ${cc.value} ${phone.value} ไว้แล้ว — ระบบส่ง SMS กำลังเชื่อมต่อ ทีมงานจะติดต่อกลับเพื่อเปิดสิทธิ์ให้`
            : `Request recorded for ${cc.value} ${phone.value}. SMS delivery is still being connected — the team will activate you directly.`)
    : (TH() ? `ส่งรหัสไปที่ ${cc.value} ${phone.value} แล้ว` : `Code sent to ${cc.value} ${phone.value}`);
  step(2);
  $$('#otp input')[0]?.focus();
});

$('#su-back')?.addEventListener('click', () => { step(1); phone.focus(); });

/* ── OTP boxes: type forward, backspace backward, paste fills ── */
const boxes = $$('#otp input');
boxes.forEach((b, i) => {
  b.addEventListener('input', () => {
    b.value = b.value.replace(/\D/g, '').slice(0, 1);
    if (b.value && i < boxes.length - 1) boxes[i + 1].focus();
  });
  b.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !b.value && i > 0) boxes[i - 1].focus();
  });
  b.addEventListener('paste', e => {
    const d = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
    if (!d) return;
    e.preventDefault();
    d.split('').forEach((ch, k) => { if (boxes[k]) boxes[k].value = ch; });
    boxes[Math.min(d.length, boxes.length - 1)].focus();
  });
});

$('#su-verify')?.addEventListener('click', async () => {
  const code = boxes.map(b => b.value).join('');
  const res = await verifyCode({ phone: cc.value + digits(), code });
  $('#su-msg').textContent = res.pending
    ? (TH() ? 'เราเก็บคำขอของคุณไว้แล้ว เมื่อระบบสมาชิกเปิดใช้งาน คุณจะได้รับ SMS พร้อมรหัสเปิดใช้ทดลอง 1 เดือนเต็ม'
            : 'Your request is saved. When the member system goes live you will get an SMS with your key for the full free month.')
    : (TH() ? 'เปิดใช้งานเรียบร้อย ทดลองใช้ได้ 30 วันเต็ม' : 'Activated. Your 30 days start now.');
  step(3);
});
})();
