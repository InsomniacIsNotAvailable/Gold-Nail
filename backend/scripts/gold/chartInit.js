// Initializes the candlestick chart with full-month day alignment (no gaps)
import { fetchOhlcRange } from './dataApi.js';
import { ensureSynced } from './sync.js';

async function waitFinancial() {
  if (window.__financialReady?.then) {
    try { await window.__financialReady; } catch(_) {}
  }
}

// Heuristic: detect if raw values look like PHP per ounce (≈ 200k+) vs PHP per gram (≈ 4k–8k)
function detectProviderUnit(rows) {
  try {
    const vals = rows.flatMap(r => [Number(r.open), Number(r.high), Number(r.low), Number(r.close)])
      .filter(v => Number.isFinite(v)).sort((a,b)=>a-b);
    if (vals.length < 4) return 'gram';
    const mid = Math.floor(vals.length / 2);
    const median = vals.length % 2 ? vals[mid] : (vals[mid-1] + vals[mid]) / 2;
    // If median is very large, assume per ounce
    return median > 20000 ? 'ounce' : 'gram';
  } catch { return 'gram'; }
}

function convertRows(rows, karatFactor) {
  // Prefer PHP/gram; if DB still contains PHP/ounce, convert client-side as a hotfix.
  const unit = detectProviderUnit(rows);
  const OZ_TO_G = 31.1034768;
  const divisor = unit === 'ounce' ? OZ_TO_G : 1;
  if (unit === 'ounce') {
    console.warn('[gold-chart] Detected ounce-based data; converting to PHP/gram on client.');
  }
  return rows
    .sort((a,b)=> a.date < b.date ? -1 : a.date > b.date ? 1 : 0)
    .map(r => ({
      _date: r.date, // keep original YYYY-MM-DD for merge
      x: new Date(r.date + 'T00:00:00Z').getTime(),
      o: (+r.open  / divisor) * karatFactor,
      h: (+r.high  / divisor) * karatFactor,
      l: (+r.low   / divisor) * karatFactor,
      c: (+r.close / divisor) * karatFactor,
      _real: true
    }));
}

async function loadData(params) {
  // Ask sync to fill the selected month up to today (for the current month)
  try {
    const isCurrentMonth = params.month === new Date().toISOString().slice(0,7);
    await ensureSynced({ from: params.firstDay, to: params.lastDay, inclusiveToday: isCurrentMonth });
  } catch(_) {}
  const rows = await fetchOhlcRange({ from: params.firstDay, to: params.lastDay });
  console.debug('[gold-chart] API returned rows:', rows.length, rows?.[0]);
  const conv = convertRows(rows, params.karatFactor);
  console.debug('[gold-chart] Converted rows:', conv.length, conv?.[0]);
  return conv;
}

// Generate all YYYY-MM-DD strings inclusive
function enumerateDays(firstDay, lastDay) {
  const out = [];
  const start = new Date(firstDay + 'T00:00:00Z');
  const end = new Date(lastDay + 'T00:00:00Z');
  for (let d = new Date(start); d <= end; d.setUTCDate(d.getUTCDate() + 1)) {
    out.push(d.toISOString().slice(0,10));
  }
  return out;
}

/**
 * Fill missing calendar days within the month so the x-axis has one candle per day.
 * Strategy:
 *  - For any missing day after the first real candle, repeat previous close as a flat candle (o=h=l=c=prevClose).
 *  - These synthetic candles are marked with _real=false. The plugin treats them as unchanged (flat) so they render with unchangedColor.
 *  - Optionally: If you do NOT want days after the last real data (e.g. current month future days), set fillFuture = false below.
 */
function ensureFullMonth(data, params, { fillFuture = true } = {}) {
  if (!data.length) return data;
  const indexByDate = Object.create(null);
  data.forEach(d => { indexByDate[d._date] = d; });

  const allDays = enumerateDays(params.firstDay, params.lastDay);
  const todayYmd = new Date().toISOString().slice(0,10);
  const isCurrentMonth = params.month === todayYmd.slice(0,7);

  const out = [];
  let prevClose = null;
  for (const day of allDays) {
    const existing = indexByDate[day];
    if (existing) {
      out.push(existing);
      prevClose = existing.c;
      continue;
    }

    // Skip future days of current (in-progress) month if fillFuture disabled
    if (isCurrentMonth && !fillFuture && day > todayYmd) continue;

    if (prevClose == null) {
      // No previous reference yet (missing days at start) -> skip until first real
      continue;
    }

    // Create flat synthetic candle
    const ts = new Date(day + 'T00:00:00Z').getTime();
    out.push({
      _date: day,
      x: ts,
      o: prevClose,
      h: prevClose,
      l: prevClose,
      c: prevClose,
      _real: false
    });
  }

  return out;
}

// Compute month range boundaries (UTC)
function calcMonthBounds(monthStr) {
  if (!/^\d{4}-\d{2}$/.test(monthStr)) return {};
  const [y,m] = monthStr.split('-').map(Number);
  const start = Date.UTC(y, m - 1, 1, 0,0,0,0);
  const end = Date.UTC(y, m, 0, 23,59,59,999);
  return { start, end };
}

