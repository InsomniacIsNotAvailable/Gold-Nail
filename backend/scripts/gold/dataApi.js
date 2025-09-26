// Handles API interaction for OHLC data
export async function fetchOhlcRange({ from, to }) {
  const params = new URLSearchParams({ action: 'list', from, to, _ts: Date.now().toString() });
  const url = 'backend/api/gold_ohlc_crud.php?' + params.toString();
  console.debug('[gold-chart] Fetching OHLC:', url);
  const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
  if (!r.ok) {
    const txt = await r.text().catch(()=> '');
    throw new Error('Fetch OHLC failed HTTP ' + r.status + ' ' + txt);
  }
  const payload = await r.json();
  const rows = Array.isArray(payload) ? payload
             : (payload && Array.isArray(payload.data)) ? payload.data
             : [];
  console.debug('[gold-chart] Rows received:', rows.length);
  return rows;
}