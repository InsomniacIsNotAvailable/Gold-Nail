// Party Mode: YouTube link play button + inline player for AppointmentDetailsModal.
// Usage: include this file after AppointmentDetailsModal.js to enable.

(function () {
  function getBaseFromAdminCss() {
    const admin = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
      .find(l => /\/Admin\.css(?:\?|$)/i.test(l.getAttribute('href') || ''));
    if (!admin) return '';
    const url = new URL(admin.href, document.location.href);
    const path = url.pathname.replace(/\/Admin\.css.*$/i, '');
    return `${url.origin}${path}`;
  }

  function ensureRgbCss() {
    if (document.querySelector('link[data-rgb-borders]')) return;
    const base = getBaseFromAdminCss();
    const href = `${base}/mods/ui/rgb-borders.css?v=1`;
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    link.setAttribute('data-rgb-borders', 'true');
    document.head.appendChild(link);
  }

  function extractYoutubeUrl(text) {
    if (!text) return null;
    const str = String(text);
    const short = str.match(/\bhttps?:\/\/(?:www\.)?youtu\.be\/([A-Za-z0-9_-]{6,})\b/);
    if (short) return `https://www.youtube.com/embed/${short[1]}`;
    const watch = str.match(/\bhttps?:\/\/(?:www\.)?(?:music\.)?youtube\.com\/watch\?[^ \n"]+/);
    if (watch) {
      const u = new URL(watch[0]);
      const v = u.searchParams.get('v');
      if (v) return `https://www.youtube.com/embed/${v}`;
    }
    const vparam = str.match(/[?&]v=([A-Za-z0-9_-]{6,})/);
    if (vparam) return `https://www.youtube.com/embed/${vparam[1]}`;
    return null;
  }

  function withAutoplay(url) {
    if (!url) return url;
    const hasQ = url.includes('?');
    const sep = hasQ ? '&' : '?';
    return `${url}${sep}autoplay=1&playsinline=1&rel=0&modestbranding=1`;
  }

  function linkifyFirstUrlIn(el, url) {
    if (!el || !url) return;
    let watchUrl = url.replace('https://www.youtube.com/embed/', 'https://www.youtube.com/watch?v=');
    const text = el.textContent || '';
    el.replaceChildren();
    const span = document.createElement('span');
    span.textContent = text.trim();
    const a = document.createElement('a');
    a.href = watchUrl;
    a.textContent = ' (Open)';
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
    a.style.marginLeft = '6px';
    el.appendChild(span);
    el.appendChild(a);
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

  function setRgbPlaying(active) {
    if (active) {
      ensureRgbCss();
      document.body.classList.add('rgb-playing');
    } else {
      document.body.classList.remove('rgb-playing');
    }
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

  // Build an expandable row (colspan across all columns)
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

  // Toggle inline player using a full-width expand row
  function toggleRowPlayer(rowEl, embedUrl) {
    if (!rowEl) return;
    const next = rowEl.nextElementSibling;
    const btn = rowEl.querySelector('.yt-inline-play');

    if (next && next.classList.contains('yt-expand')) {
      next.remove();
      rowEl.classList.remove('playing');
      btn && btn.setAttribute('aria-pressed', 'false');
      if (!document.querySelector('.concerns-table tr.playing') &&
          !document.getElementById('appointmentDetailsModal')?.classList.contains('playing')) {
        setRgbPlaying(false);
      }
      return;
    }

    const expander = buildExpandRow(rowEl, embedUrl);
    rowEl.after(expander);

    ensureRgbCss();
    setRgbPlaying(true);
    rowEl.classList.add('playing');
    btn && btn.setAttribute('aria-pressed', 'true');
  }

  function toggleModalPlayer(modalOverlay, embedUrl) {
    if (!modalOverlay) return;
    let wrap = modalOverlay.querySelector('.yt-embed-wrap');
    if (wrap) {
      wrap.remove();
      modalOverlay.classList.remove('playing');
      if (!document.querySelector('.concerns-table tr.playing')) {
        setRgbPlaying(false);
      }
      const chip = modalOverlay.querySelector('.yt-chip');
      chip && chip.setAttribute('aria-pressed', 'false');
      return;
    }
    wrap = document.createElement('div');
    wrap.className = 'yt-embed-wrap';
    const player = createPlayerIframe(embedUrl);
    wrap.appendChild(player);

    const actions = modalOverlay.querySelector('.modal-actions');
    const before = actions || modalOverlay.querySelector('.modal-content');
    if (before) {
      before.parentNode.insertBefore(wrap, before);
    } else {
      modalOverlay.appendChild(wrap);
    }

    ensureRgbCss();
    setRgbPlaying(true);
    modalOverlay.classList.add('playing');
    const chip = modalOverlay.querySelector('.yt-chip');
    chip && chip.setAttribute('aria-pressed', 'true');
  }

  function addInlinePlayBtnToRow(rowEl, embedUrl) {
    if (!rowEl || !embedUrl) return;
    const customerCell = rowEl.querySelector('.customer-cell') || rowEl.querySelector('td:nth-child(3)') || rowEl.cells?.[2];
    if (!customerCell) return;
    if (customerCell.querySelector('.yt-inline-play')) return;

    const btn = createInlinePlayBtn();
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleRowPlayer(rowEl, embedUrl);
    });

    const nameEl = customerCell.querySelector('.customer-name');
    if (nameEl && nameEl.nextSibling) {
      nameEl.parentNode.insertBefore(btn, nameEl.nextSibling);
    } else if (nameEl) {
      nameEl.parentNode.appendChild(btn);
    } else {
      customerCell.appendChild(btn);
    }
  }

  function patchDetailsModal() {
    const Cls = window.AppointmentDetailsModal;
    if (!Cls || Cls.__partyPatched) return;
    const openOrig = Cls.prototype.open;
    const closeOrig = Cls.prototype.close;

    Cls.prototype.open = function (data) {
      openOrig.call(this, data || {});
      try {
        const overlay = this.overlay;
        if (!overlay) return;
        const refs = this.refs || {};
        const messageEl = refs.message;

        const msg = (data && data.message) ? String(data.message) : (messageEl?.textContent || '');
        const embedUrl = extractYoutubeUrl(msg);

        if (embedUrl && messageEl) linkifyFirstUrlIn(messageEl, embedUrl);

        let chip = overlay.querySelector('.yt-chip');
        if (embedUrl) {
          if (!chip) {
            chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'yt-chip';
            chip.textContent = 'Play song';
            chip.setAttribute('aria-pressed', 'false');
            chip.addEventListener('click', (e) => {
              e.stopPropagation();
              toggleModalPlayer(overlay, embedUrl);
            });
            const msgGroup = messageEl?.closest('.form-group') || overlay.querySelector('.modal-content');
            msgGroup && msgGroup.parentNode.insertBefore(chip, msgGroup.nextSibling);
          } else {
            const newChip = chip.cloneNode(true);
            chip.replaceWith(newChip);
            newChip.addEventListener('click', () => toggleModalPlayer(overlay, embedUrl));
          }
        } else if (chip) {
          chip.remove();
        }

        const id = Number(data && data.id);
        if (!Number.isNaN(id)) {
          const rowEl = document.querySelector(`tbody#appointmentsTableBody tr[data-id="${id}"]`);
          if (embedUrl && rowEl) addInlinePlayBtnToRow(rowEl, embedUrl);
        }
      } catch (e) {
        console.error('PartyMode (open) failed:', e);
      }
    };

    Cls.prototype.close = function () {
      try {
        const overlay = this.overlay;
        if (overlay) {
          const wrap = overlay.querySelector('.yt-embed-wrap');
          if (wrap) wrap.remove();
          overlay.classList.remove('playing');
          if (!document.querySelector('.concerns-table tr.playing')) {
            setRgbPlaying(false);
          }
        }
      } catch (e) {
        console.error('PartyMode (close) cleanup failed:', e);
      }
      return closeOrig.call(this);
    };

    Cls.__partyPatched = true;
  }

  const processedRows = new WeakSet();

  async function enhanceRow(rowEl) {
    if (!rowEl || processedRows.has(rowEl)) return;
    const id = Number(rowEl.dataset.id);
    if (!id) return;
    try {
      const data = await window.AppointmentsApi?.get?.(id);
      const url = extractYoutubeUrl(data?.message || '');
      if (url) addInlinePlayBtnToRow(rowEl, url);
    } catch (_) {
    } finally {
      processedRows.add(rowEl);
    }
  }

  function enhanceExistingRows() {
    const rows = document.querySelectorAll('#appointmentsTableBody tr');
    rows.forEach(enhanceRow);
  }

  function observeTableForNewRows() {
    const tbody = document.getElementById('appointmentsTableBody');
    if (!tbody) return;
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        m.addedNodes.forEach((node) => {
          if (node.nodeType !== 1) return;
          if (node.matches && node.matches('tr')) {
            enhanceRow(node);
          } else {
            node.querySelectorAll?.('tr').forEach(enhanceRow);
          }
        });
      }
    });
    mo.observe(tbody, { childList: true, subtree: false });
  }

  function interceptDetailsClickForRowChip() {
    const table = document.getElementById('appointmentsTableBody');
    if (!table || table.__partyHooked) return;
    table.addEventListener('click', async (e) => {
      const btn = e.target.closest('.details-btn');
      if (!btn) return;
      const row = e.target.closest('tr');
      const id = Number(row?.dataset?.id);
      if (!row || Number.isNaN(id)) return;
      try {
        const data = await window.AppointmentsApi?.get?.(id);
        const url = extractYoutubeUrl(data?.message || '');
        if (url) addInlinePlayBtnToRow(row, url);
      } catch (_) {}
    }, { passive: true });
    table.__partyHooked = true;
  }

  document.addEventListener('DOMContentLoaded', () => {
    patchDetailsModal();
    enhanceExistingRows();
    observeTableForNewRows();
    interceptDetailsClickForRowChip();
  });
})();