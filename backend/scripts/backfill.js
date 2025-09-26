(function () {
  // Derive base prefix so this works under subfolders (e.g., /Gold%20Nail)
  let BASE_PREFIX = '';
  try {
    const script = document.currentScript;
    const srcPath = script ? new URL(script.src, window.location.href).pathname : window.location.pathname;
    const idx = srcPath.toLowerCase().indexOf('/backend/');
    BASE_PREFIX = idx > -1 ? srcPath.slice(0, idx) : (srcPath.substring(0, srcPath.lastIndexOf('/')) || '');
  } catch {}

  function toUtcYmd(d) {
    const y = d.getUTCFullYear();
    const m = String(d.getUTCMonth() + 1).padStart(2, '0');
    const day = String(d.getUTCDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }
  function yesterdayUtcYmd() {
    const now = new Date();
    const yest = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() - 1));
    return toUtcYmd(yest);
  }
  function isYmd(s) { return /^\d{4}-\d{2}-\d{2}$/.test(String(s || '')); }

  async function goldBackfill(from, to) {
    if (!from) throw new Error('from (YYYY-MM-DD) is required');
    if (!isYmd(from)) throw new Error('from must be YYYY-MM-DD');
    if (!to) to = yesterdayUtcYmd();
    if (!isYmd(to)) throw new Error('to must be YYYY-MM-DD');

    const url = `${BASE_PREFIX}/backend/api/gold_ohlc_sync.php?action=syncRange`;
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ from, to })
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || (json && json.error)) {
      const msg = (json && json.error) || `${res.status} ${res.statusText}`;
      throw new Error(msg);
    }
    console.info('Backfill complete:', json);
    return json;
  }

  // Convenience helpers
  async function goldBackfillAug18to31() {
    // Adjust the year if needed
    return goldBackfill('2025-08-18', '2025-08-31');
  }
  async function goldBackfillFrom(startYmd) {
    return goldBackfill(startYmd, yesterdayUtcYmd());
  }

  // Expose to window for console use
  window.goldBackfill = goldBackfill;
  window.goldBackfillAug18to31 = goldBackfillAug18to31;
  window.goldBackfillFrom = goldBackfillFrom;
})();