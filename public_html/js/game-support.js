document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', async () => {
  const status = button.parentElement.querySelector('[data-copy-status]');
  try { await navigator.clipboard.writeText(button.dataset.copy); status.textContent = 'คัดลอกเลขบัญชีแล้ว'; }
  catch { status.textContent = 'คัดลอกเลขบัญชีด้านบนได้เลย'; }
}));
document.querySelectorAll('[data-amount]').forEach(button => button.addEventListener('click', () => {
  const input = document.querySelector('#donation-amount'); if (input) { input.value = button.dataset.amount; input.focus(); }
}));
