// Entry point for gold chart on Sell Gold page
import { createGoldChart, reloadGoldChart } from './chartInit.js';

const KARAT_FACTORS = {
  '24': 1.0,
  '22': 0.916667,
  '21': 0.875,
  '18': 0.75,
  '14': 0.583333,
  '10': 0.416667
};

function monthToRange(monthStr) {
  // monthStr: YYYY-MM
  if (!/^\d{4}-\d{2}$/.test(monthStr)) return null;
  const [y,m] = monthStr.split('-').map(Number);
  const firstDay = `${monthStr}-01`;
  const last = new Date(Date.UTC(y, m, 0)); // day 0 of next month
  const lastDay = last.toISOString().slice(0,10);
  return { firstDay, lastDay };
}

document.addEventListener('DOMContentLoaded', async () => {
  const bootstrapEl = document.getElementById('goldChartBootstrap');
  if (!bootstrapEl) return;
  let params = {};
  try { params = JSON.parse(bootstrapEl.textContent || '{}'); } catch {}

  let chart = await createGoldChart(params, { fillMonth: false });

  const form = document.getElementById('goldChartForm');
  if (!form) return;

  // Prevent default submit (page reload)
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    // Manual button triggers same logic as change events
    triggerUpdate();
  });

  const monthInput = form.querySelector('input[name="month"]');
  const karatSelect = form.querySelector('select[name="karat"]');
  const redrawBtn = form.querySelector('button[type="submit"]');
  redrawBtn?.setAttribute('type','button');

  function buildParams() {
    const monthVal = monthInput?.value || params.month;
    const karatVal = karatSelect?.value || params.karat;
    const range = monthToRange(monthVal) || { firstDay: params.firstDay, lastDay: params.lastDay };
    return {
      month: monthVal,
      karat: karatVal,
      karatFactor: KARAT_FACTORS[karatVal] ?? 1.0,
      firstDay: range.firstDay,
      lastDay: range.lastDay
    };
  }

  let pending = null;
  async function triggerUpdate() {
    // Debounce if multiple rapid changes
    if (pending) clearTimeout(pending);
    pending = setTimeout(async () => {
      pending = null;
      params = buildParams();
  await reloadGoldChart(chart, params, { fillMonth: false });
      // Optionally sync URL without reload:
      if (history.replaceState) {
        const qs = new URLSearchParams({ month: params.month, karat: params.karat });
        history.replaceState(null,'', 'SellGold.php?'+qs.toString());
      }
    }, 120);
  }

  monthInput?.addEventListener('change', triggerUpdate);
  karatSelect?.addEventListener('change', triggerUpdate);

  document.getElementById('goldGoLatest')?.addEventListener('click', () => {
    // If you want last-N behavior again, call:
    // window.GoldChartHelpers?.centerLatest?.(chart, 20);
    // For full-month mode we just reset to month bounds (reload already sets them).
  reloadGoldChart(chart, params, { fillMonth: false });
  });

  document.getElementById('goldResetZoom')?.addEventListener('click', () => {
  if (chart?.resetZoom) chart.resetZoom();
  reloadGoldChart(chart, params, { fillMonth: false }); // restore month bounds
  });
});