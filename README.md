# classic-22（魔改版）Typecho 主题

基于 Typecho 官方默认主题 **Classic 22** 二次开发（魔改）而来，服务于「创意工坊」站点。

- 站点预览：<https://craft.hansjack.com>（创意工坊）
- 推荐 Typecho：`>= 1.2`（命名空间版，推荐 `1.3+`）
- License：MIT（见 `LICENSE`）

> 本仓库是“主题项目”，不是 Typecho 程序本体。请先安装 Typecho 再使用本主题。

---

## 功能特性（概览）

### 外观与阅读体验

- **深浅色/自定义配色**：后台可选 `自动/浅色/深色/自定义`，自定义时加载 `theme.css`
- **首页卡片式文章列表**：自动提取封面图（优先字段：`cover`/`thumb`/`thumbnail`/`image`；兜底提取正文/摘要首图，支持 `data-src/data-original/data-lazy-src`）
- **文章目录 TOC**：文章页右侧目录，滚动高亮 + 平滑滚动
- **代码高亮**：集成 `highlight.js`（CDN）
- **图片点击放大**：文章/页面内容图片自动接入 `Fancybox`（CDN），支持图集浏览；可对图片或其父容器添加 `data-no-fancybox` 禁用
- **内容增强**：当某段落“只包含 1 个 GitHub 仓库链接（`https://github.com/owner/repo`）且无其它文本”时，会自动渲染为卡片（描述 + Star）
- **首页公告**：支持多条公告轮播（可视化编辑，默认 3 秒切换）

### 互动与登录

- **评论区 Linux Do 登录（OAuth）**：一键带入昵称/邮箱/主页，可选开关

### 自动化与 AI

- **首页/文章页 AI 对话**：支持 OpenAI 兼容接口与 Right Code（GPT-Codex）
  - 域名白名单（防被外站调用）
  - 默认每 IP 每天 5 次（可在代码中调整）
  - 生成记录写入 `ai_logs/`（JSONL + quota 文件）
- **首页时间线（Timeline）**：最近发布/评论/榜单变化（15 分钟缓存），采用本地格式化生成摘要（不依赖 AI）

### 站点运营能力（可选增强）

> 下列功能依赖/联动 `Vue3Admin` 插件或外部服务，未安装时会降级或提示不可用。

- **实时在线人数（WebSocket）**：文章卡片在线角标 + 页脚连接状态（Node.js WS 服务）
- **友链页**：展示友链 + 申请表单（存储于 Vue3Admin LocalStorage）
- **投稿页**：链接解析 + AI 生成内容 + 频率限制 + reCAPTCHA v3（数据存储在 `v3a_post/`）
- **排行榜页**：浏览/评论排行 + GitHub stars/维护度排行（含缓存）

---

## 截图

![classic-22 screenshot](screenshot.png)

---

## 运行环境（建议）

> 主题本体可在“最小可用”的 Typecho 环境下运行；但部分增强功能（AI/登录/WS/Vue3Admin）对环境有额外要求。

- **Typecho**：`>= 1.2`（命名空间版，推荐 `1.3+`）
- **PHP**：建议 `7.4+`（推荐 `8.0+`）
- **必需/推荐扩展**
  - `curl`：启用 **Linux Do 登录** 与 **AI 对话/AI 润色** 时必需
  - `mbstring`：处理中文截断更稳定（无也可运行，会降级）
  - `pdo_sqlite`：使用 **Vue3Admin LocalStorage** 时常见必需（友链/访问日志等）
- **Node.js**：仅当启用“实时在线（WebSocket）”时需要（建议 `18 LTS+`）
- **网络访问（出站 HTTPS）**
  - AI：需要服务器能访问你配置的 AI API（OpenAI 兼容或 Right Code）
  - GitHub：
    - 前端 GitHub 卡片：浏览器访问 `api.github.com`
    - 排行榜：服务器访问 GitHub API（并写入缓存）
  - 投稿页 reCAPTCHA：浏览器需加载 reCAPTCHA 脚本，服务器需验证 token（若启用 Secret Key）
  - 代码高亮：默认从 CDN 加载 `highlight.js`（无法访问 CDN 时可自行改为本地资源）

### 隐私提示（AI）

若启用 AI 对话，本主题默认会记录：**IP、提问内容、时间戳、模型等元信息** 到 `ai_logs/`。如需关闭/脱敏请自行修改 `functions.php` 中日志逻辑。

---

## 安装与启用

### 1) 放置主题

将本仓库放到 Typecho 的主题目录（**目录名建议保持 `classic-22`**）：

