(() => {
  function getConfig() {
    const cfg = window.CLASSIC22_LIVE_WS || {};
    return {
      enabled: cfg.enabled !== false,
      endpoint: String(cfg.endpoint || '/ws').trim() || '/ws',
      currentPath: normalizePath(cfg.currentPath || window.location.pathname || '/')
    };
  }

  function normalizePath(raw) {
    const value = String(raw || '').trim();
    if (!value) return '/';
    try {
      if (value.startsWith('http://') || value.startsWith('https://')) {
        return String(new URL(value).pathname || '/');
      }
      if (value.startsWith('/')) {
        return String(new URL(value, window.location.origin).pathname || '/');
      }
      return String(new URL('/' + value, window.location.origin).pathname || '/');
    } catch (e) {
      return '/';
    }
  }

  function resolveSocketUrl(endpoint) {
    const raw = String(endpoint || '').trim() || '/ws';
    if (/^wss?:\/\//i.test(raw)) {
      return raw;
    }
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const path = raw.startsWith('/') ? raw : '/' + raw;
    return protocol + '//' + window.location.host + path;
  }

  function coerceCount(value) {
    const n = Number(value);
    if (!Number.isFinite(n) || n < 0) return 0;
    return Math.max(0, Math.floor(n));
  }

  function readPayload(raw) {
    if (typeof raw !== 'string') return null;
    const text = raw.trim();
    if (!text) return null;
    try {
      return JSON.parse(text);
    } catch (e) {
      return null;
    }
  }

  function parseOnlineMessage(payload, currentPath) {
    if (!payload || typeof payload !== 'object') {
      return null;
    }

    if (payload.type === 'online') {
      const p = normalizePath(payload.path || payload.page || payload.uri || currentPath);
      const count = coerceCount(payload.count ?? payload.online ?? payload.onlineCount ?? 0);
      return { [p]: count };
    }

    if (payload.type === 'online_map' && payload.data && typeof payload.data === 'object') {
      const map = {};
      Object.keys(payload.data).forEach((k) => {
        map[normalizePath(k)] = coerceCount(payload.data[k]);
      });
      return map;
    }

    if (payload.online && typeof payload.online === 'object') {
      const map = {};
      Object.keys(payload.online).forEach((k) => {
        map[normalizePath(k)] = coerceCount(payload.online[k]);
      });
      return map;
    }

    if (payload.path || payload.page || payload.uri) {
      const p = normalizePath(payload.path || payload.page || payload.uri);
      const count = coerceCount(payload.count ?? payload.online ?? payload.onlineCount ?? 0);
      return { [p]: count };
    }

    if (Object.prototype.hasOwnProperty.call(payload, 'count')) {
      const count = coerceCount(payload.count);
      return { [currentPath]: count };
    }

    return null;
  }

  function initFooterTooltip(statusButton, tooltip) {
    if (!statusButton || !tooltip) return;

    function close() {
      tooltip.hidden = true;
      statusButton.setAttribute('aria-expanded', 'false');
    }

    function open() {
      tooltip.hidden = false;
      statusButton.setAttribute('aria-expanded', 'true');
    }

    statusButton.addEventListener('click', (event) => {
      event.preventDefault();
      if (tooltip.hidden) {
        open();
      } else {
        close();
      }
    });

    document.addEventListener('click', (event) => {
      if (tooltip.hidden) return;
      const target = event.target;
      if (statusButton.contains(target) || tooltip.contains(target)) {
        return;
      }
      close();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        close();
      }
    });
  }

  function run() {
    const cfg = getConfig();
    const socketStatus = document.querySelector('[data-live-socket-status]');
    const socketStatusText = document.querySelector('[data-live-socket-state-text]');
    const socketTooltip = document.querySelector('[data-live-socket-tooltip]');
    const socketTooltipState = document.querySelector('[data-live-socket-tooltip-state]');

    initFooterTooltip(socketStatus, socketTooltip);

    const cards = Array.from(document.querySelectorAll('[data-live-online-card]'));
    const cardMap = new Map();
    cards.forEach((card) => {
      const path = normalizePath(card.getAttribute('data-page-path'));
      const numberNode = card.querySelector('[data-live-online-number]');
      if (!cardMap.has(path)) {
        cardMap.set(path, []);
      }
      cardMap.get(path).push({ card, numberNode });
    });

    function applyCount(path, count) {
      const normalized = normalizePath(path);
      const safeCount = coerceCount(count);

      const targets = cardMap.get(normalized) || [];
      targets.forEach(({ card, numberNode }) => {
        if (numberNode) {
          numberNode.textContent = String(safeCount);
        }
        card.setAttribute('data-online-count', String(safeCount));
        card.classList.toggle('is-active', safeCount > 0);
      });
    }

    function setConnectionState(stateText, stateClass) {
      if (socketStatusText) {
        socketStatusText.textContent = stateText;
      }
      if (socketTooltipState) {
        socketTooltipState.textContent = stateText;
      }
      if (socketStatus) {
        socketStatus.classList.remove('is-connecting', 'is-connected', 'is-closed');
        socketStatus.classList.add(stateClass);
      }
    }

    if (!cfg.enabled) {
      setConnectionState('已关闭', 'is-closed');
      return;
    }

    const currentPath = normalizePath(cfg.currentPath);
    let ws = null;
    let reconnectTimer = null;
    let reconnectDelay = 1500;

    function clearReconnect() {
      if (!reconnectTimer) return;
      window.clearTimeout(reconnectTimer);
      reconnectTimer = null;
    }

    function scheduleReconnect() {
      clearReconnect();
      reconnectTimer = window.setTimeout(() => {
        connect();
      }, reconnectDelay);
      reconnectDelay = Math.min(12000, reconnectDelay + 1200);
    }

    function sendSubscribe() {
      if (!ws || ws.readyState !== WebSocket.OPEN) return;
      const payload = {
        type: 'subscribe',
        page: currentPath,
        path: currentPath
      };
      try {
        ws.send(JSON.stringify(payload));
      } catch (e) {}
    }

    function onMessage(event) {
      const payload = readPayload(event && event.data);
      const updates = parseOnlineMessage(payload, currentPath);
      if (!updates) return;
      Object.keys(updates).forEach((path) => {
        applyCount(path, updates[path]);
      });
    }

    function connect() {
      clearReconnect();
      setConnectionState('连接中', 'is-connecting');

      const socketUrl = resolveSocketUrl(cfg.endpoint);
      try {
        ws = new WebSocket(socketUrl);
      } catch (e) {
        setConnectionState('连接失败', 'is-closed');
        scheduleReconnect();
        return;
      }

      ws.addEventListener('open', () => {
        reconnectDelay = 1500;
        setConnectionState('已连接', 'is-connected');
        sendSubscribe();
      });

      ws.addEventListener('message', onMessage);

      ws.addEventListener('close', () => {
        setConnectionState('已断开', 'is-closed');
        scheduleReconnect();
      });

      ws.addEventListener('error', () => {
        setConnectionState('连接失败', 'is-closed');
      });
    }

    setConnectionState('连接中', 'is-connecting');
    connect();

    window.addEventListener('beforeunload', () => {
      clearReconnect();
      if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) {
        try {
          ws.close(1000, 'page unload');
        } catch (e) {}
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
