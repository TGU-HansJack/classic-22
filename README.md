# classic-22 主题 WebSocket 实时在线说明

本主题已内置 WebSocket 前端与服务端脚本：

- 前端：`static/js/live-socket.js`
- 服务端：`ws-server.php`

目标效果：前端直接连接 `wss://你的域名/ws`，实时更新文章卡片在线人数与页脚连接状态。

---

## 1) 启动主题内置 WS 服务

在站点根目录执行（CLI）：

```bash
php blog/usr/themes/classic-22/ws-server.php
```

也可以指定监听地址和端口：

```bash
php blog/usr/themes/classic-22/ws-server.php 127.0.0.1 9527
```

建议：

- 监听 `127.0.0.1`（仅本机）
- 由 Nginx/Caddy 对外提供 TLS（`wss://`）

---

## 2) 主题后台配置

在主题设置中：

- `实时在线人数（WebSocket）`：开启
- `WebSocket 地址`：`wss://你的域名/ws`
- `内置 WS 监听 Host`：`127.0.0.1`
- `内置 WS 监听 Port`：`9527`

---

## 3) Nginx 反向代理（/ws -> 127.0.0.1:9527）

将以下 `location /ws` 加入你站点 `443` 的 `server` 块中：

```nginx
server {
    listen 443 ssl http2;
    server_name craft.hansjack.com;

    # ... 你现有的站点配置（PHP/静态资源/Typecho）

    location /ws {
        proxy_pass http://127.0.0.1:9527;
        proxy_http_version 1.1;

        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }
}
```

修改后重载：

```bash
nginx -t && nginx -s reload
```

---

## 4) Caddy 反向代理（/ws -> 127.0.0.1:9527）

`Caddyfile` 示例：

```caddy
craft.hansjack.com {
    @ws path /ws
    reverse_proxy @ws 127.0.0.1:9527

    # ... 你现有的 Typecho 配置
    # 例如：
    # root * /var/www/typecho
    # php_fastcgi 127.0.0.1:9000
    # file_server
}
```

> Caddy 会自动处理 WebSocket Upgrade，无需额外 `Upgrade/Connection` 头配置。

---

## 5) 验证方式

1. 打开站点首页或文章页。
2. 页脚状态应从“连接中”变为“已连接”。
3. 多浏览器/多设备访问同一文章，卡片右上角在线数应变化。

可选命令行测试：

```bash
npx wscat -c wss://你的域名/ws
```

连接后发送：

```json
{"type":"subscribe","path":"/official/craft.html"}
```

---

## 6) 常见问题

- `连接失败/502`：`ws-server.php` 未启动，或监听端口不对。
- `Mixed Content`：HTTPS 页面却配置了 `ws://`，应改为 `wss://`。
- 走 CDN 时连不上：确认 CDN 已开启 WebSocket 透传。
- 端口被占用：换端口后同步修改主题配置与反代目标端口。

