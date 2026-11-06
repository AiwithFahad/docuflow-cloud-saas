// DocuFlow Cloud — static demo behaviour (mock data only)
const PLANS = [
  {name:'Free', m:0, y:0, feats:['100 MB storage','3 users','Files up to 10 MB','3 versions kept','Community support']},
  {name:'Starter', m:12, y:120, feats:['10 GB storage','10 users','Files up to 25 MB','10 versions kept','Approvals & email support']},
  {name:'Professional', m:29, y:290, featured:true, feats:['100 GB storage','25 users','Files up to 100 MB','25 versions kept','Teams, audit logs, reports']},
  {name:'Business', m:79, y:790, feats:['500 GB storage','100 users','Files up to 250 MB','50 versions kept','API access & custom branding']},
  {name:'Enterprise', m:199, y:1990, feats:['Unlimited storage','Unlimited users','Unlimited file size','100 versions kept','SSO & 99.9% SLA']},
];

const DOCS = [
  {name:'Supplier Contract 2026', ext:'pdf', icon:'📕', cls:'#dc2626', cat:'Contracts', owner:'Ayesha Khan', size:'2.4 MB', dl:5, date:'2026-10-28', folder:'Finance'},
  {name:'Invoices — October', ext:'xlsx', icon:'📗', cls:'#15803d', cat:'Invoices', owner:'Bilal Ahmed', size:'860 KB', dl:12, date:'2026-11-01', folder:'Finance'},
  {name:'Remote Work Policy', ext:'docx', icon:'📘', cls:'#1d4ed8', cat:'Policies', owner:'Ayesha Khan', size:'310 KB', dl:34, date:'2026-10-19', folder:'HR'},
  {name:'Q3 Sales Report', ext:'pdf', icon:'📕', cls:'#dc2626', cat:'Reports', owner:'Bilal Ahmed', size:'1.8 MB', dl:21, date:'2026-10-30', folder:'Finance'},
  {name:'Brand Guidelines', ext:'pdf', icon:'📕', cls:'#dc2626', cat:'Reports', owner:'Sara Malik', size:'6.2 MB', dl:48, date:'2026-09-14', folder:'Marketing'},
  {name:'Employee Handbook', ext:'docx', icon:'📘', cls:'#1d4ed8', cat:'Policies', owner:'Ayesha Khan', size:'940 KB', dl:67, date:'2026-08-22', folder:'HR'},
  {name:'Vendor Onboarding Pack', ext:'zip', icon:'🗜️', cls:'#6b7280', cat:'Contracts', owner:'Sara Malik', size:'18.5 MB', dl:9, date:'2026-10-25', folder:'Operations'},
  {name:'Product Launch Deck', ext:'pptx', icon:'📙', cls:'#d97706', cat:'Reports', owner:'Omar Farooq', size:'11.3 MB', dl:15, date:'2026-11-03', folder:'Marketing'},
];