```text
usr/themes/classic-22
```

### 2) 后台启用

进入 Typecho 后台：`控制台 -> 外观`，选择并启用 `classic-22`。

---

## 目录结构（快速认识）

```text
classic-22/
  static/                    # 主题静态资源（CSS/JS/IMG）
    css/
    js/
    img/
  ws/                        # Node.js WebSocket 服务（实时在线）
  ai_logs/                   # AI 对话日志与配额文件（需可写）
  v3a_post/                  # 投稿数据存储目录（需可写）
  functions.php              # 核心：主题配置/AI/登录/时间线等
  header.php / footer.php    # 站点头尾
  index.php / post.php       # 首页/文章页
  comments.php               # 评论（含 Linux Do 登录）
  v3a_links.php              # 友链页模板（可选）
  v3a_post.php               # 投稿页模板（可选）
  v3a_ranks.php              # 排行榜页模板（可选）
  theme.css                  # 自定义配色示例（colorSchema=customize 时加载）
```

---

## 部署前必读：权限与安全（重要）

为保证部分功能正常工作，请确保以下路径**可写**（权限不足会导致 AI/时间线/投稿等功能失效）：

- `usr/themes/classic-22/ai_logs/`：AI 对话日志与配额（`quota-YYYY-MM-DD.json`）
- `usr/themes/classic-22/timeline/`：时间线缓存（首次访问会自动创建）
- `usr/themes/classic-22/v3a_post/`：投稿数据存储
- `usr/cache/`：排行榜 GitHub 缓存（`usr/cache/v3a_ranks_cache.json`）

并确保这些目录**不可被 Web 直接访问**：

- `ai_logs/`、`v3a_post/` 已内置 `index.php`、`.htaccess`、`web.config` 保护
- `timeline/` 目录在运行时自动创建，并同样写入保护文件（若创建失败会降级为空数据）

---

## 主题设置（后台配置项说明）

路径：`控制台 -> 外观 -> 设置外观（classic-22）`

### 1) 基础设置

- **网站 Logo**（`logoUrl`）
  - 支持绝对 URL 或站内相对路径
  - 留空则显示站点标题

- **外观风格**（`colorSchema`）
  - `自动 / 浅色 / 深色 / 自定义`
  - 选择 `自定义` 时加载根目录 `theme.css`

### 2) 首页公告（轮播）

- **首页公告**（`homeAnnouncements`）
  - 后台提供可视化编辑器
  - 支持多个公告，上下翻转切换（默认 3 秒）
  - 字段说明：
    - `type`：`notice | info | warning | activity`
    - `emoji`：可选（填写后优先显示 Emoji）
    - `content`：必填
    - `url`：可选（跳转链接）

示例 JSON（仅用于理解格式，实际建议直接在后台编辑）：

```json
[
  {"type":"notice","emoji":"🧩","content":"Classic-22 魔改上线","url":"https://craft.hansjack.com"},
  {"type":"activity","content":"欢迎投稿你做的主题/插件","url":"/submit.html"}
]
```

### 3) 评论区 Linux Do 登录（OAuth）

- **Linux Do Client ID**（`linuxDoClientId`）
- **Linux Do Client Secret**（`linuxDoClientSecret`）
- **评论区 Linux Do 登录**（`linuxDoCommentEnabled`：开/关）

回调地址固定为：

```text
站点首页 + ?ldo_action=callback
例如：https://example.com/?ldo_action=callback
```

说明与注意：

- 访客可在评论区点击 “Linux Do 登录” 自动带入昵称/邮箱/主页
- 需要 PHP Session；主题会在初始化时确保 Session 可用
- 反向代理场景建议正确传递 `X-Forwarded-Proto`，避免回调 URL 协议判断错误

### 4) 投稿页（`v3a_post.php`）

- **投稿提交频率限制（秒）**（`v3aPostLimitSeconds`，默认 `60`，填 `0` 表示不限制）
- **投稿页 reCAPTCHA v3 Site Key（可选）**（`v3aPostRecaptchaV3SiteKey`）
- **投稿页 reCAPTCHA v3 Secret Key（可选）**（`v3aPostRecaptchaV3SecretKey`）
  - Secret Key 留空时不会做服务端校验
- **投稿 AI Prompt**（`v3aPostAiPrompt`）
  - 用于“链接投稿 -> 自动解析并生成内容”的提示词，可按站点规范自行调整

> 投稿数据写入 `usr/themes/classic-22/v3a_post/`。若目录不可写，页面会提示并停止保存。

### 5) 实时在线（WebSocket）

