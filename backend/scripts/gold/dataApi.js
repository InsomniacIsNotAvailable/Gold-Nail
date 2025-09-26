// Handles API interaction for OHLC data
export async function fetchOhlcRange({ from, to }) {
  const params = new URLSearchParams({ action: 'list', from, to });
  const url = 'backend/api/gold_ohlc_crud.php?' + params.toString();
  console.debug('[gold-chart] Fetching OHLC:', url);
  const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
  if (!r.ok) {
    const txt = await r.text().catch(()=> '');
    throw new Error('Fetch OHLC failed HTTP ' + r.status + ' ' + txt);
  }
  const rows = await r.json();
  console.debug('[gold-chart] Rows received:', Array.isArray(rows) ? rows.length : 'non-array');
  return Array.isArray(rows) ? rows : [];
}