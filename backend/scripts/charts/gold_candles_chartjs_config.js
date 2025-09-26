/* global Chart, luxon */
(function (global) {
  /**
   * Build config for Gold Candles
   * Update: Force one tick per calendar day (no 1,7,13 gaps) via custom plugin.
   */
  function buildGoldCandlesChartConfig(seriesData, opts) {
    const o = Object.assign({
      title: 'Gold OHLC (PHP/Gram)',
      bg: '#ffffff',
      fg: '#1a1a1a',
      grid: '#ebebeb',
      upColor: '#d4af37',
      downColor: '#b78e15',
      unchangedColor: '#9d9d9d',
      maintainAspectRatio: false,
      enablePan: true,
      enableZoom: true,
      fallbackToLine: true,
      monthRangeStart: undefined,
      monthRangeEnd: undefined,
      // New options
      forceDailyTicks: true,      // force every calendar day label
      dayLabelFormat: 'd',        // label format for each day
      tickFontSize: 10            // smaller font helps fit all 31 labels
    }, opts || {});

    const hasCandlestick = !!(Chart?.registry?.controllers?.get?.('candlestick'));
    const chartType = hasCandlestick ? 'candlestick' : 'line';

    const datasets = hasCandlestick
      ? [{
          label: 'Gold (PHP / gram)',
          data: seriesData,
          color: { up: o.upColor, down: o.downColor, unchanged: o.unchangedColor },
          borderColor: o.fg
        }]
      : [{
          label: 'Gold Close (PHP / gram)',
          data: seriesData.map(d => ({ x: d.x, y: d.c })),
          borderColor: o.upColor,
          backgroundColor: o.upColor + '22',
          fill: true,
          tension: 0.15
        }];

    const xMin = typeof o.monthRangeStart === 'number' ? o.monthRangeStart : undefined;
    const xMax = typeof o.monthRangeEnd === 'number' ? o.monthRangeEnd : undefined;

    function dayTickFormatter(value) {
      try {
        return luxon.DateTime.fromMillis(value, { zone: 'utc' }).toFormat(o.dayLabelFormat);
      } catch { return ''; }
    }

    // Plugin to inject daily ticks regardless of Chart.js autoskip
    const forceDailyTicksPlugin = {
      id: 'forceDailyTicks',
      beforeBuildTicks(scale) {
        if (!o.forceDailyTicks) return;
        if (scale.type !== 'time' || scale.axis !== 'x') return;
        const min = typeof scale.options.min === 'number' ? scale.options.min : scale.min;
        const max = typeof scale.options.max === 'number' ? scale.options.max : scale.max;
        if (!min || !max) return;
        const dayMs = 24 * 3600 * 1000;
        const ticks = [];
        for (let t = min; t <= max; t += dayMs) {
            ticks.push({ value: t });
        }
        scale.ticks = ticks;
      },
      afterBuildTicks(scale) {
        // Ensure parsing doesn't re-trim them
        if (!o.forceDailyTicks) return;
        if (scale.type !== 'time' || scale.axis !== 'x') return;
        // No-op: ticks already set
      }
    };

    // Register once (idempotent) - safe because Chart.js ignores duplicates by id
    if (!Chart.registry.plugins.get('forceDailyTicks')) {
      Chart.register(forceDailyTicksPlugin);
    }

    return {
      type: chartType,
      data: { datasets },
      options: {
        backgroundColor: o.bg,
        responsive: true,
        maintainAspectRatio: o.maintainAspectRatio,
        parsing: false,
        normalized: true,
        plugins: {
          legend: { labels: { color: o.fg } },
          title: {
            display: true,
            text: o.title + (hasCandlestick ? '' : ' (Line Fallback)'),
            color: o.fg,
            font: { weight: '600', size: 16 }
          },
          tooltip: {
            mode: 'nearest',
            intersect: false,
            backgroundColor: '#ffffff',
            borderColor: '#d4d4d4',
            borderWidth: 1,
            titleColor: '#111',
            bodyColor: '#222',
            displayColors: false,
            callbacks: {
              title(items) {
                const raw = items[0].raw;
                const ts = hasCandlestick ? raw.x : items[0].parsed.x;
                return new Date(ts).toISOString().slice(0, 10);
              },
              label(ctx) {
                if (hasCandlestick) {
                  const r = ctx.raw || {};
                  return [
                    `Open:  ${Number(r.o).toLocaleString()}`,
                    `High:  ${Number(r.h).toLocaleString()}`,
                    `Low:   ${Number(r.l).toLocaleString()}`,
                    `Close: ${Number(r.c).toLocaleString()}`
                  ];
                }
                return `Close: ${Number(ctx.parsed.y).toLocaleString()}`;
              }
            }
          },
          zoom: {
            zoom: {
              wheel: { enabled: !!o.enableZoom },
              pinch: { enabled: !!o.enableZoom },
              mode: 'x'
            },
            pan: {
              enabled: !!o.enablePan,
              mode: 'x',
              modifierKey: 'shift'
            }
          }
        },
        layout: { padding: 4 },
        scales: {
          x: {
            type: 'time',
            distribution: 'linear',
            min: xMin,
            max: xMax,
            time: {
              unit: 'day',
              stepSize: 1,
              displayFormats: { day: o.dayLabelFormat },
              tooltipFormat: 'yyyy-MM-dd'
            },
            grid: { color: o.grid },
            ticks: {
              color: o.fg,
              autoSkip: false,
              maxRotation: 0,
              minRotation: 0,
              padding: 4,
              font: { size: o.tickFontSize },
              callback: (val) => dayTickFormatter(val)
            }
          },
          y: {
            grid: { color: o.grid },
            ticks: { color: o.fg }
          }
        }
      }
    };
  }

  function centerLatest(chart, barsWindow) {
    try {
      const ds = chart.data.datasets?.[0]?.data || [];
      if (ds.length < 2) return;
      const last = ds[ds.length - 1].x;
      const prev = ds[ds.length - 2].x;
      const delta = Math.max(6 * 3600 * 1000, last - prev);
      chart.options.scales.x.min = last - (barsWindow || 20) * delta;
      chart.options.scales.x.max = last + delta * 0.5;
      chart.update('none');
    } catch (_) {}
  }

  global.GoldChartConfig = buildGoldCandlesChartConfig;
  global.GoldChartHelpers = { centerLatest };
})(window);