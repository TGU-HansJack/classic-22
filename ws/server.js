'use strict';

const http = require('http');
const { WebSocketServer } = require('ws');

const host = process.env.WS_HOST || '127.0.0.1';
const port = Number(process.env.WS_PORT || 9527);

const server = http.createServer((req, res) => {
  res.statusCode = 404;
  res.setHeader('Content-Type', 'text/plain; charset=utf-8');
  res.end('Not Found');
});

const wss = new WebSocketServer({ noServer: true });

function normalizePath(raw) {
  const value = String(raw || '').trim();
  if (!value) return '/';
  try {
    if (value.startsWith('http://') || value.startsWith('https://')) {
      return new URL(value).pathname || '/';
    }
    if (value.startsWith('/')) {
      return new URL(value, 'http://localhost').pathname || '/';
    }
    return new URL('/' + value, 'http://localhost').pathname || '/';
  } catch (error) {
    return '/';
  }
}

function safeSend(ws, payload) {
  if (!ws || ws.readyState !== ws.OPEN) return;
  try {
    ws.send(payload);
  } catch (error) {}
}

function buildCounts() {
  const counts = new Map();

  wss.clients.forEach((client) => {
    if (client.readyState !== client.OPEN) return;
    const path = normalizePath(client.pagePath || '/');
    counts.set(path, (counts.get(path) || 0) + 1);
  });

  return counts;
}

function broadcastCounts() {
  const counts = buildCounts();

  wss.clients.forEach((client) => {
    if (client.readyState !== client.OPEN) return;

    const path = normalizePath(client.pagePath || '/');
    const count = Number(counts.get(path) || 0);
    const payload = JSON.stringify({
      type: 'online',
      path,
      count,
      transport: 'websocket'
    });

    safeSend(client, payload);
  });
}

wss.on('connection', (ws, req) => {
  ws.pagePath = normalizePath((req.url || '/').split('?')[0] || '/');
  ws.lastActiveAt = Date.now();

  safeSend(
    ws,
    JSON.stringify({
      type: 'welcome',
      transport: 'websocket',
      message: 'connected'
    })
  );

  broadcastCounts();

  ws.on('message', (raw) => {
    ws.lastActiveAt = Date.now();

    let payload = null;
    try {
      payload = JSON.parse(String(raw || ''));
    } catch (error) {
      return;
    }

    if (!payload || typeof payload !== 'object') {
      return;
    }

    if (payload.type === 'subscribe') {
      ws.pagePath = normalizePath(payload.path || payload.page || '/');
      broadcastCounts();
      return;
    }

    if (payload.type === 'ping') {
      safeSend(
        ws,
        JSON.stringify({
          type: 'pong',
          ts: Date.now()
        })
      );
    }
  });

  ws.on('close', () => {
    broadcastCounts();
  });

  ws.on('error', () => {});
});

server.on('upgrade', (req, socket, head) => {
  const pathname = normalizePath((req.url || '').split('?')[0] || '/');
  if (pathname !== '/ws') {
    socket.destroy();
    return;
  }

  wss.handleUpgrade(req, socket, head, (ws) => {
    wss.emit('connection', ws, req);
  });
});

const idleTimeoutMs = 2 * 60 * 1000;
setInterval(() => {
  const now = Date.now();
  wss.clients.forEach((client) => {
    if (client.readyState !== client.OPEN) return;
    if (now - Number(client.lastActiveAt || now) > idleTimeoutMs) {
      try {
        client.terminate();
      } catch (error) {}
    }
  });
}, 30 * 1000).unref();

server.listen(port, host, () => {
  console.log(`[classic22-ws] listening on ws://${host}:${port}/ws`);
});

