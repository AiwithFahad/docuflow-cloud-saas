// DocuFlow Cloud — marketing site: monthly/yearly pricing toggle
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('billingToggle');
  if (!toggle) return;
  const apply = () => {
    const yearly = toggle.checked;
    document.querySelectorAll('[data-monthly]').forEach(el => {
      el.textContent = yearly ? el.dataset.yearly : el.dataset.monthly;
    });
    document.querySelectorAll('.billing-note').forEach(el => {
      if (el.textContent.includes('/')) el.textContent = yearly ? '/mo, billed yearly' : '/mo';
    });
    document.querySelectorAll('p .billing-note').forEach(el => { el.textContent = yearly ? 'yearly' : 'monthly'; });
  };
  toggle.addEventListener('change', apply);
});
