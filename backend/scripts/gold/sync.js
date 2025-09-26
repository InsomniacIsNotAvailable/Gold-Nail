// Ensures DB is up to date (daily backfill)
import { toUtcYmd, addDaysUtc } from './timeUtils.js';

/**
 * ensureSynced optionally accepts a date window to backfill.
 * opts: { from?: 'YYYY-MM-DD', to?: 'YYYY-MM-DD', inclusiveToday?: boolean }
 */
export async function ensureSynced(opts = {}) {
  try {
    const now = new Date();
    const todayStr = toUtcYmd(new Date(Date.UTC(
      now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate()
    )));

    const lastRes = await fetch('backend/api/gold_ohlc_sync.php?action=lastDate');
    const lastJson = lastRes.ok ? await lastRes.json() : {};
    const last = lastJson?.last; // may be null if empty DB

    // Determine desired window: clamp to today unless opts.to is earlier
    let targetTo = todayStr;
    if (typeof opts.to === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(opts.to) && opts.to < targetTo) {
      targetTo = opts.to;
    }
    // If not including today and targetTo == today, back off to yesterday
    if (opts.inclusiveToday !== true && targetTo === todayStr) {
      const y = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() - 1));
      targetTo = toUtcYmd(y);
    }

    const startFrom = last ? addDaysUtc(last, 1)
      : (opts.from && /^\d{4}-\d{2}-\d{2}$/.test(opts.from) ? opts.from : targetTo);

    if (startFrom <= targetTo) {
      console.info('[gold-sync] Syncing gap', startFrom, '->', targetTo);
      const body = JSON.stringify({ from: startFrom, to: targetTo });
      const t0 = performance.now();
      const r = await fetch('backend/api/gold_ohlc_sync.php?action=syncRange', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body
      });
      if (!r.ok) throw new Error('syncRange HTTP ' + r.status);
      const js = await r.json().catch(()=>({}));
      console.info('[gold-sync] Done in', (performance.now() - t0).toFixed(0), 'ms', js);
    } else {
      console.info('[gold-sync] Up to date (last=' + (last||'none') + ')');
    }
  } catch (e) {
    console.warn('[gold-sync] Failed', e);
  }
}

// Manually trigger a sync for an exact date range (inclusive)
export async function syncRangeExact(from, to) {
  const body = JSON.stringify({ from, to });
  const r = await fetch('backend/api/gold_ohlc_sync.php?action=syncRange', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body
  });
  const js = await r.json().catch(()=>({}));
  if (!r.ok) throw new Error('syncRange HTTP ' + r.status + ' ' + JSON.stringify(js));
  return js;
}