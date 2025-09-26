<?php

// Fixed size: edit these values in code only
$w = 1000;
$h = 560;

// Monthly slicing (still configurable via month input)
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m'); // YYYY-MM

// Karat selection
$karat = isset($_GET['karat']) ? $_GET['karat'] : '24';

function valid_month(string $m): bool {
  if (!preg_match('/^\d{4}-\d{2}$/', $m)) return false;
  [$y,$mo] = array_map('intval', explode('-', $m));
  return $y >= 1970 && $mo >= 1 && $mo <= 12;
}

function valid_karat(string $k): bool {
  return in_array($k, ['24', '22', '21', '18', '14', '10'], true);
}

function get_karat_factor(string $karat): float {
  $factors = [
    '24' => 1.0,      // 24k = 100% pure gold
    '22' => 0.916667, // 22k = 91.67% pure gold
    '21' => 0.875,    // 21k = 87.5% pure gold
    '18' => 0.75,     // 18k = 75% pure gold
    '14' => 0.583333, // 14k = 58.33% pure gold
    '10' => 0.416667  // 10k = 41.67% pure gold
  ];
  return $factors[$karat] ?? 1.0;
}

if (!valid_month($month)) {
  http_response_code(400);
  echo "Invalid month. Use YYYY-MM.";
  exit;
}

if (!valid_karat($karat)) {
  $karat = '24'; // fallback to 24k
}

