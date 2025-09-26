(function () {
  function $(sel, root = document) { return root.querySelector(sel); }
  function $all(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

  function getTableContext() {
    const tbody = document.getElementById('appointmentsTableBody');
    if (!tbody) return null;
    const table = tbody.closest('table');
    const thead = table ? $('thead', table) : null;
    return { table, thead, tbody };
  }

  function extractRowModel(tr) {
    // Columns: Date | Time | Name(.customer-name) | Purpose | Status(badge) | Actions
    const cells = tr.cells;
    const dateText = (cells[0]?.textContent || '').trim();
    const timeText = (cells[1]?.textContent || '').trim();
    const nameText = (cells[2]?.querySelector('.customer-name')?.textContent || cells[2]?.textContent || '').trim();
    const purposeText = (cells[3]?.textContent || '').trim();
    const statusText = (cells[4]?.textContent || '').trim();
    const id = Number(tr.dataset.id || NaN);

    // Prefer an explicit timestamp if provided, else parse date+time
    let ts = null;
    if (tr.dataset.ts) {
      const n = Number(tr.dataset.ts);
      if (!Number.isNaN(n)) ts = n;
    }
    if (ts == null) {
      const parsed = Date.parse(`${dateText} ${timeText}`);
      ts = Number.isNaN(parsed) ? null : parsed;
    }

    return {
      id: Number.isNaN(id) ? null : id,
      dateText, timeText, nameText, purposeText, statusText,
      ts,
      html: tr.outerHTML
    };
  }

  // Only snapshot real data rows; ignore ephemeral expand rows and any non-data rows
  function snapshotRows(tbody) {
    const candidates = $all('tr', tbody)
      .filter(tr => !tr.classList.contains('yt-expand'))
      .filter(tr => tr.hasAttribute('data-id') || tr.cells.length >= 5);
    return candidates.map(extractRowModel);
  }

  function renderRows(tbody, rows, guard) {
    guard.suppress = true;
    tbody.innerHTML = rows.map(r => r.html).join('');
    setTimeout(() => { guard.suppress = false; }, 0);
  }

  function computeStatuses(rows) {
    const order = { 'New': 1, 'Confirmed': 2, 'Completed': 3, 'Cancelled': 4 };
    const s = new Set();
    rows.forEach(r => { if (r.statusText) s.add(r.statusText); });
    return Array.from(s).sort((a, b) => {
      const ra = order[a] ?? 999, rb = order[b] ?? 999;
      return ra === rb ? a.localeCompare(b) : ra - rb;
    });
  }

  // RGB helpers
  function setRgbPlaying(active) {
    if (active) document.body.classList.add('rgb-playing');
    else document.body.classList.remove('rgb-playing');
  }

  // Minimal YT helpers
  function extractYoutubeUrl(text) {
    if (!text) return null;
    const str = String(text);
    const short = str.match(/\bhttps?:\/\/(?:www\.)?youtu\.be\/([A-Za-z0-9_-]{6,})\b/);
    if (short) return `https://www.youtube.com/embed/${short[1]}`;
    const watch = str.match(/\bhttps?:\/\/(?:www\.)?(?:music\.)?youtube\.com\/watch\?[^ \n"]+/);
    if (watch) {
      try {
        const u = new URL(watch[0]);
        const v = u.searchParams.get('v');
        if (v) return `https://www.youtube.com/embed/${v}`;
      } catch {}
    }
    const vparam = str.match(/[?&]v=([A-Za-z0-9_-]{6,})/);
    if (vparam) return `https://www.youtube.com/embed/${vparam[1]}`;
    return null;
  }
  function withAutoplay(url) {
    if (!url) return url;
    const sep = url.includes('?') ? '&' : '?';
    return `${url}${sep}autoplay=1&playsinline=1&rel=0&modestbranding=1`;
  }
  function createPlayerIframe(embedUrl) {
    const iframe = document.createElement('iframe');
    iframe.className = 'yt-player';
    iframe.width = '560';
    iframe.height = '315';
    iframe.src = withAutoplay(embedUrl);
    iframe.title = 'YouTube player';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    iframe.allowFullscreen = true;
    return iframe;
  }
  function buildExpandRow(parentRow, embedUrl) {
    const tr = document.createElement('tr');
    tr.className = 'yt-expand';
    tr.dataset.parentId = parentRow.dataset.id || '';
    const colCount = parentRow.cells.length || 6;
    const td = document.createElement('td');
    td.colSpan = colCount;
    const wrap = document.createElement('div');
    wrap.className = 'yt-inline';
    wrap.appendChild(createPlayerIframe(embedUrl));
    td.appendChild(wrap);
    tr.appendChild(td);
    return tr;
  }
  function toggleRowPlayer(rowEl, embedUrl) {
    if (!rowEl) return;
    const next = rowEl.nextElementSibling;
    const btn = rowEl.querySelector('.yt-inline-play');

    if (next && next.classList.contains('yt-expand')) {
      next.remove();
      rowEl.classList.remove('playing');
      btn && btn.setAttribute('aria-pressed', 'false');
      const anyRowPlaying = document.querySelector('.concerns-table tr.playing');
      const modalPlaying = document.getElementById('appointmentDetailsModal')?.classList.contains('playing');
      if (!anyRowPlaying && !modalPlaying) setRgbPlaying(false);
      return;
    }

    const expander = buildExpandRow(rowEl, embedUrl);
    rowEl.after(expander);
    setRgbPlaying(true);
    rowEl.classList.add('playing');
    btn && btn.setAttribute('aria-pressed', 'true');
  }
  function openRowPlayer(rowEl, embedUrl) {
    if (!rowEl) return;
    const next = rowEl.nextElementSibling;
    if (!(next && next.classList.contains('yt-expand'))) {
      const expander = buildExpandRow(rowEl, embedUrl);
      rowEl.after(expander);
    }
    rowEl.classList.add('playing');
    const btn = rowEl.querySelector('.yt-inline-play');
    btn && btn.setAttribute('aria-pressed', 'true');
    setRgbPlaying(true);
  }
  function createInlinePlayBtn() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'yt-inline-play';
    btn.setAttribute('aria-label', 'Play song');
    btn.setAttribute('aria-pressed', 'false');
    btn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>';
    return btn;
  }
  function addInlinePlayBtnToRow(rowEl, embedUrl) {
    if (!rowEl || !embedUrl) return;
    const customerCell = rowEl.querySelector('.customer-cell') || rowEl.querySelector('td:nth-child(3)') || rowEl.cells?.[2];
    if (!customerCell) return;
    if (customerCell.querySelector('.yt-inline-play')) return; // already present
    const btn = createInlinePlayBtn();
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleRowPlayer(rowEl, embedUrl);
    });
    const nameEl = customerCell.querySelector('.customer-name');
    if (nameEl && nameEl.nextSibling) nameEl.parentNode.insertBefore(btn, nameEl.nextSibling);
    else if (nameEl) nameEl.parentNode.appendChild(btn);
    else customerCell.appendChild(btn);
  }

  // Cache URLs to avoid repeated API calls
  const ytUrlCache = new Map(); // id -> embedUrl | null

  async function rehydrateInlinePlayButtons(tbody) {
    if (!window.AppointmentsApi?.get) return;
    const rows = $all('tr[data-id]', tbody);
    for (const tr of rows) {
      if (tr.querySelector('.yt-inline-play')) continue; // already present (e.g., PartyMode added)
      const id = Number(tr.dataset.id);
      if (!id) continue;
      let url = ytUrlCache.get(id);
      if (url === undefined) {
        try {
          const data = await window.AppointmentsApi.get(id);
          url = extractYoutubeUrl(data?.message || '');
        } catch {
          url = null;
        }
        ytUrlCache.set(id, url);
      }
      if (url) addInlinePlayBtnToRow(tr, url);
    }
  }

  // Track currently playing so we can restore it
  let playingState = { id: null, url: null };
  function capturePlayingState() {
    const row = document.querySelector('.concerns-table tr.playing');
    if (!row) { playingState = { id: null, url: null }; return; }
    const id = Number(row.dataset.id) || null;
    // Try get URL from existing iframe
    let url = null;
    const next = row.nextElementSibling;
    const iframe = (next && next.classList.contains('yt-expand')) ? next.querySelector('iframe.yt-player') : null;
    if (iframe?.src) url = iframe.src.split('?')[0]; // strip params
    if (!url && id && ytUrlCache.has(id)) url = ytUrlCache.get(id);
    playingState = { id, url };
  }

  // Close any open overlays that might intercept clicks
  function closeAnyOpenDropdowns() {
    document.querySelectorAll('.status-dropdown.open').forEach(el => {
      el.classList.remove('open');
      el.setAttribute('aria-hidden', 'true');
    });
  }

  // Tear down any inline players before re-rendering, and normalize RGB state
  function cleanupInlinePlayers() {
    document.querySelectorAll('.concerns-table tr.yt-expand').forEach(tr => tr.remove());
    document.querySelectorAll('.concerns-table tr.playing').forEach(tr => tr.classList.remove('playing'));
    const modalPlaying = document.getElementById('appointmentDetailsModal')?.classList.contains('playing');
    if (!modalPlaying) document.body.classList.remove('rgb-playing');
  }

  function init() {
    const ctx = getTableContext();
    if (!ctx || !ctx.tbody) return;

    const { table, thead, tbody } = ctx;
    let baseRows = snapshotRows(tbody);

    // Always sort by time by default; direction asc
    let current = { sort: { index: 0, dir: 1 }, filters: { status: '' } };

    async function apply(guard) {
      // 1) Capture current playing (if any)
      capturePlayingState();
      // 2) Close overlays and clean inline players
      closeAnyOpenDropdowns();
      cleanupInlinePlayers();

      // Filter by Status if set
      let rows = baseRows.filter(r => {
        if (current.filters.status) {
          if (r.statusText !== current.filters.status) return false;
        }
        return true;
      });

      // Always sort by timestamp; missing ts goes to the end
      rows = rows.slice().sort((a, b) => {
        const at = (a.ts == null ? Number.POSITIVE_INFINITY : a.ts);
        const bt = (b.ts == null ? Number.POSITIVE_INFINITY : b.ts);
        return (at - bt) * (current.sort.dir || 1);
      });

      renderRows(tbody, rows, guard);

      // After DOM is replaced, rehydrate play buttons and optionally restore the player
      setTimeout(async () => {
        await rehydrateInlinePlayButtons(tbody);
        if (playingState.id && playingState.url) {
          const rowEl = tbody.querySelector(`tr[data-id="${playingState.id}"]`);
          if (rowEl) {
            addInlinePlayBtnToRow(rowEl, playingState.url);
            openRowPlayer(rowEl, playingState.url);
          } else {
            // Row no longer visible with current filter; ensure dark mode off if no modal playing
            const modalPlaying = document.getElementById('appointmentDetailsModal')?.classList.contains('playing');
            if (!modalPlaying) setRgbPlaying(false);
          }
        }
      }, 0);
    }

    // Sorting via header click (Date/Time only toggles asc/desc)
    if (thead) {
      thead.addEventListener('click', (e) => {
        const th = e.target.closest('th');
        if (!th) return;
        const ths = $all('th', thead);
        const idx = ths.indexOf(th);
        if (idx < 0) return;

        if (idx === 0 || idx === 1) {
          current.sort.index = idx;
          current.sort.dir = (current.sort.dir || 1) * -1;
          apply(guard);
        }
      }, { passive: true });
    }

    // Filter button dropdown using StatusDropdown UI
    const section = table?.closest('.recent-concerns-section') || document;
    const filterBtn = $('#filterAppointmentsBtn', section);

    let statusMenu = null;
    const guard = { suppress: false };

    if (filterBtn && window.StatusDropdown) {
      filterBtn.addEventListener('click', (e) => {
        e.stopPropagation();

        const statuses = computeStatuses(baseRows);
        const items = ['All statuses', ...statuses];

        if (!statusMenu) {
          statusMenu = new window.StatusDropdown({ statuses: items });
        } else {
          statusMenu.statuses = items; // refresh options each open
        }

        const currentLabel = current.filters.status || 'All statuses';
        statusMenu.open(filterBtn, currentLabel, (selected) => {
          const val = selected === 'All statuses' ? '' : selected;
          current.filters.status = val;

          filterBtn.textContent = val || 'Filter';
          apply(guard);
        });
      });
    }

    // Keep baseRows in sync with external changes (new/deleted rows), ignoring our own renders
    const mo = new MutationObserver(() => {
      if (guard.suppress) return;
      baseRows = snapshotRows(tbody);
      // New rows may need buttons
      rehydrateInlinePlayButtons(tbody);
    });
    mo.observe(tbody, { childList: true });

    // Initial pass
    apply(guard);
  }

  document.addEventListener('DOMContentLoaded', init);
})();