- **实时在线人数（WebSocket）**（`liveWsEnabled`：开/关）
- **WebSocket 地址**（`liveWsEndpoint`）
  - 支持填写：
    - `/ws`（推荐：自动根据当前站点协议选择 `ws/wss`）
    - `ws://127.0.0.1:9527/ws`
    - `wss://your-domain/ws`
  - 留空默认使用 `/ws`

### 6) 首页/文章页 AI 对话

- **AI 对话**（`aiEnabled`：开/关）
- **AI 允许域名**（`aiAllowedDomains`）
  - 一行一个域名，例如：
    ```text
    craft.hansjack.com
    *.hansjack.com
    ```
  - 留空时默认允许当前站点域名
  - 校验基于请求 `Origin/Referer` 的 host（用于防止被外站盗用接口）

- **AI 接口类型**（`aiProvider`）
  - `OpenAI 兼容接口`
  - `Right Code (GPT-Codex)`（会强制使用 `responses`，并在 baseUrl 空/指向 openai 时回退到 `https://www.right.codes/codex/v1`）

- **AI 请求模式**（`aiApiMode`）
  - `chat/completions`：OpenAI 兼容接口常用
  - `responses`：Right Code 推荐

- **AI 接口地址**（`aiApiBaseUrl`，默认 `https://api.openai.com/v1`）
- **AI API Key**（`aiApiKey`）
- **AI 模型列表**（`aiModels`，每行一个；前端下拉展示）
- **默认 AI 模型**（`aiDefaultModel`，为空则取列表第一项）
- **AI 系统提示词（可选）**（`aiSystemPrompt`）

内置限制与日志：

- 默认每 IP 每天 `5` 次（写入 `ai_logs/quota-YYYY-MM-DD.json`）
- 每次请求写入 `ai_logs/chat-YYYY-MM-DD.jsonl`（JSONL，一行一条）

### （调试用）AI 接口路径与请求格式

主题内置 2 个接口（由 `themeInit()` 拦截 `classic22_ai` 参数）：

- 获取文章列表：`GET /?classic22_ai=articles`
- 发起对话：`POST /?classic22_ai=chat`

`chat` 请求体示例：

```json
{
  "message": "请总结本站最近更新了什么",
  "model": "gpt-4o-mini",
  "articleId": 0,
  "articleUrl": ""
}
```

注意：接口会校验 `Origin/Referer` 的域名是否在白名单内；用 `curl` 测试时请带 `Origin`：

```bash
curl -i "https://example.com/?classic22_ai=articles" \
  -H "Origin: https://example.com"

curl -i "https://example.com/?classic22_ai=chat" \
  -H "Origin: https://example.com" \
  -H "Content-Type: application/json" \
  --data "{\"message\":\"你好\",\"model\":\"gpt-4o-mini\",\"articleId\":0,\"articleUrl\":\"\"}"
```

常见响应：

- `403`：AI 未开启 / 域名不在白名单 / 触发地区限制
- `429`：超过每日配额（默认 5 次/天/IP）
- `502`：上游 AI 接口错误或返回格式异常

---

## 页面模板（友链/投稿/排行榜）

主题内置 3 个“独立页面模板”文件：

- `v3a_links.php`：友链展示 + 申请
- `v3a_post.php`：投稿（链接解析 + AI 生成 + 管理员审核/导出）
- `v3a_ranks.php`：排行榜（浏览/评论 + GitHub stars/维护度等）

使用方式（按你的 Typecho 版本选择其一）：

### 方式 A：后台选择模板（推荐）

新建一个「独立页面」，在右侧的 “模板/自定义模板” 下拉中选择对应文件。

> 如果你的后台下拉列表里看不到这些文件，请使用方式 B。

### 方式 B：按 slug 绑定模板（通用兜底）

复制模板文件并改名为 `page-<slug>.php`，然后创建同 slug 的页面：

- 投稿页 `/submit.html`：
  - 复制 `v3a_post.php` 为 `page-submit.php`
  - 新建页面 slug=`submit`

- 友链页 `/links.html`：
  - 复制 `v3a_links.php` 为 `page-links.php`
  - 新建页面 slug=`links`

- 排行榜 `/ranks.html`：
  - 复制 `v3a_ranks.php` 为 `page-ranks.php`
  - 新建页面 slug=`ranks`

> 实际 URL 取决于你的路由/伪静态规则；以上为常见默认形式示例。

### v3a_post.php（投稿页）

默认字段（内置 Schema）：

