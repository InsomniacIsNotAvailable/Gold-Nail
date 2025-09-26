// Initializes the candlestick chart with full-month day alignment (no gaps)
import { fetchOhlcRange } from './dataApi.js';
import { ensureSynced } from './sync.js';

async function waitFinancial() {
  if (window.__financialReady?.then) {
    try { await window.__financialReady; } catch(_) {}
  }
}

function convertRows(rows, karatFactor) {
  const gramDivisor = 31.1035;
  return rows
    .sort((a,b)=> a.date < b.date ? -1 : a.date > b.date ? 1 : 0)
    .map(r => ({
      _date: r.date, // keep original YYYY-MM-DD for merge
      x: new Date(r.date + 'T00:00:00Z').getTime(),
      o: (+r.open  / gramDivisor) * karatFactor,
      h: (+r.high  / gramDivisor) * karatFactor,
      l: (+r.low   / gramDivisor) * karatFactor,
      c: (+r.close / gramDivisor) * karatFactor,
      _real: true
    }));
}

async function loadData(params) {
  await ensureSynced();
  const rows = await fetchOhlcRange({ from: params.firstDay, to: params.lastDay });
  return convertRows(rows, params.karatFactor);
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

export async function createGoldChart(params, { mountId='goldChartCanvas', emptyId='goldChartEmpty' } = {}) {
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

  // Fill missing days (set fillFuture: false to NOT create flat future candles in current month)
  const fullData = ensureFullMonth(baseData, params, { fillFuture: true });

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

export async function reloadGoldChart(chart, params) {
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

  const fullData = ensureFullMonth(baseData, params, { fillFuture: true });
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
  chart.options.plugins.title.text = `Gold OHLC (${params.karat}K • PHP/gram) — ${params.month}`;

  empty.style.display = 'none';
  canvas.style.opacity = '1';
  chart.update();
  if (chart.$panTip?.show) chart.$panTip.show();
}