document.addEventListener('DOMContentLoaded', () => {
  /* ---- landing: burger + pricing ---- */
  const burger = document.getElementById('burger');
  const links = document.getElementById('navLinks');
  if (burger && links) burger.addEventListener('click', () => links.classList.toggle('open'));

  const grid = document.getElementById('priceGrid');
  const sw = document.getElementById('billingSwitch');
  function renderPrices() {
    if (!grid) return;
    const yearly = sw && sw.classList.contains('on');
    grid.innerHTML = PLANS.slice(0, 4).map(p => `
      <div class="price ${p.featured ? 'featured' : ''}">
        ${p.featured ? '<span class="price-badge">Most popular</span>' : ''}
        <h3 style="margin:0;color:var(--navy)">${p.name}</h3>
        <div class="price-num">$${yearly ? p.y : p.m}<small>/mo${yearly ? ', billed yearly' : ''}</small></div>
        <ul>${p.feats.map(f => `<li>${f}</li>`).join('')}</ul>
        <a class="btn ${p.featured ? 'btn-purple' : 'btn-outline'}" href="dashboard.html" style="justify-content:center">Start 14-day trial</a>
      </div>`).join('');
  }
  if (sw) sw.addEventListener('click', () => { sw.classList.toggle('on'); renderPrices(); });
  renderPrices();

  /* ---- app: sidebar toggle + pages ---- */
  const sidebar = document.getElementById('sidebar');
  const sbToggle = document.getElementById('sbToggle');
  if (sbToggle && sidebar) sbToggle.addEventListener('click', () => sidebar.classList.toggle('hidden'));

  window.switchPage = function (name) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('show'));
    const el = document.getElementById('page-' + name);
    if (el) el.classList.add('show');
    document.querySelectorAll('#sbNav a').forEach(a => a.classList.toggle('active', a.dataset.page === name));
    if (window.innerWidth < 900 && sidebar) sidebar.classList.add('hidden');
  };
  document.querySelectorAll('#sbNav a').forEach(a =>
    a.addEventListener('click', () => switchPage(a.dataset.page)));

  /* ---- charts ---- */
  if (window.Chart) {
    const up = document.getElementById('upChart');
    if (up) new Chart(up, {type:'line', data:{labels:['Jun','Jul','Aug','Sep','Oct','Nov'],
      datasets:[{label:'Uploads', data:[86,124,118,162,190,174], borderColor:'#7c3aed', backgroundColor:'rgba(124,58,237,.12)', fill:true, tension:.35}]},
      options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}, maintainAspectRatio:false}});
    const cat = document.getElementById('catChart');
    if (cat) new Chart(cat, {type:'doughnut', data:{labels:['Contracts','Invoices','Policies','Reports','Other'],
      datasets:[{data:[342,418,196,228,100], backgroundColor:['#7c3aed','#1e3a5f','#a78bfa','#3b82c4','#c4b5fd']}]},
      options:{plugins:{legend:{position:'bottom'}}, maintainAspectRatio:false}});
  }

  /* ---- file manager: folder tree, grid/list, search ---- */
  const folders = [
    {name:'All files', count:1284, depth:0, key:'all'},
    {name:'Finance', count:512, depth:0, key:'Finance'},
    {name:'Invoices 2026', count:214, depth:1, key:'Finance'},
    {name:'HR', count:288, depth:0, key:'HR'},
    {name:'Legal', count:176, depth:0, key:'Legal'},
    {name:'Marketing', count:198, depth:0, key:'Marketing'},
    {name:'Operations', count:110, depth:0, key:'Operations'},
  ];
  const tree = document.getElementById('folderTree');
  let activeFolder = 'Finance';
  function renderTree() {
    if (!tree) return;
    tree.innerHTML = folders.map(f => `
      <a class="tree-item ${f.key === activeFolder ? 'active' : ''}" data-folder="${f.key}" style="padding-left:${8 + f.depth * 18}px">
      📁 ${f.name}<span class="cnt">${f.count}</span></a>`).join('');
    tree.querySelectorAll('[data-folder]').forEach(a => a.addEventListener('click', () => {
      activeFolder = a.dataset.folder; renderTree(); renderFiles();
    }));
  }
  const gridEl = document.getElementById('fileGrid');
  const tableEl = document.getElementById('fileTable');
  const recentEl = document.getElementById('recentList');
  const q = document.getElementById('globalSearch');
  function visibleDocs() {
    const term = (q && q.value || '').toLowerCase();
    return DOCS.filter(d =>
      (activeFolder === 'all' || d.folder === activeFolder) &&
      (!term || d.name.toLowerCase().includes(term) || d.cat.toLowerCase().includes(term)));
  }
  function renderFiles() {
    const docs = visibleDocs();
    const crumb = document.getElementById('crumb');
    if (crumb) crumb.innerHTML = `Files / <strong>${activeFolder === 'all' ? 'All files' : activeFolder}</strong> · ${docs.length} item(s)`;
    if (gridEl) gridEl.innerHTML = docs.map(d => `
      <div class="fcard"><span class="file-ic">${d.icon}</span><b>${d.name}.${d.ext}</b>
      <small>${d.size} · ${d.date}</small><br><span class="pill grey">${d.cat}</span></div>`).join('')
      || '<p style="color:var(--muted)">No documents match.</p>';
    if (tableEl) tableEl.innerHTML = docs.map(d => `
      <tr><td>${d.icon} ${d.name}.${d.ext}</td><td>${d.cat}</td><td>${d.owner}</td><td>${d.size}</td><td>${d.dl}</td><td>${d.date}</td></tr>`).join('');
    if (recentEl) recentEl.innerHTML = DOCS.slice(0, 5).map(d => `
      <a href="#" onclick="switchPage('files');return false"><span class="file-ic">${d.icon}</span>
      <span><strong>${d.name}.${d.ext}</strong><small>${d.owner} · ${d.size} · ${d.date}</small></span><span style="margin-left:auto;color:#98a2b8">›</span></a>`).join('');
  }
  renderTree(); renderFiles();
  if (q) q.addEventListener('input', () => { switchPage('files'); renderFiles(); });

  const gridBtn = document.getElementById('gridBtn'), listBtn = document.getElementById('listBtn');
  const wrap = document.getElementById('fileTableWrap');
  if (gridBtn && listBtn) {
    gridBtn.addEventListener('click', () => { gridBtn.classList.add('on'); listBtn.classList.remove('on'); gridEl.style.display = ''; wrap.style.display = 'none'; });
    listBtn.addEventListener('click', () => { listBtn.classList.add('on'); gridBtn.classList.remove('on'); gridEl.style.display = 'none'; wrap.style.display = ''; });
  }
});
