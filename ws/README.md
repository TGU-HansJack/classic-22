# classic-22/ws (Node.js WebSocket 服务)

此目录是 `classic-22` 主题的独立 Node WebSocket 服务。

## 启动

```bash
cd blog/usr/themes/classic-22/ws
npm install
npm run start
```

默认监听：`ws://0.0.0.0:9527/ws`（建议容器环境）

可通过环境变量覆盖：

```bash
WS_HOST=0.0.0.0 WS_PORT=9527 npm run start
```

## PM2 守护与开机自启

先全局安装 PM2（如果未安装）：

```bash
npm i -g pm2
```

在 `ws` 目录执行：

```bash
cd blog/usr/themes/classic-22/ws
npm install
pm2 start ecosystem.config.cjs --env production
pm2 save
```

常用命令：

```bash
pm2 status
pm2 logs classic22-ws
pm2 restart classic22-ws
pm2 stop classic22-ws
pm2 delete classic22-ws
```

### 配置开机自启

执行：

```bash
pm2 startup
```

它会输出一条需要你复制执行的命令（通常带 `sudo env PATH=... pm2 startup ...`）。

执行那条命令后，再执行：

```bash
pm2 save
```

这样服务器重启后，`classic22-ws` 会自动拉起。

## 反向代理

Nginx/Caddy 反代到本服务：`127.0.0.1:9527`

主题设置中的 `WebSocket 地址` 填：

- `wss://craft.hansjack.com/ws`

## 1Panel 常见问题排查

如果浏览器提示无法连接 `wss://域名/ws`，优先检查下面 4 项：

1. **服务监听地址**
   - 必须监听 `0.0.0.0`（容器环境推荐），不要只监听 `127.0.0.1`。

2. **Nginx `/ws` 反代是否生效**
   - `location /ws` 需要在站点 `server` 内，并且不要被站点 rewrite 规则覆盖。

3. **1Panel 安全/WAF 拦截**
   - 若响应里出现 `Request Denied by Edge Algorithm.`，说明请求被边缘防护拦截，
     需要在 1Panel 安全策略里为 `/ws` 放行或关闭该路径防护。

4. **握手验证（必须返回 101）**
   - 先测后端：
   ```bash
   curl --http1.1 -i -N \
     -H "Connection: Upgrade" \
     -H "Upgrade: websocket" \
     -H "Sec-WebSocket-Version: 13" \
     -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
     http://127.0.0.1:9527/ws
   ```
   - 再测域名：
   ```bash
   curl --http1.1 -i -N \
     -H "Connection: Upgrade" \
     -H "Upgrade: websocket" \
     -H "Sec-WebSocket-Version: 13" \
     -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
     https://craft.hansjack.com/ws
   ```
   - 两步都应返回：`HTTP/1.1 101 Switching Protocols`。

5. **首页卡片人数不变（一直 0）**
   - 这是因为首页展示的是“各文章路径”的在线人数，
     只有用户实际打开某篇文章（如 `/official/craft.html`）并建立 WS 订阅后，
     对应卡片才会从 `0` 变为 `1/2/...`。
   - 现在服务端会额外广播 `online_map`（全量路径人数），
     首页能实时接收并更新每个卡片的数字。
