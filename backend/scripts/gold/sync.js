// Ensures DB is up to date (daily backfill)
import { toUtcYmd, addDaysUtc } from './timeUtils.js';

export async function ensureSynced() {
  try {
    const now = new Date();
    const yesterday = new Date(Date.UTC(
      now.getUTCFullYear(),
      now.getUTCMonth(),
      now.getUTCDate() - 1
    ));
    const yesterdayStr = toUtcYmd(yesterday);

    const lastRes = await fetch('backend/api/gold_ohlc_sync.php?action=lastDate');
    const lastJson = lastRes.ok ? await lastRes.json() : {};
    const last = lastJson?.last;

    if (!last) {
      console.info('[gold-sync] No DB data yet');
      return;
    }
    const from = addDaysUtc(last, 1);
    if (from <= yesterdayStr) {
      console.info('[gold-sync] Syncing gap', from, '->', yesterdayStr);
      const body = JSON.stringify({ from, to: yesterdayStr });
      const t0 = performance.now();
      const r = await fetch('backend/api/gold_ohlc_sync.php?action=syncRange', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body
      });
      if (!r.ok) throw new Error('syncRange HTTP ' + r.status);
      await r.json().catch(()=>{});
      console.info('[gold-sync] Done in', (performance.now() - t0).toFixed(0), 'ms');
    } else {
      console.info('[gold-sync] Up to date (last=' + last + ')');
    }
  } catch (e) {
    console.warn('[gold-sync] Failed', e);
  }
}