$karatFactor = get_karat_factor($karat);
$firstDay = (new DateTimeImmutable($month . '-01'))->format('Y-m-d');
$lastDay  = (new DateTimeImmutable($month . '-01'))->modify('last day of this month')->format('Y-m-d');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Gold Candlestick (Monthly)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --bg: #0b0b0b;
      --panel: #111111;
      --panel-border: #222222;
      --fg: #e7e7e7;
      --muted: #bdbdbd;
      --up: #22c55e;
      --down: #ef4444;
    }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin:0; padding:16px; background:var(--bg); color:var(--fg); }
    .wrap { max-width: 1600px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .controls { width: <?= $w ?>px; margin: 0 auto; }
    .controls-card { width: 100%; display: flex; background: var(--panel); border: 1px solid var(--panel-border); border-radius: 12px; padding: 10px 14px; box-shadow: 0 0 0 1px rgba(255,255,255,0.02) inset; box-sizing: border-box; }
    .controls-form { display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; flex-wrap: wrap; }
    .controls-left, .controls-right { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .chart-wrap { position: relative; width: <?= $w ?>px; height: <?= $h ?>px; background: var(--panel); border: 1px solid var(--panel-border); border-radius: 12px; padding: 8px; box-shadow: 0 0 0 1px rgba(255,255,255,0.02) inset, 0 10px 30px rgba(0,0,0,0.4); overflow: hidden; margin: 0 auto; box-sizing: border-box; }
    .pan-tip { position: absolute; right: 10px; bottom: 8px; font-size: 12px; color: #cfcfcf; background: rgba(0,0,0,0.35); border: 1px solid #2a2a2a; padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(2px); pointer-events: none; }
    .empty-state { position: absolute; inset: 0; display: none; align-items: center; justify-content: center; color: #bdbdbd; font-size: 14px; background: transparent; }
    canvas { width: 100% !important; height: 100% !important; }
    button, input, select, a.button-link { background:#171717; color:var(--fg); border:1px solid #2b2b2b; border-radius:8px; padding:6px 10px; text-decoration: none; display: inline-block; }
    button:hover, a.button-link:hover { border-color:#3a3a3a; cursor:pointer; }
    label { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); }
  </style>

  <!-- Chart.js core -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

  <!-- Luxon + adapter -->
  <script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>

  <!-- Zoom plugin -->
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.umd.min.js"></script>

  <!-- Local financial plugin -->
  <script src="backend/package/dist/chartjs-chart-financial.js"></script>

  <!-- Simple registration check -->
  <script>
    (function () {
      function candlestickRegistered() {
        return !!(Chart?.registry?.controllers?.get?.('candlestick'));
      }

      function tryManualRegister() {
        try {
          if (candlestickRegistered()) return true;
          
          // The local file should auto-register, but try manual if needed
          const Financial = window['chartjs-chart-financial'] || window.ChartFinancial || {};
          const regs = [];
          if (Financial.FinancialElement) regs.push(Financial.FinancialElement);
          if (Financial.FinancialController) regs.push(Financial.FinancialController);
          if (Financial.CandlestickController) regs.push(Financial.CandlestickController);
          if (Financial.OhlcController) regs.push(Financial.OhlcController);
          
          // Check if attached to Chart namespace directly
          if (!regs.length && (Chart.FinancialElement || Chart.CandlestickController)) {
            if (Chart.FinancialElement) regs.push(Chart.FinancialElement);
            if (Chart.FinancialController) regs.push(Chart.FinancialController);
            if (Chart.CandlestickController) regs.push(Chart.CandlestickController);
            if (Chart.OhlcController) regs.push(Chart.OhlcController);
          }
          
          if (regs.length) {
            Chart.register(...regs);
            console.debug('[financial] manually registered', regs.length, 'components');
          }
          return candlestickRegistered();
        } catch (e) {
          console.warn('[financial] manual register failed', e);
          return false;
        }
      }

      // Set up promise for chart loading
      window.__financialReady = Promise.resolve().then(() => {
        const available = candlestickRegistered() || tryManualRegister();
        console.debug('[financial] local file - candlestick available:', available);
        if (available) {
          console.debug('[financial] SUCCESS: Using local chartjs-chart-financial.js');
        } else {
          console.warn('[financial] Local file loaded but candlestick not registered');
        }
        return available;
      });

      // Register zoom
      try {
        const Zoom = window['chartjs-plugin-zoom'];
        if (Zoom) Chart.register(Zoom);
      } catch (e) {
        console.warn('[zoom] registration error', e);
      }
    })();
  </script>

  <!-- Reusable config -->
  <script src="backend/scripts/charts/gold_candles_chartjs_config.js"></script>
</head>
<body>
  <div class="wrap">
    <h1 style="margin:0;">Gold Candlestick</h1>

    <div class="controls">
      <div class="controls-card">
        <form method="get" class="controls-form">
          <div class="controls-left">
            <label>Month <input type="month" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>" /></label>
            <label>Karat 
              <select name="karat">
                <option value="24" <?= $karat === '24' ? 'selected' : '' ?>>24K (100%)</option>
                <option value="22" <?= $karat === '22' ? 'selected' : '' ?>>22K (91.67%)</option>
                <option value="21" <?= $karat === '21' ? 'selected' : '' ?>>21K (87.5%)</option>
                <option value="18" <?= $karat === '18' ? 'selected' : '' ?>>18K (75%)</option>
                <option value="14" <?= $karat === '14' ? 'selected' : '' ?>>14K (58.33%)</option>
                <option value="10" <?= $karat === '10' ? 'selected' : '' ?>>10K (41.67%)</option>
              </select>
            </label>
            <button type="submit">Redraw</button>
            <a class="button-link" href="?month=<?= date('Y-m') ?>&karat=<?= htmlspecialchars($karat, ENT_QUOTES) ?>">Current month</a>
          </div>
          <div class="controls-right">
            <button type="button" id="goLatestBtn" title="Center latest candle">Go to latest</button>
            <button type="button" id="resetZoomBtn" title="Reset zoom/pan">Reset zoom</button>
          </div>
        </form>
      </div>
    </div>

    <div class="chart-wrap">
      <canvas id="goldChartCanvas"></canvas>
      <div class="empty-state" id="emptyState">No data for this month.</div>
      <div class="pan-tip">Pan: hold Shift + drag. Zoom: mouse wheel/pinch.</div>
    </div>
  </div>

  <script>
    (function () {
      const emptyEl = document.getElementById('emptyState');
      const canvasEl = document.getElementById('goldChartCanvas');
      const karatFactor = <?= json_encode($karatFactor) ?>;
      const selectedKarat = <?= json_encode($karat) ?>;

      function toUtcYmd(d) {
        const y = d.getUTCFullYear();
        const m = String(d.getUTCMonth() + 1).padStart(2, '0');
        const day = String(d.getUTCDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
      }
      function addDaysUtc(ymd, delta) {
        const d = new Date(ymd + 'T00:00:00Z');
        d.setUTCDate(d.getUTCDate() + delta);
        return toUtcYmd(d);
      }

      async function ensureSynced() {
        console.log('[SYNC CHECK] Starting sync verification...');
        
        try {
          // Get current date info
          const now = new Date();
          const currentDate = toUtcYmd(now);
          const targetEnd = new Date(Date.UTC(
            now.getUTCFullYear(),
            now.getUTCMonth(),
            now.getUTCDate() - 1
          ));
          const yesterdayDate = toUtcYmd(targetEnd);
          
          console.log(`[SYNC CHECK] Current date: ${currentDate}`);
          console.log(`[SYNC CHECK] Target end date (yesterday): ${yesterdayDate}`);
          
          // Fetch last date from database
          console.log('[SYNC CHECK] Fetching latest date from database...');
          const lastRes = await fetch('backend/api/gold_ohlc_sync.php?action=lastDate');
          const lastJson = lastRes.ok ? await lastRes.json() : { last: null };
          const last = lastJson?.last || null;
          
          if (!last) {
            console.log('[SYNC CHECK] No data in database - skipping automatic sync');
            return;
          }
          
          console.log(`[SYNC CHECK] Latest date in database: ${last}`);
          
          const from = addDaysUtc(last, 1);
          const to = yesterdayDate;
          
          console.log(`[SYNC CHECK] Checking if sync needed:`);
          console.log(`   From: ${from}`);
          console.log(`   To: ${to}`);
          console.log(`   Needs sync: ${from <= to}`);
          
          if (from <= to) {
            console.log('[SYNC CHECK] Database is behind - starting sync...');
            
            const syncStart = Date.now();
            await fetch('backend/api/gold_ohlc_sync.php?action=syncRange', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ from, to })
            }).then(r => {
              const syncDuration = Date.now() - syncStart;
              if (r.ok) {
                console.log(`[SYNC CHECK] Sync completed successfully in ${syncDuration}ms`);
                return r.json();
              } else {
                console.error(`[SYNC CHECK] Sync failed: HTTP ${r.status}`);
                throw new Error('syncRange failed');
              }
            });
          } else {
            console.log('[SYNC CHECK] Database is up to date - no sync needed');
          }
          
        } catch (error) {
          console.warn('[SYNC CHECK] Sync check completed with error:', error);
        }
      }

      async function loadChart() {
        const params = new URLSearchParams({
          action: 'list',
          from: '<?= $firstDay ?>',
          to:   '<?= $lastDay ?>'
        });
        const apiUrl = 'backend/api/gold_ohlc_crud.php?' + params.toString();

        const r = await fetch(apiUrl, { headers: { 'Accept': 'application/json' } });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const rows = await r.json();

        const data = (rows || [])
          .sort((a,b) => (a.date < b.date ? -1 : a.date > b.date ? 1 : 0))
          .map(r => ({ 
            x: new Date(r.date + 'T00:00:00').getTime(), // Convert to milliseconds
            o: ((+r.open) / 31.1035) * karatFactor,   // Convert to PHP per gram and adjust for karat
            h: ((+r.high) / 31.1035) * karatFactor,   // Convert to PHP per gram and adjust for karat
            l: ((+r.low) / 31.1035) * karatFactor,    // Convert to PHP per gram and adjust for karat
            c: ((+r.close) / 31.1035) * karatFactor   // Convert to PHP per gram and adjust for karat
          }));

        if (!data.length) {
          emptyEl.style.display = 'flex';
          canvasEl.style.display = 'none';
          return;
        }

        // Wait for financial loader (but don't fail if it doesn't work)
        if (window.__financialReady?.then) {
          try { await window.__financialReady; } catch (_) {}
        }

        emptyEl.style.display = 'none';
        canvasEl.style.display = '';
        const ctx = canvasEl.getContext('2d');
        const config = window.GoldChartConfig(data, {
          title: `Gold OHLC (${selectedKarat}K - PHP per gram) — <?= htmlspecialchars($month, ENT_QUOTES) ?>`,
          bg: getComputedStyle(document.documentElement).getPropertyValue('--panel').trim() || '#111111',
          fg: getComputedStyle(document.documentElement).getPropertyValue('--fg').trim() || '#e7e7e7',
          grid: getComputedStyle(document.documentElement).getPropertyValue('--panel-border').trim() || '#222222',
          upColor: getComputedStyle(document.documentElement).getPropertyValue('--up').trim() || '#22c55e',
          downColor: getComputedStyle(document.documentElement).getPropertyValue('--down').trim() || '#ef4444',
          enablePan: true,
          enableZoom: true,
          centerLatest: true,
          centerWindowBars: 20
        });
        const chart = new Chart(ctx, config);
        setTimeout(() => window.GoldChartHelpers.centerLatest(chart, 20), 0);

        document.getElementById('resetZoomBtn')?.addEventListener('click', () => {
          if (typeof chart.resetZoom === 'function') chart.resetZoom();
          if (chart.options?.scales?.x) {
            chart.options.scales.x.min = undefined;
            chart.options.scales.x.max = undefined;
            chart.update('none');
          }
        });
        document.getElementById('goLatestBtn')?.addEventListener('click', () => {
          window.GoldChartHelpers.centerLatest(chart, 20);
        });
      }

      // Auto-redraw when Karat changes (submit the form)
      document.querySelector('select[name="karat"]')?.addEventListener('change', (e) => {
        const form = e.target.closest('form');
        if (form) (form.requestSubmit ? form.requestSubmit() : form.submit());
      });

      // Optional: auto-redraw when Month changes too
      document.querySelector('input[name="month"]')?.addEventListener('change', (e) => {
        const form = e.target.closest('form');
        if (form) (form.requestSubmit ? form.requestSubmit() : form.submit());
      });

      (async () => {
        try {
          await ensureSynced();
        } catch (e) {
          console.warn('Auto-sync skipped:', e);
        }
        try {
          await loadChart();
        } catch (e) {
          emptyEl.style.display = 'flex';
          emptyEl.textContent = 'Failed to load data.';
          canvasEl.style.display = 'none';
          console.error(e);
        }
      })();
    })();
  </script>
  <script src="backend/scripts/backfill.js"></script>
</body>
</html>