/* Pan tip fade controller */
function initPanTipFader(chart) {
  const tip = document.querySelector('.gold-chart-pan-tip');
  if (!tip) return;
  let hideTimer;

  function showTip() {
    tip.classList.add('visible');
    tip.classList.remove('fade-out');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
      tip.classList.add('fade-out');
    }, 2600);
  }

  requestAnimationFrame(showTip);
  const canvas = chart.canvas;
  canvas.addEventListener('wheel', showTip, { passive: true });
  canvas.addEventListener('pointerdown', (e) => { if (e.shiftKey) showTip(); });

  const zoomOpts = chart?.options?.plugins?.zoom || {};
  if (zoomOpts.pan) {
    const origPanComplete = zoomOpts.pan.onPanComplete;
    zoomOpts.pan.onPanComplete = (ctx) => { showTip(); if (origPanComplete) origPanComplete(ctx); };
  }
  if (zoomOpts.zoom) {
    const origZoomComplete = zoomOpts.zoom.onZoomComplete;
    zoomOpts.zoom.onZoomComplete = (ctx) => { showTip(); if (origZoomComplete) origZoomComplete(ctx); };
  }
  chart.$panTip = { show: showTip };
}

export async function createGoldChart(params, { mountId='goldChartCanvas', emptyId='goldChartEmpty', fillMonth=false } = {}) {
  const canvas = document.getElementById(mountId);
  const empty = document.getElementById(emptyId);
  if (!canvas) return null;

  empty.textContent = 'Loading...';
  empty.style.display = 'flex';

  let baseData;
  try {
    baseData = await loadData(params);
  } catch (e) {
    console.error('[gold-chart] initial load failed', e);
    empty.textContent = 'Failed to load data.';
    return null;
  }

  if (!baseData.length) {
    empty.textContent = 'No data for selected month.';
    return null;
  }

  // Optionally fill month with synthetic flat bars; default is off for a cleaner look
  const fullData = fillMonth ? ensureFullMonth(baseData, params, { fillFuture: false }) : baseData;

  await waitFinancial();
  empty.style.display = 'none';
  canvas.style.display = '';

  const ctx = canvas.getContext('2d');
  const css = v => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
  const { start, end } = calcMonthBounds(params.month);

  const config = window.GoldChartConfig(fullData, {
    title: `Gold OHLC (${params.karat}K • PHP/gram) — ${params.month}`,
    bg: css('--panel') || '#ffffff',
    fg: css('--fg') || '#1a1a1a',
    grid: css('--panel-border') || '#ebebeb',
    upColor: css('--up') || '#d4af37',
    downColor: css('--down') || '#b78e15',
    monthRangeStart: start,
    monthRangeEnd: end
  });

  const chart = new Chart(ctx, config);
  initPanTipFader(chart);
  return chart;
}

export async function reloadGoldChart(chart, params, { fillMonth=false } = {}) {
  if (!chart) return;
  const empty = document.getElementById('goldChartEmpty');
  const canvas = document.getElementById('goldChartCanvas');
  empty.textContent = 'Updating...';
  empty.style.display = 'flex';
  canvas.style.opacity = '0.35';

  let baseData = [];
  try {
    baseData = await loadData(params);
  } catch (e) {
    console.error('[gold-chart] reload failed', e);
    empty.textContent = 'Reload failed.';
    canvas.style.opacity = '1';
    return;
  }

  if (!baseData.length) {
    empty.textContent = 'No data for month.';
    canvas.style.opacity = '1';
    return;
  }

  const fullData = fillMonth ? ensureFullMonth(baseData, params, { fillFuture: false }) : baseData;
  const ds = chart.data.datasets?.[0];
  if (ds) {
    if (chart.config.type === 'candlestick') {
      ds.data = fullData;
    } else {
      ds.data = fullData.map(d => ({ x: d.x, y: d.c }));
    }
  }

  if (chart.options.scales?.x) {
    const { start, end } = calcMonthBounds(params.month);
    chart.options.scales.x.min = start;
    chart.options.scales.x.max = end;
  }
  // Dynamically fit Y to data with padding
  if (chart.options.scales?.y) {
    const values = (fullData || []).flatMap(d => [d.o, d.h, d.l, d.c]).filter(v => Number.isFinite(v));
    if (values.length) {
      const min = Math.min(...values);
      const max = Math.max(...values);
      const pad = Math.max(1, (max - min) * 0.06);
      chart.options.scales.y.min = min - pad;
      chart.options.scales.y.max = max + pad;
    }
  }
  chart.options.plugins.title.text = `Gold OHLC (${params.karat}K • PHP/gram) — ${params.month}`;

  empty.style.display = 'none';
  canvas.style.opacity = '1';
  chart.update();
  if (chart.$panTip?.show) chart.$panTip.show();
}