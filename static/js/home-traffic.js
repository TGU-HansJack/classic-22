(() => {
  const BOOTSTRAP_SELECTOR = '[data-home-traffic-bootstrap]';
  const TREND_SELECTOR = '[data-home-traffic-trend-chart]';
  const REFERRING_SELECTOR = '[data-home-traffic-referring-chart]';
  const POPULAR_SELECTOR = '[data-home-traffic-popular-chart]';

  function parseBootstrap(el) {
    if (!el) return null;
    try {
      const raw = String(el.textContent || '').trim();
      if (!raw) return null;
      const data = JSON.parse(raw);
      return data && typeof data === 'object' ? data : null;
    } catch (e) {
      return null;
    }
  }

  function pad2(n) {
    return String(n).padStart(2, '0');
  }

  function formatMD(ts, tz) {
    const unix = Number(ts || 0) + Number(tz || 0);
    if (!Number.isFinite(unix) || unix <= 0) return '';
    const d = new Date(unix * 1000);
    return `${pad2(d.getUTCMonth() + 1)}/${pad2(d.getUTCDate())}`;
  }

  function truncateLabel(value, maxLen) {
    const raw = String(value || '');
    const limit = Math.max(4, Number(maxLen || 16) || 16);
    if (raw.length <= limit) return raw;
    return `${raw.slice(0, Math.max(1, limit - 3))}...`;
  }

  function chartPalette() {
    return {
      axisColor: '#6b7280',
      axisLine: '#cbd5e1',
      gridLine: '#e5e7eb',
      green: '#16a34a',
      blue: '#2563eb',
    };
  }

  function buildTrendOption(payload) {
    const tz = Number(payload.tz || 0) || 0;
    const trend = Array.isArray(payload.trend) ? payload.trend : [];

    const labels = trend.map((row) => formatMD(row && row.ts ? row.ts : 0, tz));
    const views = trend.map((row) => Number(row && row.views ? row.views : 0) || 0);

    const desiredLabels = 7;
    const interval = Math.max(0, Math.ceil(labels.length / desiredLabels) - 1);

    const palette = chartPalette();

    return {
      animation: false,
      // Leave a bit more room for y-axis labels so they don't get clipped.
      grid: { left: 56, right: 12, top: 10, bottom: 34, containLabel: true },
      tooltip: { trigger: 'axis', axisPointer: { type: 'line' } },
      xAxis: {
        type: 'category',
        boundaryGap: false,
        data: labels,
        axisLine: { lineStyle: { color: palette.axisLine } },
        axisTick: { show: false },
        axisLabel: {
          color: palette.axisColor,
          fontSize: 12,
          margin: 12,
          interval,
        },
        splitLine: { show: true, lineStyle: { color: palette.gridLine } },
      },
      yAxis: {
        type: 'value',
        name: 'Total views',
        nameLocation: 'middle',
        nameGap: 40,
        nameTextStyle: { color: palette.axisColor, fontSize: 12 },
        axisLine: { show: true, lineStyle: { color: palette.axisLine } },
        axisTick: { show: false },
        axisLabel: { color: palette.axisColor, fontSize: 12 },
        splitLine: { show: true, lineStyle: { color: palette.gridLine } },
      },
      series: [
        {
          name: 'Views',
          type: 'line',
          data: views,
          showSymbol: true,
          symbol: 'circle',
          symbolSize: 6,
          itemStyle: { color: palette.green },
          lineStyle: { width: 2, color: palette.green },
        },
      ],
    };
  }

  function buildBarOption(items, labelKey, valueKey, axisName) {
    const palette = chartPalette();
    const rows = Array.isArray(items) ? items : [];

    const categories = rows.map((row) => String(row && row[labelKey] ? row[labelKey] : ''));
    const values = rows.map((row) => Number(row && row[valueKey] ? row[valueKey] : 0) || 0);

    return {
      animation: false,
      grid: { left: 8, right: 10, top: 6, bottom: 8, containLabel: true },
      tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
      xAxis: {
        type: 'value',
        name: axisName,
        nameTextStyle: { color: palette.axisColor, fontSize: 11 },
        axisLine: { show: true, lineStyle: { color: palette.axisLine } },
        axisTick: { show: false },
        axisLabel: { color: palette.axisColor, fontSize: 11 },
        splitLine: { show: true, lineStyle: { color: palette.gridLine } },
      },
      yAxis: {
        type: 'category',
        data: categories,
        axisLine: { show: true, lineStyle: { color: palette.axisLine } },
        axisTick: { show: false },
        axisLabel: {
          color: palette.axisColor,
          fontSize: 11,
          formatter: (value) => truncateLabel(value, 18),
        },
      },
      series: [
        {
          type: 'bar',
          data: values,
          itemStyle: { color: palette.blue },
          barWidth: 10,
        },
      ],
    };
  }

  function initCharts() {
    const bootstrapEl = document.querySelector(BOOTSTRAP_SELECTOR);
    if (!bootstrapEl) return;

    const payload = parseBootstrap(bootstrapEl);
    if (!payload) return;

    if (!window.echarts) return;

    const instances = [];
    const charts = [
      {
        el: document.querySelector(TREND_SELECTOR),
        option: () => buildTrendOption(payload),
      },
      {
        el: document.querySelector(REFERRING_SELECTOR),
        option: () =>
          buildBarOption(payload.referringSites, 'site', 'views', ''),
      },
      {
        el: document.querySelector(POPULAR_SELECTOR),
        option: () =>
          buildBarOption(payload.popularContent, 'title', 'views', ''),
      },
    ];

    charts.forEach((entry) => {
      const el = entry.el;
      if (!el) return;
      const inst = window.echarts.getInstanceByDom(el) || window.echarts.init(el);
      inst.setOption(entry.option());
      instances.push(inst);
    });

    if (!instances.length) return;

    const resize = () => instances.forEach((inst) => inst.resize());
    window.addEventListener('resize', resize);

    if (window.ResizeObserver) {
      const ro = new ResizeObserver(resize);
      charts.forEach((entry) => {
        if (entry.el) ro.observe(entry.el);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts);
  } else {
    initCharts();
  }
})();