- `source_url`：投稿链接（必填，URL）
- `title`：标题（可选）
- `project_author`：项目作者（可选）
- `project_type`：项目类型（可选，`typecho|halo`）
- `project_link`：项目链接（可选，URL）
- `content`：文章内容（Markdown，可选）

安全与防刷：

- **频率限制**：默认 60 秒/次（可在主题设置 `v3aPostLimitSeconds` 调整，`0` 表示不限制）
- **Honeypot**：内置隐藏字段拦截简单机器人提交
- **reCAPTCHA v3（可选）**：配置 Site Key/Secret Key 后启用

AI 生成（可选）：

- 当配置了 `aiApiKey`（主题设置）或 `v3a_ai_api_key`（Vue3Admin）时，投稿页会尝试：
  1) 抓取链接页面内容（标题/描述/正文文本）
  2) 调用 AI 生成 `title/content/project_*` 等字段（提示词来自 `v3aPostAiPrompt`）
- 未配置 AI Key 时会降级为“基础解析 + 手动编辑”。

数据存储与管理：

- 存储目录：`usr/themes/classic-22/v3a_post/`
- 主数据文件：`v3a_post/page-<页面CID>.json`
- 管理员可在页面内：筛选状态、编辑字段、修改审核状态、导出数据
- 审核状态：`pending`（待审核）/ `approved`（已通过）/ `rejected`（已拒绝）

兼容/高级用法（了解即可）：

- 投稿页会读取该页面的 **自定义字段**（`table.fields`），支持旧配置：
  - `limit`：频率限制秒数
  - `recaptcha_v3_id`：reCAPTCHA Site Key
  - `recaptcha_v3_key`：reCAPTCHA Secret Key
- 还支持用“自定义字段 JSON”扩展表单字段（类型支持：`input/editor/checkbox/radio/select`）。
  例：新增一个必填邮箱字段（字段名：`contact_email`，字段值：）
  ```json
  {"type":"input","label":"联系邮箱","required":true,"input_type":"email","order":60,"placeholder":"name@example.com","max_length":190}
  ```

### v3a_links.php（友链页）

依赖与说明：

- 需要启用 `Vue3Admin` 插件（使用其 LocalStorage / SQLite）
- 友链展示来源：`v3a_friend_link`（`status=1`）
- 申请记录写入：`v3a_friend_link_apply`（默认 `status=0` 待处理）

申请表单可配置项：

- `v3a_friend_apply_settings`（JSON，通常由 Vue3Admin/站点配置写入）
  - `allowTypeSelect`：是否允许选择友链类型
  - `allowedTypes.friend/collection`：允许的类型
  - `defaultType`：默认类型
  - `required.email/avatar/description/message`：哪些字段必填

### v3a_ranks.php（排行榜页）

功能点：

- 浏览/评论排行榜
- GitHub 维度排行榜：Stars、近 90 天 Commit、维护度（基于 Commit 频率的指数）

缓存与限制：

- GitHub API 数据缓存：`usr/cache/v3a_ranks_cache.json`（默认 6 小时）
- GitHub API 未鉴权时有频率限制；命中限制会自动沿用缓存数据

站点结构约定（建议）：

- 默认会从分类 slug 为 `plugins` / `themes` 的文章中提取 GitHub 仓库链接并生成榜单
  - 识别形式：`https://github.com/owner/repo` 或 `git@github.com:owner/repo(.git)`

---

## 实时在线（WebSocket）：Node.js 服务部署

主题已切换为 **Node.js WebSocket 服务**（不再使用 PHP 版 WS 服务）。

### 目录结构

- 前端：`static/js/live-socket.js`
- Node 服务：`ws/server.js`

### 1) 启动 Node WS 服务

```bash
cd usr/themes/classic-22/ws
npm install
npm run start
```

默认监听：`ws://0.0.0.0:9527/ws`

可选环境变量：

```bash
WS_HOST=0.0.0.0 WS_PORT=9527 npm run start
```

### （可选）PM2 守护与开机自启

> 适合长期运行在服务器上。

先安装 PM2（如已安装可跳过）：

```bash
npm i -g pm2
```

启动并保存：

```bash
cd usr/themes/classic-22/ws
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

配置开机自启：

```bash
pm2 startup
```

`pm2 startup` 会输出一条需要你复制执行的命令（通常带 `sudo env PATH=... pm2 startup ...`）。执行后再次运行：

```bash
pm2 save
```

### 2) 主题后台配置

- `实时在线人数（WebSocket）`：开启
- `WebSocket 地址`：
  - HTTPS 站点推荐：`wss://你的域名/ws`
  - 或者直接填：`/ws`

