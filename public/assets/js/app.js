// DocuFlow Cloud — workspace behaviour: sidebar toggle + active link highlight
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('appSidebar');
  const toggle = document.getElementById('sidebarToggle');
  if (toggle && sidebar) toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', (e) => {
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle && !toggle?.contains(e.target)) {
      if (window.innerWidth < 992) sidebar.classList.remove('open');
    }
  });
  const here = window.location.pathname.replace(/\/$/, '');
  document.querySelectorAll('.sidebar-nav a').forEach(a => {
    const href = new URL(a.href).pathname.replace(/\/$/, '');
    if (href && href === here) a.classList.add('active');
    if (here.startsWith('/app/documents') && a.getAttribute('href')?.includes('/app/files')) a.classList.add('active');
  });
});
