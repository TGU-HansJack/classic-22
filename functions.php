<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',

        null,
        _t('网站 Logo'),
        _t('可填写绝对 URL 或站内相对路径，留空则显示站点标题')
    );

    $form->addInput($logoUrl);

    $colorSchema = new \Typecho\Widget\Helper\Form\Element\Select(
        'colorSchema',
        array(
            null => _t('自动'),
            'light' => _t('浅色'),
            'dark' => _t('深色'),
            'customize' => _t('自定义'),
        ),
        null,
        _t('外观风格'),
        _t('如果选择了自定义，主题将使用 theme.css 的样式')
    );

    $form->addInput($colorSchema);

    $homeAnnouncementsDescription = <<<'HTML'
在这里管理首页顶部公告（支持多个公告，上下翻转切换）。<br>
「内容」必填；「跳转链接」可留空；「Emoji」可选（填写后将优先显示 Emoji）。<div id="classic22-home-announcements-editor" style="margin-top: .5rem;"></div>
<style>
#classic22-home-announcements-editor .classic22-anno-toolbar {
  margin-bottom: .5rem;
}

#classic22-home-announcements-editor .classic22-anno-toolbar button {
  margin-right: .5rem;
}

#classic22-home-announcements-editor table.classic22-anno-table {
  width: 100%;
  border-collapse: collapse;
}

#classic22-home-announcements-editor table.classic22-anno-table th,
#classic22-home-announcements-editor table.classic22-anno-table td {
  padding: .5rem;
  border: 1px solid #e5e5e5;
  vertical-align: middle;
}

#classic22-home-announcements-editor .classic22-anno-mark {
  display: flex;
  gap: .5rem;
  align-items: center;
}

#classic22-home-announcements-editor .classic22-anno-mark select {
  width: 8rem;
}

#classic22-home-announcements-editor .classic22-anno-mark input {
  width: 6rem;
}

