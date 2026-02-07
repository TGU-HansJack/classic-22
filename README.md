# classic-22 主题实时在线（Node.js WebSocket）

当前主题已切换为 **Node.js WebSocket 服务**，不再使用 PHP 版 WS 服务。

## 目录结构

- 前端：`static/js/live-socket.js`
- Node 服务：`ws/server.js`

## 1) 启动 Node WS 服务

```bash
cd blog/usr/themes/classic-22/ws
npm install
npm run start
```

默认监听：`ws://127.0.0.1:9527/ws`

可选环境变量：

```bash
WS_HOST=127.0.0.1 WS_PORT=9527 npm run start
```

## 2) 主题后台配置

- `实时在线人数（WebSocket）`：开启
- `WebSocket 地址`：`wss://你的域名/ws`

## 3) Nginx 反向代理（/ws -> Node 服务）

在站点 `server` 块加入：

```nginx
location /ws {
    proxy_pass http://127.0.0.1:9527;
    proxy_http_version 1.1;

    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    proxy_read_timeout 3600s;
    proxy_send_timeout 3600s;
    proxy_buffering off;
}
```

## 4) Caddy 反向代理（/ws -> Node 服务）

```caddy
craft.hansjack.com {
    @ws path /ws
    reverse_proxy @ws 127.0.0.1:9527

    # 其他 Typecho 配置...
}
```

## 5) 验证

1. 打开首页/文章页。
2. 页脚 Socket 状态应从“连接中”变为“已连接”。
3. 多浏览器访问同一文章，卡片右上角在线人数应实时变化。

