# classic-22/ws (Node.js WebSocket 服务)

此目录是 `classic-22` 主题的独立 Node WebSocket 服务。

## 启动

```bash
cd blog/usr/themes/classic-22/ws
npm install
npm run start
```

默认监听：`ws://127.0.0.1:9527/ws`

可通过环境变量覆盖：

```bash
WS_HOST=127.0.0.1 WS_PORT=9527 npm run start
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