#classic22-home-announcements-editor input[type="text"],
#classic22-home-announcements-editor input[type="url"] {
  width: 100%;
  box-sizing: border-box;
}
</style>
<script>
(function () {
  function run() {
    var root = document.getElementById('classic22-home-announcements-editor');
    if (!root) return;

    var storage = document.querySelector('textarea[name="homeAnnouncements"]');
    if (!storage) return;

    function safeString(value) {
      if (value === null || value === undefined) return '';
      return String(value);
    }

    function normalizeItem(item) {
      item = item && typeof item === 'object' ? item : {};

      var type = safeString(item.type || 'notice').trim();
      if (!/^(notice|info|warning|activity)$/.test(type)) {
        type = 'notice';
      }

      return {
        type: type,
        emoji: safeString(item.emoji || '').trim(),
        content: safeString(item.content || item.text || '').trim(),
        url: safeString(item.url || '').trim()
      };
    }

    function parseItems() {
      var raw = safeString(storage.value).trim();
      if (!raw) return [];

      try {
        var parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed.map(normalizeItem);
      } catch (e) {
        return [];
      }
    }

    var items = parseItems();

    function sync() {
      storage.value = JSON.stringify(items);
    }

    function el(tag, attrs) {
      var node = document.createElement(tag);
      if (attrs) {
        Object.keys(attrs).forEach(function (key) {
          if (key === 'text') {
            node.textContent = attrs[key];
          } else if (key === 'html') {
            node.innerHTML = attrs[key];
          } else {
            node.setAttribute(key, attrs[key]);
          }
        });
      }
      return node;
    }

    function render() {
      root.innerHTML = '';

      var toolbar = el('div', { class: 'classic22-anno-toolbar' });
      var addBtn = el('button', { type: 'button', class: 'btn', text: '添加公告' });
      addBtn.addEventListener('click', function () {
        items.push({ type: 'notice', emoji: '', content: '', url: '' });
        sync();
        render();
      });
      toolbar.appendChild(addBtn);
      root.appendChild(toolbar);

      var table = el('table', { class: 'classic22-anno-table' });
      table.appendChild(el('thead', {
        html: '<tr><th style="width: 13rem;">标识/Emoji</th><th>内容</th><th>跳转链接</th><th style="width: 6rem;">操作</th></tr>'
      }));

      var tbody = el('tbody');

      if (!items.length) {
        var emptyRow = el('tr');
        emptyRow.appendChild(el('td', {
          html: '<em>暂无公告，点击「添加公告」开始。</em>',
          colspan: '4'
        }));
        tbody.appendChild(emptyRow);
      } else {
        items.forEach(function (item, index) {
          var tr = el('tr');

          // 标识/Emoji
          var markTd = el('td');
          var markWrap = el('div', { class: 'classic22-anno-mark' });

          var typeSelect = el('select');
          [
            { value: 'notice', label: '通知' },
            { value: 'info', label: '信息' },
            { value: 'warning', label: '警告' },
            { value: 'activity', label: '活动' }
          ].forEach(function (opt) {
            var option = el('option', { value: opt.value, text: opt.label });
            if (item.type === opt.value) option.selected = true;
            typeSelect.appendChild(option);
          });
          typeSelect.addEventListener('change', function () {
            items[index].type = safeString(typeSelect.value).trim();
            sync();
          });

          var emojiInput = el('input', { type: 'text', placeholder: '📝', value: item.emoji || '' });
          emojiInput.addEventListener('input', function () {
            items[index].emoji = safeString(emojiInput.value).trim();
            sync();
          });

          markWrap.appendChild(typeSelect);
          markWrap.appendChild(emojiInput);
          markTd.appendChild(markWrap);
          tr.appendChild(markTd);

          // 内容
          var contentTd = el('td');
          var contentInput = el('input', { type: 'text', value: item.content || '' });
          contentInput.addEventListener('input', function () {
            items[index].content = safeString(contentInput.value).trim();
            sync();
          });
          contentTd.appendChild(contentInput);
          tr.appendChild(contentTd);

          // 链接
          var urlTd = el('td');
          var urlInput = el('input', { type: 'url', placeholder: 'https://example.com/', value: item.url || '' });
          urlInput.addEventListener('input', function () {
            items[index].url = safeString(urlInput.value).trim();
            sync();
          });
          urlTd.appendChild(urlInput);
          tr.appendChild(urlTd);

          // 操作
          var actionsTd = el('td');
          var delBtn = el('button', { type: 'button', class: 'btn', text: '删除' });
          delBtn.addEventListener('click', function () {
            items.splice(index, 1);
            sync();
            render();
          });
          actionsTd.appendChild(delBtn);
          tr.appendChild(actionsTd);

          tbody.appendChild(tr);
        });
      }

      table.appendChild(tbody);
      root.appendChild(table);
    }

    // Ensure stored value is normalized JSON.
    items = items.map(normalizeItem);
    sync();
    render();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
</script>
HTML;

    $homeAnnouncements = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'homeAnnouncements',
        null,
        '[]',
        _t('首页公告'),
        $homeAnnouncementsDescription
    );

    // Use a hidden textarea to store JSON; the editor above will sync to this value.
    $homeAnnouncements->setInputsAttribute('style', 'display:none');
    $form->addInput($homeAnnouncements);

    $linuxDoClientId = new \Typecho\Widget\Helper\Form\Element\Text(
        'linuxDoClientId',
        null,
        (string) (classic22LinuxDoFallbackConfig()['linuxDoClientId'] ?? ''),
        _t('Linux Do Client ID'),
        _t('在 Connect.Linux.Do 申请应用后获得。')
    );
    $form->addInput($linuxDoClientId);

    $linuxDoClientSecret = new \Typecho\Widget\Helper\Form\Element\Password(
        'linuxDoClientSecret',
        null,
        (string) (classic22LinuxDoFallbackConfig()['linuxDoClientSecret'] ?? ''),
        _t('Linux Do Client Secret'),
        _t('仅服务端使用。回调地址请填写：站点首页 + ?ldo_action=callback，例如 https://example.com/?ldo_action=callback')
    );
    $form->addInput($linuxDoClientSecret);

    $liveWsEnabled = new \Typecho\Widget\Helper\Form\Element\Select(
        'liveWsEnabled',
        [
            '1' => _t('开启'),
            '0' => _t('关闭'),
        ],
        '1',
        _t('实时在线人数（WebSocket）'),
        _t('用于文章卡片在线人数与页脚连接状态展示。')
    );
    $form->addInput($liveWsEnabled);

    $liveWsEndpoint = new \Typecho\Widget\Helper\Form\Element\Text(
        'liveWsEndpoint',
        null,
        '/ws',
        _t('WebSocket 地址'),
        _t('支持 /ws、ws:// 或 wss:// 地址。留空时默认使用 /ws。')
    );
    $form->addInput($liveWsEndpoint);


    return;

}

function classic22LinuxDoThemeName(): string
{
    return basename(__DIR__);
}

function classic22LinuxDoThemeOptionName(): string
{
    return 'theme:' . classic22LinuxDoThemeName();
}

function classic22LinuxDoFallbackOptionName(): string
{
    return 'classic22_linuxdo_config';
}

function classic22LinuxDoDb()
{
    try {
        if (class_exists('\\Typecho\\Db')) {
            return \Typecho\Db::get();
        }

        if (class_exists('Typecho_Db')) {
            return \Typecho_Db::get();
        }
    } catch (\Throwable $exception) {
        return null;
    }

    return null;
}

function classic22LinuxDoLoadOptionJson(string $name): array
{
    $db = classic22LinuxDoDb();
    if (!is_object($db) || $name === '') {
        return [];
    }

    try {
        $row = $db->fetchRow(
            $db->select('value')
                ->from('table.options')
                ->where('name = ? AND user = ?', $name, 0)
                ->limit(1)
        );
    } catch (\Throwable $exception) {
        return [];
    }

    $raw = '';
    if (is_array($row)) {
        $raw = (string) ($row['value'] ?? '');
    } elseif (is_object($row)) {
        $raw = (string) ($row->value ?? '');
    }

    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function classic22LinuxDoSaveOptionJson(string $name, array $payload): void
{
    $db = classic22LinuxDoDb();
    if (!is_object($db) || $name === '') {
        return;
    }

    $value = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($value)) {
        return;
    }

    try {
        $exists = $db->fetchObject(
            $db->select('name')
                ->from('table.options')
                ->where('name = ? AND user = ?', $name, 0)
                ->limit(1)
        );

        if (is_object($exists)) {
            $db->query(
                $db->update('table.options')
                    ->rows(['value' => $value])
                    ->where('name = ? AND user = ?', $name, 0)
            );
            return;
        }

        $db->query(
            $db->insert('table.options')
                ->rows([
                    'name' => $name,
                    'value' => $value,
                    'user' => 0,
                ])
        );
    } catch (\Throwable $exception) {
    }
}

function classic22LinuxDoFallbackConfig(bool $refresh = false): array
{
    static $cache = null;

    if ($refresh || !is_array($cache)) {
        $cache = classic22LinuxDoLoadOptionJson(classic22LinuxDoFallbackOptionName());
    }

    return $cache;
}

function classic22LinuxDoThemeConfig(bool $refresh = false): array
{
    static $cache = null;

    if ($refresh || !is_array($cache)) {
        $cache = classic22LinuxDoLoadOptionJson(classic22LinuxDoThemeOptionName());
    }

    return $cache;
}

function classic22LinuxDoSaveFallbackConfig(array $config): void
{
    classic22LinuxDoSaveOptionJson(classic22LinuxDoFallbackOptionName(), $config);
    classic22LinuxDoFallbackConfig(true);
}

function classic22LinuxDoExtractSettingsFromRequest(): array
{
    $keys = ['linuxDoClientId', 'linuxDoClientSecret', 'liveWsEnabled', 'liveWsEndpoint'];
    $extracted = [];

    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $extracted[$key] = trim((string) $_POST[$key]);
        }
    }

    $rawBody = '';
    try {
        $rawBody = (string) file_get_contents('php://input');
    } catch (\Throwable $exception) {
        $rawBody = '';
    }

    if ($rawBody === '') {
        return $extracted;
    }

    $decodedBody = json_decode($rawBody, true);
    if (!is_array($decodedBody)) {
        return $extracted;
    }

    $values = [];
    if (isset($decodedBody['values']) && is_array($decodedBody['values'])) {
        $values = $decodedBody['values'];
    }

    foreach ($keys as $key) {
        if (array_key_exists($key, $decodedBody)) {
            $extracted[$key] = trim((string) $decodedBody[$key]);
        }

        if (array_key_exists($key, $values)) {
            $extracted[$key] = trim((string) $values[$key]);
        }
    }

    return $extracted;
}

