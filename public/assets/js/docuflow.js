/* DocuFlow Cloud — shared workspace JavaScript layer.
   Powers the AJAX file manager, dashboard charts (real API aggregates),
   toasts, confirm dialogs, favourites and stats widgets on every
   authenticated page. Loaded after app.js by partials/app-close. */
(function () {
  'use strict';
  const base = (document.querySelector('meta[name="app-base"]') || {}).content || '/';
  const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const DF = {
    url: (p) => base.replace(/\/?$/, '/') + p.replace(/^\//, ''),
    async api(path, opts = {}) {
      const res = await fetch(DF.url(path), {
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': csrf, ...(opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}), ...(opts.headers || {}) },
        method: opts.method || 'GET',
        body: opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData) ? JSON.stringify(opts.body) : opts.body,
      });
      let data = null;
      try { data = await res.json(); } catch (e) { /* non-JSON */ }
      if (!res.ok || (data && data.ok === false)) {
        const msg = (data && data.error) || ('Request failed (' + res.status + ')');
        throw new Error(msg);
      }
      return data;
    },
    toast(msg, type = 'success') {
      let wrap = document.getElementById('dfToasts');
      if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'dfToasts';
        wrap.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(wrap);
      }
      const el = document.createElement('div');
      el.className = 'toast align-items-center text-bg-' + (type === 'error' ? 'danger' : type) + ' border-0';
      el.innerHTML = '<div class="d-flex"><div class="toast-body">' + esc(msg) + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
      wrap.appendChild(el);
      const t = new bootstrap.Toast(el, { delay: 4200 });
      t.show();
      el.addEventListener('hidden.bs.toast', () => el.remove());
    },
    confirm(title, body) {
      return new Promise((resolve) => {
        let m = document.getElementById('dfConfirm');
        if (!m) {
          m = document.createElement('div');
          m.id = 'dfConfirm';
          m.className = 'modal fade';
          m.innerHTML = '<div class="modal-dialog"><div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
            '<div class="modal-body"></div>' +
            '<div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>' +
            '<button class="btn btn-purple" data-df-yes>Confirm</button></div></div></div>';
          document.body.appendChild(m);
        }
        m.querySelector('.modal-title').textContent = title;
        m.querySelector('.modal-body').textContent = body;
        const modal = new bootstrap.Modal(m);
        const yes = m.querySelector('[data-df-yes]');
        const onYes = () => { cleanup(); resolve(true); };
        const onHide = () => { cleanup(); resolve(false); };
        function cleanup() {
          yes.removeEventListener('click', onYes);
          m.removeEventListener('hidden.bs.modal', onHide);
          modal.hide();
        }
        yes.addEventListener('click', onYes);
        m.addEventListener('hidden.bs.modal', onHide);
        modal.show();
      });
    },
    prompt(title, label, initial = '') {
      return new Promise((resolve) => {
        let m = document.getElementById('dfPrompt');
        if (!m) {
          m = document.createElement('div');
          m.id = 'dfPrompt';
          m.className = 'modal fade';
          m.innerHTML = '<div class="modal-dialog"><div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
            '<div class="modal-body"><label class="form-label"></label><input class="form-control"></div>' +
            '<div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>' +
            '<button class="btn btn-purple" data-df-ok>Save</button></div></div></div>';
          document.body.appendChild(m);
        }
        m.querySelector('.modal-title').textContent = title;
        m.querySelector('.form-label').textContent = label;
        const input = m.querySelector('input');
        input.value = initial;
        const modal = new bootstrap.Modal(m);
        const ok = m.querySelector('[data-df-ok]');
        const onOk = () => { const v = input.value.trim(); cleanup(); resolve(v || null); };
        const onHide = () => { cleanup(); resolve(null); };
        function cleanup() { ok.removeEventListener('click', onOk); m.removeEventListener('hidden.bs.modal', onHide); modal.hide(); }
        ok.addEventListener('click', onOk);
        m.addEventListener('hidden.bs.modal', onHide);
        modal.show();
        setTimeout(() => input.focus(), 250);
      });
    },
  };
  window.DF = DF;

  /* ---------------------------------------------------------- favourites */
  document.querySelectorAll('[data-df-fav]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      try {
        const r = await DF.api('api/documents/' + btn.dataset.dfFav + '/favorite', { method: 'POST', body: {} });
        btn.classList.toggle('btn-warning', r.favorite);
        btn.classList.toggle('btn-outline-secondary', !r.favorite);
        const icon = btn.querySelector('i');
        if (icon) icon.className = 'bi ' + (r.favorite ? 'bi-star-fill' : 'bi-star');
        DF.toast(r.favorite ? 'Added to favourites.' : 'Removed from favourites.');
      } catch (e) { DF.toast(e.message, 'error'); }
    });
  });

  /* --------------------------------------------------------------- stats */
  const statEls = document.querySelectorAll('[data-df-stat]');
  if (statEls.length) {
    DF.api('api/stats').then((r) => {
      statEls.forEach((el) => {
        const key = el.dataset.dfStat;
        if (r.stats && r.stats[key] !== undefined) el.textContent = r.stats[key];
      });
      document.querySelectorAll('[data-df-storagebar]').forEach((el) => { el.style.width = (r.stats.storage_pct || 0) + '%'; });
    }).catch(() => { /* leave server-rendered values */ });
  }

  /* -------------------------------------------------------------- charts */
  const palette = ['#7c3aed', '#1e3a5f', '#a78bfa', '#3b82c4', '#c4b5fd', '#f5b93f', '#15803d'];
  document.querySelectorAll('canvas[data-df-chart]').forEach((canvas) => {
    const type = canvas.dataset.dfChart;
    let chart = null;
    const load = async () => {
      const params = new URLSearchParams({ type });
      if (canvas.dataset.form) {
        const form = document.querySelector(canvas.dataset.form);
        if (form) new FormData(form).forEach((v, k) => { if (v) params.set(k, v); });
      }
      try {
        const r = await DF.api('api/charts?' + params.toString());
        const cfg = {
          labels: r.labels,
          datasets: [{
            label: r.unit || type, data: r.data,
            backgroundColor: (type === 'categories' || type === 'approvals') ? palette : 'rgba(124,58,237,.75)',
            borderColor: '#6d28d9', borderRadius: 6, tension: .3, fill: type === 'storage',
          }],
        };
        const kind = (type === 'categories' || type === 'approvals') ? 'doughnut' : (type === 'teams' ? 'bar' : (type === 'storage' || type === 'activity' ? 'line' : 'bar'));
        if (chart) { chart.data = cfg; chart.update(); }
        else chart = new Chart(canvas, { type: kind, data: cfg, options: { responsive: true, indexAxis: type === 'teams' ? 'y' : 'x', plugins: { legend: { display: kind === 'doughnut' } }, scales: kind === 'doughnut' ? {} : { y: { beginAtZero: true } } } });
      } catch (e) { /* chart stays empty; page content still server-rendered */ }
    };
    load();
    if (canvas.dataset.form) {
      const form = document.querySelector(canvas.dataset.form);
      if (form) form.addEventListener('change', load);
    }
  });

  /* ------------------------------------------------- version comparison */
  document.querySelectorAll('[data-df-vcompare]').forEach((wrap) => {
    const a = wrap.querySelector('[data-df-vcompare-a]'), b = wrap.querySelector('[data-df-vcompare-b]');
    if (!a || !b) return;
    const render = () => {
      const read = (sel) => { const o = sel.selectedOptions[0]; return o ? o.dataset : {}; };
      const da = read(a), db = read(b);
      const set = (k, v) => { const el = wrap.querySelector('[data-df-vc="' + k + '"]'); if (el) el.textContent = v ?? '—'; };
      set('a-size', da.size); set('b-size', db.size);
      set('a-by', da.by); set('b-by', db.by);
      set('a-date', da.date); set('b-date', db.date);
      set('a-note', da.note); set('b-note', db.note);
      wrap.querySelectorAll('[data-df-vrow]').forEach((row) => {
        const k = row.dataset.dfVrow;
        row.classList.toggle('table-warning', da[k] !== db[k]);
      });
    };
    a.addEventListener('change', render);
    b.addEventListener('change', render);
    render();
  });

  /* --------------------------------------------------------- file manager */
  document.querySelectorAll('[data-df-filemanager]').forEach((root) => new FileManager(root));

  function FileManager(root) {
    const state = {
      folder: root.dataset.folder ? parseInt(root.dataset.folder, 10) : null,
      view: localStorage.getItem('df_view') || 'grid',
      docs: [], selected: new Set(),
      status: 'active', shareDoc: null,
    };
    const $ = (sel) => root.querySelector(sel);
    const docsBox = $('[data-df-docs]'), foldersBox = $('[data-df-folders]'),
      crumb = $('[data-df-crumb]'), bulkbar = $('[data-df-bulkbar]'), emptyBox = $('[data-df-empty]');

    const filters = () => ({
      q: $('[data-df-search]').value.trim(),
      status: state.status,
      category: $('[data-df-category]').value, tag: $('[data-df-tag]').value,
      department: $('[data-df-department]').value, sort: $('[data-df-sort]').value,
    });
    const globalMode = () => { const f = filters(); return f.q !== '' || f.status !== 'active' || f.category || f.tag || f.department; };

    async function load() {
      const f = filters();
      const dp = new URLSearchParams();
      Object.entries(f).forEach(([k, v]) => { if (v) dp.set(k, v); });
      if (!globalMode()) dp.set('folder', state.folder === null ? 'root' : state.folder);
      try {
        const [docsRes, foldRes] = await Promise.all([
          DF.api('api/documents?' + dp.toString()),
          DF.api('api/folders' + (state.folder !== null ? '?folder=' + state.folder : '')),
        ]);
        state.docs = docsRes.documents;
        state.selected.clear();
        renderCrumb(foldRes.breadcrumb || []);
        renderFolders(globalMode() ? [] : (foldRes.folders || []));
        renderDocs();
      } catch (e) { DF.toast(e.message, 'error'); }
    }

    function renderCrumb(chain) {
      if (globalMode()) { crumb.innerHTML = '<li class="breadcrumb-item active">Search results across the workspace</li>'; return; }
      let html = '<li class="breadcrumb-item"><a href="#" data-df-goto="">Root</a></li>';
      chain.forEach((f, i) => {
        html += i === chain.length - 1
          ? '<li class="breadcrumb-item active">' + esc(f.name) + '</li>'
          : '<li class="breadcrumb-item"><a href="#" data-df-goto="' + f.id + '">' + esc(f.name) + '</a></li>';
      });
      crumb.innerHTML = html;
      crumb.querySelectorAll('[data-df-goto]').forEach((a) => a.addEventListener('click', (ev) => {
        ev.preventDefault();
        state.folder = a.dataset.dfGoto ? parseInt(a.dataset.dfGoto, 10) : null;
        load();
      }));
    }

    function renderFolders(folders) {
      foldersBox.innerHTML = folders.map((f) =>
        '<div class="col-6 col-md-4 col-xl-3"><div class="folder-card h-100" data-df-openfolder="' + f.id + '">' +
        '<i class="bi bi-folder-fill" style="color:' + esc(f.color || '#f5b93f') + ';font-size:1.8rem"></i>' +
        '<div class="fw-semibold mt-1 text-truncate">' + esc(f.name) + '</div>' +
        '<div class="small text-muted">' + (f.doc_count || 0) + ' files · ' + (f.sub_count || 0) + ' subfolders</div>' +
        '</div></div>').join('');
      foldersBox.querySelectorAll('[data-df-openfolder]').forEach((el) =>
        el.addEventListener('click', () => { state.folder = parseInt(el.dataset.dfOpenfolder, 10); load(); }));
    }

    function badges(d) {
      let h = '';
      if (d.status === 'archived') h += '<span class="badge text-bg-secondary">Archived</span> ';
      if (d.expires_at) h += '<span class="badge ' + (d.expires_at < new Date().toISOString().slice(0, 10) ? 'text-bg-danger' : 'text-bg-warning') + '">Expires ' + esc(d.expires_at) + '</span> ';
      if (d.deleted_at) h += '<span class="badge text-bg-danger">In trash</span> ';
      return h;
    }

    function actionsMenu(d) {
      const inTrash = state.status === 'trash';
      let items = '<li><a class="dropdown-item" href="' + esc(d.url) + '"><i class="bi bi-box-arrow-up-right me-2"></i>Open</a></li>';
      if (d.can_download && !inTrash) items += '<li><a class="dropdown-item" href="' + esc(d.download_url) + '"><i class="bi bi-download me-2"></i>Download</a></li>';
      if (!inTrash) {
        items += '<li><button class="dropdown-item" data-act="rename"><i class="bi bi-pencil me-2"></i>Rename</button></li>';
        items += '<li><button class="dropdown-item" data-act="share"><i class="bi bi-link-45deg me-2"></i>Share link</button></li>';
        items += '<li><button class="dropdown-item" data-act="' + (d.status === 'archived' ? 'unarchive' : 'archive') + '"><i class="bi bi-archive me-2"></i>' + (d.status === 'archived' ? 'Unarchive' : 'Archive') + '</button></li>';
        items += '<li><hr class="dropdown-divider"></li><li><button class="dropdown-item text-danger" data-act="delete"><i class="bi bi-trash me-2"></i>Move to trash</button></li>';
      } else {
        items += '<li><button class="dropdown-item" data-act="restore"><i class="bi bi-arrow-counterclockwise me-2"></i>Restore</button></li>';
        items += '<li><button class="dropdown-item text-danger" data-act="purge"><i class="bi bi-trash3 me-2"></i>Purge permanently</button></li>';
      }
      return '<div class="dropdown"><button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>' +
        '<ul class="dropdown-menu dropdown-menu-end" data-doc-menu="' + d.id + '">' + items + '</ul></div>';
    }

    function renderDocs() {
      syncBulkbar();
      emptyBox.classList.toggle('d-none', state.docs.length > 0);
      root.querySelectorAll('[data-df-view]').forEach((b) => b.classList.toggle('active', b.dataset.dfView === state.view));
      if (state.view === 'list') {
        docsBox.innerHTML = '<div class="panel p-0 overflow-hidden"><table class="table table-hover align-middle mb-0"><thead><tr>' +
          '<th style="width:36px"><input type="checkbox" class="form-check-input" data-df-checkall></th><th>Name</th><th>Category</th><th>Owner</th><th>Size</th><th>Downloads</th><th>Updated</th><th></th></tr></thead><tbody>' +
          state.docs.map((d) =>
            '<tr><td><input type="checkbox" class="form-check-input" data-df-check="' + d.id + '"' + (state.selected.has(d.id) ? ' checked' : '') + '></td>' +
            '<td><i class="bi ' + esc(d.icon) + ' me-2"></i><a href="' + esc(d.url) + '">' + esc(d.name) + '</a>.<span class="text-muted">' + esc(d.extension) + '</span> ' + badges(d) + '</td>' +
            '<td>' + esc(d.category_name || '—') + '</td><td>' + esc(d.owner_name || '') + '</td><td>' + esc(d.size) + '</td>' +
            '<td>' + d.downloads + '</td><td>' + esc(String(d.updated_at).slice(0, 10)) + '</td>' +
            '<td class="text-end"><button class="btn btn-sm ' + (d.favorite ? 'btn-warning' : 'btn-light') + '" data-df-favrow="' + d.id + '"><i class="bi ' + (d.favorite ? 'bi-star-fill' : 'bi-star') + '"></i></button> ' + actionsMenu(d) + '</td></tr>').join('') +
          '</tbody></table></div>';
        const all = docsBox.querySelector('[data-df-checkall]');
        if (all) all.addEventListener('change', () => {
          state.docs.forEach((d) => all.checked ? state.selected.add(d.id) : state.selected.delete(d.id));
          renderDocs();
        });
      } else {
        docsBox.innerHTML = '<div class="row g-3">' + state.docs.map((d) =>
          '<div class="col-6 col-md-4 col-xl-3"><div class="doc-card h-100 position-relative">' +
          '<input type="checkbox" class="form-check-input position-absolute" style="top:10px;left:10px" data-df-check="' + d.id + '"' + (state.selected.has(d.id) ? ' checked' : '') + '>' +
          '<button class="btn btn-sm position-absolute ' + (d.favorite ? 'text-warning' : 'text-muted') + '" style="top:2px;right:34px" data-df-favrow="' + d.id + '"><i class="bi ' + (d.favorite ? 'bi-star-fill' : 'bi-star') + '"></i></button>' +
          '<div class="position-absolute" style="top:4px;right:2px">' + actionsMenu(d) + '</div>' +
          '<a href="' + esc(d.url) + '" class="text-decoration-none text-dark"><i class="bi ' + esc(d.icon) + '" style="font-size:2.2rem"></i>' +
          '<div class="fw-semibold mt-2 text-truncate">' + esc(d.name) + '.<span class="text-muted">' + esc(d.extension) + '</span></div></a>' +
          '<div class="small text-muted">' + esc(d.size) + ' · ' + esc(d.owner_name || '') + '<br>' + esc(d.category_name || 'Uncategorised') + ' · ' + d.downloads + ' downloads</div>' +
          '<div class="mt-1">' + badges(d) + '</div>' +
          '</div></div>').join('') + '</div>';
      }
      docsBox.querySelectorAll('[data-df-check]').forEach((c) => c.addEventListener('change', () => {
        const id = parseInt(c.dataset.dfCheck, 10);
        c.checked ? state.selected.add(id) : state.selected.delete(id);
        syncBulkbar();
      }));
      docsBox.querySelectorAll('[data-df-favrow]').forEach((b) => b.addEventListener('click', async () => {
        try { await DF.api('api/documents/' + b.dataset.dfFavrow + '/favorite', { method: 'POST', body: {} }); load(); }
        catch (e) { DF.toast(e.message, 'error'); }
      }));
      docsBox.querySelectorAll('[data-doc-menu]').forEach((menu) => {
        const id = parseInt(menu.dataset.docMenu, 10);
        menu.querySelectorAll('[data-act]').forEach((btn) => btn.addEventListener('click', () => docAction(btn.dataset.act, id)));
      });
    }

    function syncBulkbar() {
      const n = state.selected.size;
      bulkbar.classList.toggle('d-none', n === 0);
      const count = bulkbar.querySelector('[data-df-selcount]');
      if (count) count.textContent = n;
      const inTrash = state.status === 'trash';
      bulkbar.querySelector('[data-df-bulk="purge"]').classList.toggle('d-none', !inTrash);
      ['move', 'share', 'archive', 'favorite'].forEach((a) => {
        const b = bulkbar.querySelector('[data-df-bulk="' + a + '"]');
        if (b) b.classList.toggle('d-none', inTrash);
      });
      const del = bulkbar.querySelector('[data-df-bulk="delete"]');
      if (del) del.classList.toggle('d-none', inTrash);
      const res = bulkbar.querySelector('[data-df-bulk="restore"]');
      if (res) res.classList.toggle('d-none', !inTrash);
    }

    async function bulk(action, extra = {}) {
      const ids = [...state.selected];
      if (!ids.length) return;
      if ((action === 'delete' || action === 'purge') &&
        !(await DF.confirm(action === 'purge' ? 'Purge permanently?' : 'Move to trash?',
          action === 'purge' ? 'These files will be permanently deleted. This cannot be undone.' : 'Move ' + ids.length + ' document(s) to trash?'))) return;
      try {
        const r = await DF.api('api/documents/bulk', { method: 'POST', body: { action, ids, ...extra } });
        DF.toast(r.message + (r.errors && r.errors.length ? ' ' + r.errors.join('; ') : ''), r.errors && r.errors.length ? 'warning' : 'success');
        load();
      } catch (e) { DF.toast(e.message, 'error'); }
    }

    async function docAction(act, id) {
      const doc = state.docs.find((d) => d.id === id);
      if (act === 'rename') {
        const name = await DF.prompt('Rename document', 'New name', doc ? doc.name : '');
        if (!name) return;
        try { await DF.api('api/documents/' + id + '/rename', { method: 'POST', body: { name } }); DF.toast('Renamed.'); load(); }
        catch (e) { DF.toast(e.message, 'error'); }
      } else if (act === 'share') {
        state.selected = new Set([id]);
        openShare();
      } else if (act === 'archive' || act === 'unarchive' || act === 'restore') {
        state.selected = new Set([id]);
        bulk(act);
      } else if (act === 'delete' || act === 'purge') {
        state.selected = new Set([id]);
        bulk(act);
      }
    }

    function openShare() {
      if (state.selected.size !== 1) { DF.toast('Select exactly one document to create a share link.', 'warning'); return; }
      state.shareDoc = [...state.selected][0];
      const res = document.querySelector('[data-df-share-result]');
      if (res) res.classList.add('d-none');
      new bootstrap.Modal(document.getElementById('dfShareModal')).show();
    }

    /* bulk bar buttons */
    bulkbar.querySelectorAll('[data-df-bulk]').forEach((b) => b.addEventListener('click', () => {
      const a = b.dataset.dfBulk;
      if (a === 'move') new bootstrap.Modal(document.getElementById('dfMoveModal')).show();
      else if (a === 'share') openShare();
      else if (a === 'download') {
        let opened = 0;
        state.docs.filter((d) => state.selected.has(d.id)).forEach((d, i) => {
          if (d.can_download) { setTimeout(() => window.open(d.download_url, '_blank'), i * 350); opened++; }
        });
        DF.toast(opened ? 'Downloading ' + opened + ' file(s)…' : 'None of the selected files allow downloads.', opened ? 'success' : 'warning');
      } else bulk(a);
    }));
    const clearBtn = bulkbar.querySelector('[data-df-clear]');
    if (clearBtn) clearBtn.addEventListener('click', () => { state.selected.clear(); renderDocs(); });
    const moveBtn = document.querySelector('[data-df-move-confirm]');
    if (moveBtn) moveBtn.addEventListener('click', () => {
      const target = document.querySelector('[data-df-move-target]').value;
      bootstrap.Modal.getInstance(document.getElementById('dfMoveModal')).hide();
      bulk('move', { folder_id: target ? parseInt(target, 10) : null });
    });
    const shareBtn = document.querySelector('[data-df-share-create]');
    if (shareBtn) shareBtn.addEventListener('click', async () => {
      try {
        const r = await DF.api('api/documents/' + state.shareDoc + '/share', {
          method: 'POST',
          body: {
            days: document.querySelector('[data-df-share-days]').value,
            password: document.querySelector('[data-df-share-password]').value,
            can_download: document.querySelector('[data-df-share-download]').checked ? 1 : 0,
          },
        });
        const box = document.querySelector('[data-df-share-result]');
        box.classList.remove('d-none');
        box.querySelector('[data-df-share-url]').value = r.url;
        DF.toast('Share link created' + (r.password_protected ? ' (password-protected)' : '') + '.');
      } catch (e) { DF.toast(e.message, 'error'); }
    });
    const copyBtn = document.querySelector('[data-df-share-copy]');
    if (copyBtn) copyBtn.addEventListener('click', () => {
      const input = document.querySelector('[data-df-share-url]');
      input.select();
      navigator.clipboard && navigator.clipboard.writeText(input.value);
      DF.toast('Link copied.');
    });

    /* folder create */
    const folderBtn = document.querySelector('[data-df-folder-create]');
    if (folderBtn) folderBtn.addEventListener('click', async () => {
      const name = document.querySelector('[data-df-folder-name]').value.trim();
      if (!name) { DF.toast('Folder name is required.', 'warning'); return; }
      try {
        await DF.api('api/folders', { method: 'POST', body: { name, color: document.querySelector('[data-df-folder-color]').value, parent_id: state.folder } });
        bootstrap.Modal.getInstance(document.getElementById('dfFolderModal')).hide();
        DF.toast('Folder created.');
        load();
      } catch (e) { DF.toast(e.message, 'error'); }
    });

    /* filters / search / view */
    let debounce = null;
    $('[data-df-search]').addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(load, 300); });
    ['[data-df-category]', '[data-df-tag]', '[data-df-department]', '[data-df-sort]'].forEach((sel) => {
      const el = $(sel); if (el) el.addEventListener('change', load);
    });
    $('[data-df-status]').addEventListener('change', (e) => { state.status = e.target.value; load(); });
    root.querySelectorAll('[data-df-view]').forEach((b) => b.addEventListener('click', () => {
      state.view = b.dataset.dfView;
      localStorage.setItem('df_view', state.view);
      renderDocs();
    }));

    /* uploads: input + drag & drop with per-file progress */
    const uploadsBox = $('[data-df-uploads]');
    function uploadFiles(fileList) {
      const files = [...fileList];
      if (!files.length) return;
      uploadsBox.classList.remove('d-none');
      let done = 0, failed = 0;
      files.forEach((file) => {
        const row = document.createElement('div');
        row.className = 'df-upload-row';
        row.innerHTML = '<span class="text-truncate">' + esc(file.name) + '</span>' +
          '<div class="progress flex-grow-1"><div class="progress-bar bg-purple" style="width:0%"></div></div><span class="small text-muted df-up-state">0%</span>';
        uploadsBox.appendChild(row);
        const bar = row.querySelector('.progress-bar'), label = row.querySelector('.df-up-state');
        const fd = new FormData();
        fd.append('files[]', file);
        if (state.folder !== null && !globalMode()) fd.append('folder_id', state.folder);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', DF.url('api/upload'));
        xhr.setRequestHeader('X-CSRF-Token', csrf);
        xhr.upload.addEventListener('progress', (ev) => {
          if (ev.lengthComputable) { const p = Math.round(ev.loaded / ev.total * 100); bar.style.width = p + '%'; label.textContent = p + '%'; }
        });
        xhr.addEventListener('load', () => {
          done++;
          let ok = false;
          try { const r = JSON.parse(xhr.responseText); ok = r.ok && r.uploaded && r.uploaded.length > 0; if (!ok && r.errors) label.textContent = r.errors.join(' '); } catch (e) { /* ignore */ }
          if (ok) { bar.style.width = '100%'; label.textContent = 'Done'; }
          else { failed++; row.classList.add('df-upload-error'); if (label.textContent === '100%' || label.textContent.endsWith('%')) label.textContent = 'Failed'; }
          if (done === files.length) {
            DF.toast(failed ? (done - failed) + ' uploaded, ' + failed + ' failed.' : done + ' file(s) uploaded.', failed ? 'warning' : 'success');
            setTimeout(() => { uploadsBox.classList.add('d-none'); uploadsBox.innerHTML = ''; }, 4000);
            load();
          }
        });
        xhr.addEventListener('error', () => { done++; failed++; label.textContent = 'Network error'; if (done === files.length) load(); });
        xhr.send(fd);
      });
    }
    const fileInput = $('[data-df-fileinput]');
    if (fileInput) fileInput.addEventListener('change', () => { uploadFiles(fileInput.files); fileInput.value = ''; });
    const dz = $('[data-df-dropzone]'), overlay = $('[data-df-dropoverlay]');
    let dragCount = 0;
    ['dragenter', 'dragover'].forEach((ev) => root.addEventListener(ev, (e) => { e.preventDefault(); dragCount++; overlay.classList.remove('d-none'); }));
    root.addEventListener('dragleave', (e) => { e.preventDefault(); if (--dragCount <= 0) { dragCount = 0; overlay.classList.add('d-none'); } });
    root.addEventListener('drop', (e) => {
      e.preventDefault(); dragCount = 0; overlay.classList.add('d-none');
      if (e.dataTransfer && e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
    });

    load();
  }
})();
