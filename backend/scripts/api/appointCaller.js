// Simple browser client for backend/api/appointCRUD.php
// Derives base prefix from this script's src, works under subfolders like /Gold%20Nail.
(function () {
  // Allow manual override if needed: window.APP_BASE_PREFIX = '/Gold%20Nail';
  let BASE_PREFIX = typeof window.APP_BASE_PREFIX === 'string' ? window.APP_BASE_PREFIX : '';

  if (!BASE_PREFIX) {
    const script = document.currentScript;
    const srcPath = script ? new URL(script.src, window.location.href).pathname : window.location.pathname;
    const idx = srcPath.toLowerCase().indexOf('/backend/');
    BASE_PREFIX = idx > -1 ? srcPath.slice(0, idx) : (srcPath.substring(0, srcPath.lastIndexOf('/')) || '');
  }

  const APPT_API = `${BASE_PREFIX}/backend/api/appointCRUD.php`;

  async function httpJson(url, options = {}) {
    const res = await fetch(url, options);
    const text = await res.text();
    // Strip leading UTF-8 BOM if present (\uFEFF)
    const cleaned = text && text.charCodeAt(0) === 0xFEFF ? text.slice(1) : text;
    let data = null;
    try {
      data = cleaned ? JSON.parse(cleaned) : null;
    } catch (e) {
      const ct = res.headers.get('content-type') || '';
      const snippet = (cleaned || '').slice(0, 200);
      throw new Error(`Invalid JSON (${res.status} ${res.statusText}; ct=${ct}): ${snippet}`);
    }
    if (!res.ok || (data && typeof data === 'object' && data.error)) {
      const msg = (data && data.error) || `${res.status} ${res.statusText}`;
      throw new Error(msg);
    }
    return data;
  }

  const AppointmentsApi = {
    list(params = {}) {
      const u = new URL(APPT_API, window.location.href);
      u.searchParams.set('action', 'list');
      if (params.status) u.searchParams.set('status', params.status);
      if (params.from)   u.searchParams.set('from', params.from);
      if (params.to)     u.searchParams.set('to', params.to);
      return httpJson(u.toString()).then((data) => {
        if (Array.isArray(data)) return data;
        if (data && Array.isArray(data.rows)) return data.rows;
        throw new Error('Unexpected response shape for appointments list');
      });
    },
    get(id) {
      const u = new URL(APPT_API, window.location.href);
      u.searchParams.set('action', 'get');
      u.searchParams.set('id', String(id));
      return httpJson(u.toString());
    },
    create(payload) {
      return httpJson(`${APPT_API}?action=create`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload || {})
      });
    },
    update(id, fields) {
      return httpJson(`${APPT_API}?action=update&id=${encodeURIComponent(id)}`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(fields || {})
      });
    },
    delete(id) {
      return httpJson(`${APPT_API}?action=delete&id=${encodeURIComponent(id)}`, {
        method: 'POST'
      });
    }
  };

  window.AppointmentsApi = AppointmentsApi;
})();