function themeConfigHandle(array $settings, bool $isInit)
{
    $requestSettings = classic22LinuxDoExtractSettingsFromRequest();
    $existingThemeSettings = classic22LinuxDoThemeConfig();
    $mergedSettings = array_merge($existingThemeSettings, $settings, $requestSettings);
    classic22LinuxDoSaveOptionJson(classic22LinuxDoThemeOptionName(), $mergedSettings);
    classic22LinuxDoThemeConfig(true);

    $fallbackConfig = classic22LinuxDoFallbackConfig();
    $linuxDoKeys = ['linuxDoClientId', 'linuxDoClientSecret'];

    foreach ($linuxDoKeys as $linuxDoKey) {
        if (!array_key_exists($linuxDoKey, $mergedSettings)) {
            continue;
        }

        $newValue = trim((string) $mergedSettings[$linuxDoKey]);

        if (
            $linuxDoKey === 'linuxDoClientSecret'
            && $newValue === ''
            && isset($fallbackConfig[$linuxDoKey])
            && trim((string) $fallbackConfig[$linuxDoKey]) !== ''
        ) {
            continue;
        }

        $fallbackConfig[$linuxDoKey] = $newValue;
    }

    classic22LinuxDoSaveFallbackConfig($fallbackConfig);
}

function classic22LinuxDoGetOption($options, string $key, string $default = ''): string
{
    if (!is_object($options)) {
        $fallback = classic22LinuxDoFallbackConfig();
        if (isset($fallback[$key]) && !is_array($fallback[$key]) && !is_object($fallback[$key])) {
            $value = trim((string) $fallback[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return $default;
    }

    $value = null;

    try {
        $value = $options->{$key};
    } catch (\Throwable $exception) {
    }

    if ($value !== null && !is_array($value) && !is_object($value)) {
        $normalized = trim((string) $value);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    $themeConfig = classic22LinuxDoThemeConfig();
    if (isset($themeConfig[$key]) && !is_array($themeConfig[$key]) && !is_object($themeConfig[$key])) {
        $normalized = trim((string) $themeConfig[$key]);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    $fallback = classic22LinuxDoFallbackConfig();
    if (isset($fallback[$key]) && !is_array($fallback[$key]) && !is_object($fallback[$key])) {
        $normalized = trim((string) $fallback[$key]);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    return $default;
}

function classic22LinuxDoDetectBaseUrlFromServer(): string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '/';
    }

    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $httpsEnabled = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || $forwardedProto === 'https';

    $scheme = $httpsEnabled ? 'https' : 'http';
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
    $basePath = str_replace('\\', '/', dirname($scriptName));
    if ($basePath === '.' || $basePath === '/') {
        $basePath = '';
    }

    return $scheme . '://' . $host . $basePath . '/';
}

function classic22LinuxDoSiteBaseUrl($options): string
{
    $siteUrl = classic22LinuxDoGetOption($options, 'siteUrl', '/');
    if ($siteUrl === '' || $siteUrl === '/') {
        return classic22LinuxDoDetectBaseUrlFromServer();
    }

    if (!preg_match('/^https?:\/\//i', $siteUrl)) {
        return classic22LinuxDoDetectBaseUrlFromServer();
    }

    return rtrim($siteUrl, '/') . '/';
}

function classic22LinuxDoCallbackUrl($options): string
{
    return classic22LinuxDoSiteBaseUrl($options) . '?ldo_action=callback';
}

function classic22LinuxDoNormalizeReturnTo(string $returnTo, $options): string
{
    $fallback = classic22LinuxDoSiteBaseUrl($options);
    $returnTo = trim($returnTo);

    if ($returnTo === '' || strpos($returnTo, '//') === 0) {
        return $fallback;
    }

    $siteParts = parse_url($fallback);
    $returnParts = parse_url($returnTo);

    if (is_array($returnParts) && isset($returnParts['scheme'])) {
        $siteScheme = strtolower((string) ($siteParts['scheme'] ?? ''));
        $siteHost = strtolower((string) ($siteParts['host'] ?? ''));
        $sitePort = (int) ($siteParts['port'] ?? 0);

        $returnScheme = strtolower((string) ($returnParts['scheme'] ?? ''));
        $returnHost = strtolower((string) ($returnParts['host'] ?? ''));
        $returnPort = (int) ($returnParts['port'] ?? 0);

        if ($siteScheme !== $returnScheme || $siteHost !== $returnHost) {
            return $fallback;
        }

        if ($sitePort > 0 && $returnPort > 0 && $sitePort !== $returnPort) {
            return $fallback;
        }

        return $returnTo;
    }

    if (strpos($returnTo, '?') === 0) {
        return $fallback . ltrim($returnTo, '?');
    }

    if (strpos($returnTo, '/') === 0) {
        return rtrim($fallback, '/') . $returnTo;
    }

    return rtrim($fallback, '/') . '/' . ltrim($returnTo, '/');
}

function classic22LinuxDoBuildActionUrl($options, string $action, string $returnTo = ''): string
{
    $params = ['ldo_action' => $action];

    if ($returnTo !== '') {
        $params['return_to'] = classic22LinuxDoNormalizeReturnTo($returnTo, $options);
    }

    return classic22LinuxDoSiteBaseUrl($options) . '?' . http_build_query($params);
}

function classic22LinuxDoIsConfigured($options): bool
{
    return classic22LinuxDoGetOption($options, 'linuxDoClientId') !== '';
}

function classic22LinuxDoHasClientSecret($options): bool
{
    return classic22LinuxDoGetOption($options, 'linuxDoClientSecret') !== '';
}

function classic22LinuxDoCookieGet(string $key, string $default = ''): string
{
    if (class_exists('\\Typecho\\Cookie')) {
        $value = \Typecho\Cookie::get($key, $default);
        return is_string($value) ? $value : $default;
    }

    return $default;
}

function classic22LinuxDoCookieSet(string $key, string $value, int $expire = 0): void
{
    if (class_exists('\\Typecho\\Cookie')) {
        \Typecho\Cookie::set($key, $value, $expire);
    }
}

function classic22LinuxDoCookieDelete(string $key): void
{
    if (class_exists('\\Typecho\\Cookie')) {
        \Typecho\Cookie::delete($key);
    }
}

function classic22LinuxDoRedirect($archive, string $location, bool $permanent = false): void
{
    $location = \Typecho\Common::safeUrl($location);

    if (!headers_sent()) {
        header('Location: ' . $location, true, $permanent ? 301 : 302);
        exit;
    }

    $escaped = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8">',
        '<meta http-equiv="refresh" content="0;url=',
        $escaped,
        '">',
        '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
        '<title>Redirecting...</title></head><body>',
        '<p><a href="',
        $escaped,
        '">继续跳转</a></p>',
        '</body></html>';
    exit;
}

function classic22LinuxDoBase64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function classic22LinuxDoBase64UrlDecode(string $data): string
{
    $data = strtr($data, '-_', '+/');
    $pad = strlen($data) % 4;
    if ($pad !== 0) {
        $data .= str_repeat('=', 4 - $pad);
    }

    $decoded = base64_decode($data, true);
    return $decoded === false ? '' : $decoded;
}

function classic22LinuxDoSigningKey($options): string
{
    $secret = classic22LinuxDoGetOption($options, 'secret');
    if ($secret === '') {
        $secret = classic22LinuxDoSiteBaseUrl($options);
    }

    return 'classic22-linuxdo|' . $secret;
}

function classic22LinuxDoMakeSignedPayload(array $data, $options): string
{
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $payload = classic22LinuxDoBase64UrlEncode($json === false ? '{}' : $json);
    $sig = hash_hmac('sha256', $payload, classic22LinuxDoSigningKey($options));
    return $payload . '.' . $sig;
}

function classic22LinuxDoParseSignedPayload(string $value, $options): ?array
{
    $value = trim($value);
    if ($value === '' || strpos($value, '.') === false) {
        return null;
    }

    [$payload, $sig] = explode('.', $value, 2);
    if ($payload === '' || $sig === '') {
        return null;
    }

    $expected = hash_hmac('sha256', $payload, classic22LinuxDoSigningKey($options));
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    $json = classic22LinuxDoBase64UrlDecode($payload);
    if ($json === '') {
        return null;
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function classic22LinuxDoMakeStateToken(string $returnTo, $options): string
{
    $returnTo = classic22LinuxDoNormalizeReturnTo($returnTo, $options);
    return classic22LinuxDoMakeSignedPayload([
        't' => time(),
        'r' => $returnTo,
        'n' => classic22LinuxDoGenerateState(),
    ], $options);
}

function classic22LinuxDoParseStateToken(string $state, $options): ?string
{
    $data = classic22LinuxDoParseSignedPayload($state, $options);
    if (!is_array($data)) {
        return null;
    }

    $issuedAt = (int) ($data['t'] ?? 0);
    $returnTo = (string) ($data['r'] ?? '');
    if ($issuedAt <= 0 || $returnTo === '') {
        return null;
    }

    $now = time();
    if ($issuedAt > $now + 300 || $issuedAt < $now - 3600) {
        return null;
    }

    return classic22LinuxDoNormalizeReturnTo($returnTo, $options);
}

function classic22LinuxDoPersistUser(array $identity, $options): void
{
    classic22LinuxDoEnsureSession();
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['classic22_linuxdo_user'] = $identity;
    }

    $cookieValue = classic22LinuxDoMakeSignedPayload([
        't' => time(),
        'u' => [
            'id' => (string) ($identity['id'] ?? ''),
            'username' => (string) ($identity['username'] ?? ''),
            'name' => (string) ($identity['name'] ?? ''),
            'author' => (string) ($identity['author'] ?? ''),
            'mail' => (string) ($identity['mail'] ?? ''),
            'url' => (string) ($identity['url'] ?? ''),
        ],
    ], $options);

    classic22LinuxDoCookieSet('__classic22_linuxdo_user', $cookieValue, 14 * 24 * 3600);
}

function classic22LinuxDoReadUserFromCookie($options): ?array
{
    $raw = classic22LinuxDoCookieGet('__classic22_linuxdo_user', '');
    if ($raw === '') {
        return null;
    }

    $data = classic22LinuxDoParseSignedPayload($raw, $options);
    if (!is_array($data) || empty($data['u']) || !is_array($data['u'])) {
        return null;
    }

    $issuedAt = (int) ($data['t'] ?? 0);
    if ($issuedAt > 0 && $issuedAt < time() - 30 * 24 * 3600) {
        return null;
    }

    $user = $data['u'];
    $author = trim((string) ($user['author'] ?? ''));
    $mail = trim((string) ($user['mail'] ?? ''));
    if ($author === '' || $mail === '') {
        return null;
    }

    return [
        'id' => (string) ($user['id'] ?? ''),
        'username' => (string) ($user['username'] ?? ''),
        'name' => (string) ($user['name'] ?? ''),
        'author' => $author,
        'mail' => $mail,
        'url' => (string) ($user['url'] ?? ''),
        'login_at' => $issuedAt > 0 ? $issuedAt : time(),
    ];
}

function classic22LinuxDoEnsureSession(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
}

function classic22LinuxDoSetError(string $message): void
{
    classic22LinuxDoEnsureSession();

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['classic22_linuxdo_error'] = $message;
        return;
    }

    classic22LinuxDoCookieSet('__classic22_linuxdo_error', classic22LinuxDoBase64UrlEncode($message), 300);
}

function classic22LinuxDoConsumeError(): string
{
    classic22LinuxDoEnsureSession();

    if (session_status() === PHP_SESSION_ACTIVE) {
        $message = (string) ($_SESSION['classic22_linuxdo_error'] ?? '');
        unset($_SESSION['classic22_linuxdo_error']);

        if ($message !== '') {
            return $message;
        }
    }

    $raw = classic22LinuxDoCookieGet('__classic22_linuxdo_error', '');
    if ($raw === '') {
        return '';
    }

    classic22LinuxDoCookieDelete('__classic22_linuxdo_error');
    return classic22LinuxDoBase64UrlDecode($raw);
}

function classic22LinuxDoCurrentUser($options = null): ?array
{
    classic22LinuxDoEnsureSession();

    if (session_status() === PHP_SESSION_ACTIVE) {
        $user = $_SESSION['classic22_linuxdo_user'] ?? null;
        if (is_array($user) && !empty($user['author']) && !empty($user['mail'])) {
            return $user;
        }
    }

    if ($options === null) {
        return null;
    }

    return classic22LinuxDoReadUserFromCookie($options);
}

function classic22LinuxDoClearSession(): void
{
    classic22LinuxDoEnsureSession();

    if (session_status() === PHP_SESSION_ACTIVE) {
        unset(
            $_SESSION['classic22_linuxdo_user'],
            $_SESSION['classic22_linuxdo_state'],
            $_SESSION['classic22_linuxdo_return_to'],
            $_SESSION['classic22_linuxdo_error']
        );
    }

    classic22LinuxDoCookieDelete('__classic22_linuxdo_user');
    classic22LinuxDoCookieDelete('__classic22_linuxdo_error');
}

function classic22LinuxDoGenerateState(): string
{
    if (function_exists('random_bytes')) {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable $exception) {
        }
    }

    return md5(uniqid((string) mt_rand(), true));
}

function classic22LinuxDoHttpRequest(string $url, string $method = 'GET', string $body = '', array $headers = []): array
{
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 0,
            'body' => '',
            'error' => '服务器未启用 cURL 扩展',
        ];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $responseBody = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($responseBody === false) {
        return [
            'ok' => false,
            'status' => $status,
            'body' => '',
            'error' => $error !== '' ? $error : '请求失败',
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => (string) $responseBody,
        'error' => $error,
    ];
}

function classic22LinuxDoExchangeCodeForToken(string $code, $options): array
{
    if (!classic22LinuxDoHasClientSecret($options)) {
        return ['ok' => false, 'message' => '请先在主题设置中填写 Linux Do Client Secret。'];
    }

    $payload = http_build_query([
        'client_id' => classic22LinuxDoGetOption($options, 'linuxDoClientId'),
        'client_secret' => classic22LinuxDoGetOption($options, 'linuxDoClientSecret'),
        'code' => $code,
        'redirect_uri' => classic22LinuxDoCallbackUrl($options),
        'grant_type' => 'authorization_code',
    ]);

    $response = classic22LinuxDoHttpRequest(
        'https://connect.linux.do/oauth2/token',
        'POST',
        $payload,
        [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ]
    );

    $message = '获取访问令牌失败';
    if (!$response['ok']) {
        $data = json_decode((string) $response['body'], true);
        if (is_array($data) && !empty($data['error_description'])) {
            $message = $message . '：' . (string) $data['error_description'];
        } elseif (!empty($response['error'])) {
            $message = $message . '：' . (string) $response['error'];
        }

        return ['ok' => false, 'message' => $message];
    }

    $data = json_decode((string) $response['body'], true);
    if (!is_array($data) || empty($data['access_token'])) {
        return ['ok' => false, 'message' => $message];
    }

    return ['ok' => true, 'access_token' => (string) $data['access_token']];
}

function classic22LinuxDoFetchUserInfo(string $accessToken): array
{
    $response = classic22LinuxDoHttpRequest(
        'https://connect.linux.do/api/user',
        'GET',
        '',
        [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
        ]
    );

    if (!$response['ok']) {
        return ['ok' => false, 'message' => '获取用户信息失败'];
    }

    $data = json_decode((string) $response['body'], true);
    if (!is_array($data)) {
        return ['ok' => false, 'message' => '用户信息格式无效'];
    }

    return ['ok' => true, 'data' => $data];
}

function classic22LinuxDoMapIdentity(array $userData): ?array
{
    $payload = isset($userData['user']) && is_array($userData['user']) ? $userData['user'] : $userData;

    $id = trim((string) ($payload['id'] ?? ''));
    $username = trim((string) ($payload['username'] ?? ''));
    $name = trim((string) ($payload['name'] ?? ''));

    if ($id === '' && $username === '') {
        return null;
    }

    $author = $name !== '' ? $name : $username;
    if ($author === '') {
        $author = 'Linux Do 用户';
    }

    $mailSeed = strtolower((string) ($id !== '' ? $id : $username));
    $mailSeed = preg_replace('/[^a-z0-9_\.-]+/i', '.', $mailSeed ?? '');
    $mailSeed = trim((string) $mailSeed, '.');
    if ($mailSeed === '') {
        $mailSeed = substr(md5($author), 0, 12);
    }

    $profileUrl = 'https://linux.do';
    if ($username !== '') {
        $profileUrl = 'https://linux.do/u/' . rawurlencode($username);
    }

    return [
        'id' => $id,
        'username' => $username,
        'name' => $name,
        'author' => $author,
        'mail' => $mailSeed . '@linux.do',
        'url' => $profileUrl,
        'avatar_template' => (string) ($payload['avatar_template'] ?? ''),
        'login_at' => time(),
    ];
}

function classic22LinuxDoHandleLogin($archive): void
{
    $options = $archive->options;
    $returnTo = classic22LinuxDoNormalizeReturnTo((string) $archive->request->get('return_to'), $options);
    $state = classic22LinuxDoMakeStateToken($returnTo, $options);

    $authorizeUrl = 'https://connect.linux.do/oauth2/authorize?' . http_build_query([
        'client_id' => classic22LinuxDoGetOption($options, 'linuxDoClientId'),
        'redirect_uri' => classic22LinuxDoCallbackUrl($options),
        'response_type' => 'code',
        'scope' => 'user',
        'state' => $state,
    ]);

    classic22LinuxDoRedirect($archive, $authorizeUrl);
}

function classic22LinuxDoHandleCallback($archive): void
{
    $options = $archive->options;
    $fallback = classic22LinuxDoSiteBaseUrl($options);
    $state = trim((string) $archive->request->get('state'));
    $returnToFromState = $state !== '' ? classic22LinuxDoParseStateToken($state, $options) : null;
    $redirectTo = $returnToFromState ?: $fallback;

    $oauthError = trim((string) $archive->request->get('error'));
    if ($oauthError !== '') {
        classic22LinuxDoSetError('Linux Do 授权失败：' . $oauthError);
        classic22LinuxDoRedirect($archive, $redirectTo);
    }

    $code = trim((string) $archive->request->get('code'));

    if ($code === '' || $state === '' || $returnToFromState === null) {
        classic22LinuxDoSetError('Linux Do 授权状态校验失败，请重试。');
        classic22LinuxDoRedirect($archive, $redirectTo);
    }

    $tokenResult = classic22LinuxDoExchangeCodeForToken($code, $options);
    if (empty($tokenResult['ok'])) {
        classic22LinuxDoSetError((string) ($tokenResult['message'] ?? '获取访问令牌失败'));
        classic22LinuxDoRedirect($archive, $redirectTo);
    }

    $userResult = classic22LinuxDoFetchUserInfo((string) $tokenResult['access_token']);
    if (empty($userResult['ok'])) {
        classic22LinuxDoSetError((string) ($userResult['message'] ?? '获取用户信息失败'));
        classic22LinuxDoRedirect($archive, $redirectTo);
    }

    $identity = classic22LinuxDoMapIdentity((array) $userResult['data']);
    if ($identity === null) {
        classic22LinuxDoSetError('用户信息无效，无法完成登录。');
        classic22LinuxDoRedirect($archive, $redirectTo);
    }

    classic22LinuxDoPersistUser($identity, $options);
    classic22LinuxDoCookieDelete('__classic22_linuxdo_error');

    classic22LinuxDoRedirect($archive, $redirectTo);
}

function classic22LinuxDoHandleRequest($archive): void
{
    if (!is_object($archive)) {
        return;
    }

    $action = trim((string) $archive->request->get('ldo_action'));
    if (!in_array($action, ['login', 'logout', 'callback'], true)) {
        return;
    }

    $returnTo = classic22LinuxDoNormalizeReturnTo((string) $archive->request->get('return_to'), $archive->options);

    if ($action === 'logout') {
        classic22LinuxDoClearSession();
        classic22LinuxDoRedirect($archive, $returnTo);
    }

    if (!classic22LinuxDoIsConfigured($archive->options)) {
        classic22LinuxDoSetError('请先在主题设置中填写 Linux Do Client ID。');
        classic22LinuxDoRedirect($archive, $returnTo);
    }

    if ($action === 'login') {
        classic22LinuxDoHandleLogin($archive);
    }

    if ($action === 'callback') {
        classic22LinuxDoHandleCallback($archive);
    }
}

function themeInit($archive)
{
    if (is_object($archive) && classic22LinuxDoIsConfigured($archive->options)) {
        classic22LinuxDoEnsureSession();
    }

    classic22LinuxDoHandleRequest($archive);
}

function postMeta(
    \Widget\Archive $archive,
    string $metaType = 'archive'
)
{
?>
    <header class="entry-header text-center">
        <h1 class="entry-title" itemprop="name headline">
            <a href="<?php $archive->permalink() ?>" itemprop="url"><?php $archive->title() ?></a>
        </h1>
        <?php if ($metaType != 'page'): ?>
        <ul class="entry-meta list-inline text-muted">
            <li class="feather-calendar"><time datetime="<?php $archive->date('c'); ?>" itemprop="datePublished"><?php $archive->date(); ?></time></li>
            <li class="feather-folder"><?php $archive->category(', '); ?></li>
            <li class="feather-message"><a href="<?php $archive->permalink() ?>#comments"  itemprop="discussionUrl"><?php $archive->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></a></li>
        </ul>
        <?php endif; ?>
    </header>
<?php
}

function postCoverUrl(\Widget\Archive $archive): ?string
{
    foreach (['cover', 'thumb', 'thumbnail', 'image'] as $field) {
        if (isset($archive->fields->{$field}) && !empty($archive->fields->{$field})) {
            return (string) $archive->fields->{$field};
        }
    }

    $html = $archive->excerpt ?: $archive->content;
    if (empty($html)) {
        return null;
    }

    if (preg_match('/<img[^>]+(?:data-src|data-original|data-lazy-src)=[\'"]([^\'"]+)[\'"]/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, $archive->options->charset);
    }

    if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $html, $matches)) {
        $src = html_entity_decode($matches[1], ENT_QUOTES, $archive->options->charset);

        if (strpos($src, 'data:') === 0) {
            return null;
        }

        return $src;
    }

    return null;
}

function postExcerptText(\Widget\Archive $archive, int $length = 140, string $trim = '...'): string
{
    $text = strip_tags($archive->excerpt ?? '');
    $text = html_entity_decode($text, ENT_QUOTES, $archive->options->charset);
    $text = trim(preg_replace('/\\s+/u', ' ', $text ?? ''));

    if ($text === '') {
        $text = html_entity_decode($archive->title, ENT_QUOTES, $archive->options->charset);
    }

    return \Typecho\Common::subStr($text, 0, $length, $trim);
}

function classic22ParseHomeAnnouncements(?string $raw): array
{
    if (empty($raw)) {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }

    $allowedTypes = ['notice', 'info', 'warning', 'activity'];
    $items = [];

    foreach ($data as $item) {
        if (!is_array($item)) {
            continue;
        }

        $content = trim((string) ($item['content'] ?? $item['text'] ?? ''));
        if ($content === '') {
            continue;
        }

        $type = (string) ($item['type'] ?? 'notice');
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'notice';
        }

        $emoji = trim((string) ($item['emoji'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));
        if ($url !== '') {
            $url = \Typecho\Common::safeUrl($url);
        }

        $items[] = [
            'type' => $type,
            'emoji' => $emoji,
            'content' => $content,
            'url' => $url,
        ];
    }

    return $items;
}

function classic22HomeAnnouncementIconSvg(string $type): string
{
    switch ($type) {
        case 'info':
            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
        case 'warning':
            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 9-6 6"/><path d="M2.586 16.726A2 2 0 0 1 2 15.312V8.688a2 2 0 0 1 .586-1.414l4.688-4.688A2 2 0 0 1 8.688 2h6.624a2 2 0 0 1 1.414.586l4.688 4.688A2 2 0 0 1 22 8.688v6.624a2 2 0 0 1-.586 1.414l-4.688 4.688a2 2 0 0 1-1.414.586H8.688a2 2 0 0 1-1.414-.586z"/><path d="m9 9 6 6"/></svg>';
        case 'activity':
            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5.8 11.3 2 22l10.7-3.79"/><path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2h.01"/><path d="M22 20h.01"/><path d="m22 2-2.24.75a2.9 2.9 0 0 0-1.96 3.12c.1.86-.57 1.63-1.45 1.63h-.38c-.86 0-1.6.6-1.76 1.44L14 10"/><path d="m22 13-.82-.33c-.86-.34-1.82.2-1.98 1.11c-.11.7-.72 1.22-1.43 1.22H17"/><path d="m11 2 .33.82c.34.86-.2 1.82-1.11 1.98C9.52 4.9 9 5.52 9 6.23V7"/><path d="M11 13c1.93 1.93 2.83 4.17 2 5-.83.83-3.07-.07-5-2-1.93-1.93-2.83-4.17-2-5 .83-.83 3.07.07 5 2Z"/></svg>';
        case 'notice':
        default:
            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/><path d="M8 6v8"/></svg>';
    }
}