### 3) Nginx 反向代理（/ws -> Node 服务）

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

### 4) Caddy 反向代理（/ws -> Node 服务）

```caddy
craft.hansjack.com {
    @ws path /ws
    reverse_proxy @ws 127.0.0.1:9527

    # 其他 Typecho 配置...
}
```

### 5) 验证

1. 打开首页/文章页。
2. 页脚 Socket 状态应从“连接中”变为“已连接”。
3. 多浏览器访问同一文章，卡片右上角在线人数应实时变化。

### （可选）握手验证（必须返回 101）

先测后端：

```bash
curl --http1.1 -i -N \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  http://127.0.0.1:9527/ws
```

再测域名（经过反向代理）：

```bash
curl --http1.1 -i -N \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  https://你的域名/ws
```

---

## Vue3Admin（可选）说明

本主题的部分能力会联动/依赖 `Vue3Admin` 插件：

- 友链页：读取/写入友链表（LocalStorage / SQLite）
- 浏览统计：读取 `v3a_visit_log` 作为浏览排行数据源（若存在）
- 排行榜页：从文章内容提取 GitHub 仓库链接，结合 GitHub API 统计 stars/维护度（含缓存）

常见要求：

- PHP 启用 `pdo_sqlite`（Vue3Admin LocalStorage 常见依赖）
- `usr/cache/` 可写（排行榜缓存文件）

---

## FAQ / 常见问题排查

### 1) WebSocket 一直“连接失败/已断开”

- HTTPS 站点必须使用 `wss://`（或直接填 `/ws` 让前端自动选择）
- 检查反向代理是否设置了 `Upgrade/Connection` 头
- 确认 `/ws` 握手能返回 `HTTP/1.1 101 Switching Protocols`
- 若使用 1Panel/WAF，确保 `/ws` 不被拦截
  - 如果响应里出现 `Request Denied by Edge Algorithm.`，通常表示边缘防护拦截了 WS 握手请求

### 2) AI 对话提示“当前域名未被允许使用 AI 对话”

- 检查 `AI 允许域名` 是否包含当前访问域名
- 多域名/子域名建议使用：`*.example.com`

### 3) AI 对话提示“AI 日志目录不可写”

- 给 `usr/themes/classic-22/ai_logs/` 设置写权限
- 容器部署请确认 PHP-FPM 用户与目录权限匹配

### 4) 投稿页提示“投稿存储目录不可写”

- 给 `usr/themes/classic-22/v3a_post/` 设置写权限

### 5) 首页时间线/排行榜无数据

- 时间线缓存依赖 `usr/themes/classic-22/timeline/` 可写
- 排行榜页依赖 `usr/cache/` 可写，并可能受 GitHub API rate limit 影响（已做 6 小时缓存）

### 6) 评论区没有出现 Linux Do 登录按钮 / 登录失败

- 确认主题设置中已填写 `Linux Do Client ID/Secret` 且 `评论区 Linux Do 登录` 已开启
- 回调地址必须为：`https://你的域名/?ldo_action=callback`（与 Connect.Linux.Do 配置一致）
- 服务器需启用 `curl` 扩展；否则无法请求 OAuth token / 用户信息
- 反向代理下建议设置 `X-Forwarded-Proto`，避免协议识别错误导致回调异常

### 7) 友链页/排行榜页提示未启用 Vue3Admin

- 安装并启用 `Vue3Admin` 插件
- 常见还需要启用 PHP 扩展 `pdo_sqlite`

### 8) GitHub 仓库卡片不生效 / Star 不显示

- 仅当“段落内只有一个 GitHub 仓库根链接（`https://github.com/owner/repo`）且无其它文字”时才会自动转卡片
- Star 依赖浏览器请求 GitHub API；网络受限/触发 GitHub 频率限制时会降级为普通链接

---

## 二次开发建议

- 样式入口：`static/css/overrides.css`（推荐在这里覆盖而非改原文件）
- 自定义配色：设置外观风格为 `自定义` 后编辑 `theme.css`
- 关键脚本：
  - `static/js/home-ai-chat.js`：AI 对话前端
  - `static/js/live-socket.js`：实时在线前端
  - `static/js/post-toc.js`：文章目录
  - `static/js/content-enhance.js`：代码高亮 + GitHub 卡片
- 关键 PHP：
  - `functions.php`：主题设置、Linux Do OAuth、AI API、时间线缓存等
  - `v3a_post.php`：投稿页（解析/存储/审核/导出）

---

## License

MIT License，见 `LICENSE`。
