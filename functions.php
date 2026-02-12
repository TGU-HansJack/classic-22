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

    $linuxDoCommentEnabled = new \Typecho\Widget\Helper\Form\Element\Select(
        'linuxDoCommentEnabled',
        [
            '1' => _t('开启'),
            '0' => _t('关闭'),
        ],
        '1',
        _t('评论区 Linux Do 登录'),
        _t('关闭后评论区不显示 Linux Do 登录按钮，也不会自动带入 Linux Do 用户信息。')
    );
    $form->addInput($linuxDoCommentEnabled);

    $v3aPostLimitSeconds = new \Typecho\Widget\Helper\Form\Element\Text(
        'v3aPostLimitSeconds',
        null,
        '60',
        _t('投稿提交频率限制（秒）'),
        _t('用于投稿页防刷。填写 0 表示不限制。')
    );
    $form->addInput($v3aPostLimitSeconds);

    $v3aPostRecaptchaV3SiteKey = new \Typecho\Widget\Helper\Form\Element\Text(
        'v3aPostRecaptchaV3SiteKey',
        null,
        '',
        _t('投稿页 reCAPTCHA v3 Site Key（可选）'),
        _t('用于投稿页防刷。留空表示关闭。')
    );
    $form->addInput($v3aPostRecaptchaV3SiteKey);

    $v3aPostRecaptchaV3SecretKey = new \Typecho\Widget\Helper\Form\Element\Password(
        'v3aPostRecaptchaV3SecretKey',
        null,
        (string) (classic22LinuxDoFallbackConfig()['v3aPostRecaptchaV3SecretKey'] ?? ''),
        _t('投稿页 reCAPTCHA v3 Secret Key（可选）'),
        _t('服务端使用。留空将保留已保存的 Key；如需关闭，请清空 Site Key。')
    );
    $form->addInput($v3aPostRecaptchaV3SecretKey);

    $v3aPostAiPrompt = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'v3aPostAiPrompt',
        null,
        "你是博客投稿内容生成助手。你将得到一个网页链接，以及从网页解析出的标题/作者/描述/正文文本。\n请基于这些信息生成一篇用于本站发布的投稿文章，并只返回严格 JSON（不要代码块、不要附加文字）。\n\nJSON 格式：\n{\n  \"title\": \"\",\n  \"content\": \"\",\n  \"project_link\": \"\",\n  \"project_type\": \"typecho|halo\",\n  \"project_author\": \"\"\n}\n\n要求：\n- 禁止编造不存在的功能、数据、作者信息；不确定的字段请返回空字符串。\n- title：简短明确（<=60字）。\n- project_link：必须是有效 URL，优先项目主页/仓库地址；否则使用原始链接。\n- project_type：只能是 typecho 或 halo；无法判断则根据内容关键词推断，仍不确定则留空。\n- content：使用 Markdown，包含：简介、主要特性、安装/使用、相关链接（至少包含 project_link）。",
        _t('投稿 AI 内容生成提示词'),
        _t('用于投稿页「输入链接 → 解析网页 → AI 生成内容」。留空则使用内置默认提示词。')
    );
    $form->addInput($v3aPostAiPrompt);

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

    $aiEnabled = new \Typecho\Widget\Helper\Form\Element\Select(
        'aiEnabled',
        [
            '1' => _t('开启'),
            '0' => _t('关闭'),
        ],
        '1',
        _t('首页 AI 对话'),
        _t('在首页文章列表上方显示 AI 对话输入框。')
    );
    $form->addInput($aiEnabled);

    $aiAllowedDomains = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'aiAllowedDomains',
        null,
        '',
        _t('AI 允许域名（可选）'),
        _t('限制 AI 接口仅允许这些域名页面调用。每行一个域名（如 craft.hansjack.com）；留空时默认仅允许当前站点域名。')
    );
    $form->addInput($aiAllowedDomains);

    $aiProvider = new \Typecho\Widget\Helper\Form\Element\Select(
        'aiProvider',
        [
            'openai' => _t('OpenAI 兼容接口'),
            'rightcode' => _t('Right Code (GPT-Codex)'),
        ],
        'openai',
        _t('AI 接口类型'),
        _t('支持 OpenAI 兼容接口与 Right Code。')
    );
    $form->addInput($aiProvider);

    $aiApiMode = new \Typecho\Widget\Helper\Form\Element\Select(
        'aiApiMode',
        [
            'chat_completions' => _t('chat/completions 兼容接口'),
            'responses' => _t('responses 接口（Right Code 推荐）'),
        ],
        'chat_completions',
        _t('AI 请求模式'),
        _t('OpenAI 兼容通常使用 chat/completions；Right Code 建议使用 responses。')
    );
    $form->addInput($aiApiMode);

    $aiApiBaseUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'aiApiBaseUrl',
        null,
        'https://api.openai.com/v1',
        _t('AI 接口地址'),
        _t('例如 https://api.openai.com/v1 或其他 OpenAI 兼容服务地址。')
    );
    $form->addInput($aiApiBaseUrl);

    $aiApiKey = new \Typecho\Widget\Helper\Form\Element\Password(
        'aiApiKey',
        null,
        (string) (classic22LinuxDoFallbackConfig()['aiApiKey'] ?? ''),
        _t('AI API Key'),
        _t('用于服务端请求 AI。留空时将保留已保存的 Key。')
    );
    $form->addInput($aiApiKey);

    $aiModels = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'aiModels',
        null,
        "gpt-4o-mini\ngpt-4.1-mini\ngpt-4o",
        _t('AI 模型列表'),
        _t('每行一个模型名称，首页右下角下拉框将显示此列表。')
    );
    $form->addInput($aiModels);

    $aiDefaultModel = new \Typecho\Widget\Helper\Form\Element\Text(
        'aiDefaultModel',
        null,
        'gpt-4o-mini',
        _t('默认 AI 模型'),
        _t('为空时自动使用模型列表第一项。')
    );
    $form->addInput($aiDefaultModel);

    $aiSystemPrompt = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'aiSystemPrompt',
        null,
        '你是博客首页 AI 助手，请使用中文简洁回答用户问题。',
        _t('AI 系统提示词（可选）'),
        _t('可留空；留空时使用主题内置提示词。')
    );
    $form->addInput($aiSystemPrompt);


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
    $keys = [
        'linuxDoClientId',
        'linuxDoClientSecret',
        'linuxDoCommentEnabled',
        'v3aPostLimitSeconds',
        'v3aPostRecaptchaV3SiteKey',
        'v3aPostRecaptchaV3SecretKey',
        'v3aPostAiPrompt',
        'liveWsEnabled',
        'liveWsEndpoint',
        'aiEnabled',
        'aiAllowedDomains',
        'aiProvider',
        'aiApiMode',
        'aiApiBaseUrl',
        'aiApiKey',
        'aiModels',
        'aiDefaultModel',
        'aiSystemPrompt',
    ];
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
    $secretKeys = ['linuxDoClientId', 'linuxDoClientSecret', 'aiApiKey', 'v3aPostRecaptchaV3SecretKey'];

    foreach ($secretKeys as $secretKey) {
        if (!array_key_exists($secretKey, $mergedSettings)) {
            continue;
        }

        $newValue = trim((string) $mergedSettings[$secretKey]);

        if (
            in_array($secretKey, ['linuxDoClientSecret', 'aiApiKey', 'v3aPostRecaptchaV3SecretKey'], true)
            && $newValue === ''
            && isset($fallbackConfig[$secretKey])
            && trim((string) $fallbackConfig[$secretKey]) !== ''
        ) {
            continue;
        }

        $fallbackConfig[$secretKey] = $newValue;
    }

    classic22LinuxDoSaveFallbackConfig($fallbackConfig);
}

function classic22LinuxDoGetOption($options, string $key, string $default = ''): string
{
    if (!is_object($options)) {
        $themeConfig = classic22LinuxDoThemeConfig();
        if (isset($themeConfig[$key]) && !is_array($themeConfig[$key]) && !is_object($themeConfig[$key])) {
            $value = trim((string) $themeConfig[$key]);
            if ($value !== '') {
                return $value;
            }
        }

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
    curl_setopt($ch, CURLOPT_ENCODING, '');

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
    classic22Vue3AdminTranslateHandleRequest($archive);
    classic22AiHandleRequest($archive);

    if (is_object($archive) && classic22LinuxDoIsConfigured($archive->options)) {
        classic22LinuxDoEnsureSession();
    }

    classic22LinuxDoHandleRequest($archive);
}

function classic22Vue3AdminTranslateHandleRequest($archive): void
{
    // Note: $archive->options is a protected property, and may not be accessible via isset() in all contexts.
    // The handler itself can work without directly relying on $archive->options.
    if (!is_object($archive) || !isset($archive->request)) {
        return;
    }

    $flag = trim((string) $archive->request->get('classic22_translate'));
    if ($flag === '') {
        return;
    }

    $cid = 0;
    try {
        $cid = (int) $archive->request->filter('int')->get('cid', 0);
    } catch (\Throwable $exception) {
        $cid = 0;
    }
    if ($cid <= 0) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '缺少文章 ID。',
        ], 400);
    }

    $lang = '';
    try {
        $lang = strtolower(trim((string) $archive->request->get('lang')));
    } catch (\Throwable $exception) {
        $lang = '';
    }
    $lang = preg_replace('/[^0-9a-z-]+/i', '', $lang);
    $lang = is_string($lang) ? $lang : '';
    if ($lang === '') {
        classic22AiSendJson([
            'ok' => false,
            'message' => '缺少语言参数。',
        ], 400);
    }

    $allowedLangs = classic22PostTranslateLangOptions($archive->options);
    if (!in_array($lang, $allowedLangs, true)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '语言不受支持。',
        ], 400);
    }

    $ctype = 'post';
    $isMarkdown = false;

    $contentRow = null;
    try {
        $db = classic22LinuxDoDb();
        if (is_object($db)) {
            $contentRow = $db->fetchRow(
                $db->select('cid', 'type', 'text', 'status', 'password', 'authorId')
                    ->from('table.contents')
                    ->where('cid = ?', $cid)
                    ->limit(1)
            );
        }
    } catch (\Throwable $exception) {
        $contentRow = null;
    }

    if (is_object($contentRow)) {
        $contentRow = (array) $contentRow;
    }

    if (!is_array($contentRow)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '文章不存在。',
        ], 404);
    }

    $rawType = strtolower(trim((string) ($contentRow['type'] ?? '')));
    if ($rawType === 'page' || $rawType === 'page_draft') {
        $ctype = 'page';
    } elseif ($rawType === 'post' || $rawType === 'post_draft') {
        $ctype = 'post';
    } else {
        classic22AiSendJson([
            'ok' => false,
            'message' => '文章类型不支持。',
        ], 404);
    }

    $rawText = (string) ($contentRow['text'] ?? '');
    $isMarkdown = 0 === strpos($rawText, '<!--markdown-->');

    $status = strtolower(trim((string) ($contentRow['status'] ?? '')));
    $password = (string) ($contentRow['password'] ?? '');
    $authorId = (int) ($contentRow['authorId'] ?? 0);

    $userUid = 0;
    $userIsEditor = false;
    try {
        if (class_exists('\\Widget\\User')) {
            $user = \Widget\User::alloc();
            $userUid = (int) ($user->uid ?? 0);
            try {
                $userIsEditor = (bool) $user->pass('editor', true);
            } catch (\Throwable $exception) {
                $userIsEditor = false;
            }
        }
    } catch (\Throwable $exception) {
        $userUid = 0;
        $userIsEditor = false;
    }

    $userCanBypass = $userUid > 0 && ($userUid === $authorId || $userIsEditor);

    // Avoid leaking unpublished/private content on a public endpoint.
    if ($status !== 'publish' && !$userCanBypass) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '文章不存在。',
        ], 404);
    }

    // Password-protected contents: require correct cookie unless author/editor.
    if (trim($password) !== '' && !$userCanBypass) {
        $cookiePassword = '';
        try {
            if (class_exists('\\Typecho\\Cookie')) {
                $cookiePassword = (string) \Typecho\Cookie::get('protectPassword_' . $cid);
            } elseif (class_exists('Typecho_Cookie')) {
                $cookiePassword = (string) \Typecho_Cookie::get('protectPassword_' . $cid);
            }
        } catch (\Throwable $exception) {
            $cookiePassword = '';
        }

        if ($cookiePassword !== $password) {
            classic22AiSendJson([
                'ok' => false,
                'message' => '此内容被密码保护。',
            ], 403);
        }
    }

    if (!class_exists('\\TypechoPlugin\\Vue3Admin\\Ai')) {
        $aiFile = __TYPECHO_ROOT_DIR__ . '/usr/plugins/Vue3Admin/Ai.php';
        if (is_file($aiFile)) {
            require_once $aiFile;
        }
    }

    if (!class_exists('\\TypechoPlugin\\Vue3Admin\\Ai') || !method_exists('\\TypechoPlugin\\Vue3Admin\\Ai', 'getTranslation')) {
        classic22AiSendJson([
            'ok' => false,
            'message' => 'Vue3Admin 翻译功能不可用。',
        ], 500);
    }

    try {
        $translation = \TypechoPlugin\Vue3Admin\Ai::getTranslation($cid, $ctype, $lang);
    } catch (\Throwable $exception) {
        $translation = null;
    }

    if (!is_array($translation)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '暂无该语言翻译内容。',
        ], 404);
    }

    $title = trim((string) ($translation['title'] ?? ''));
    $text = (string) ($translation['text'] ?? '');
    if (trim($text) === '') {
        classic22AiSendJson([
            'ok' => false,
            'message' => '翻译内容为空。',
        ], 404);
    }

    if ($isMarkdown) {
        if (0 === strpos($text, '<!--markdown-->')) {
            $text = substr($text, 15);
        }
        $html = \Utils\Markdown::convert($text);
    } else {
        $autoP = new \Utils\AutoP();
        $html = $autoP->parse($text);
    }

    try {
        $html = (string) \Typecho\Common::fixHtml($html);
    } catch (\Throwable $exception) {
    }

    classic22AiSendJson([
        'ok' => true,
        'cid' => $cid,
        'ctype' => $ctype,
        'lang' => $lang,
        'title' => $title,
        'html' => $html,
        'updated' => (int) ($translation['updated'] ?? 0),
        'model' => (string) ($translation['model'] ?? ''),
    ]);
}

function classic22PostTranslateLangOptions($options): array
{
    $defaultLang = 'zh';
    $optionsBag = $options;
    $langs = [];

    try {
        if (class_exists('\\Widget\\Options')) {
            $optionsBag = \Widget\Options::alloc();
        }
    } catch (\Throwable $exception) {
        $optionsBag = $options;
    }

    if (!class_exists('\\TypechoPlugin\\Vue3Admin\\Ai')) {
        $aiFile = __TYPECHO_ROOT_DIR__ . '/usr/plugins/Vue3Admin/Ai.php';
        if (is_file($aiFile)) {
            require_once $aiFile;
        }
    }

    if (class_exists('\\TypechoPlugin\\Vue3Admin\\Ai') && method_exists('\\TypechoPlugin\\Vue3Admin\\Ai', 'getConfig')) {
        try {
            $cfg = (array) \TypechoPlugin\Vue3Admin\Ai::getConfig($optionsBag);
            $cfgLangs = $cfg['languages'] ?? [];
            if (is_array($cfgLangs) && !empty($cfgLangs)) {
                $langs = $cfgLangs;
            }
        } catch (\Throwable $exception) {
            $langs = [];
        }
    }

    if (empty($langs)) {
        $raw = '';
        try {
            $raw = trim((string) ($optionsBag->v3a_ai_languages ?? ''));
        } catch (\Throwable $exception) {
            $raw = '';
        }

        if ($raw !== '') {
            if (class_exists('\\TypechoPlugin\\Vue3Admin\\Ai') && method_exists('\\TypechoPlugin\\Vue3Admin\\Ai', 'parseLanguages')) {
                try {
                    $langs = (array) \TypechoPlugin\Vue3Admin\Ai::parseLanguages($raw);
                } catch (\Throwable $exception) {
                    $langs = [];
                }
            } else {
                $parts = preg_split('/[\\s,]+/u', $raw);
                foreach ((array) $parts as $part) {
                    $lang = strtolower(trim((string) $part));
                    $lang = preg_replace('/[^0-9a-z-]+/i', '', $lang);
                    $lang = is_string($lang) ? $lang : '';
                    if ($lang === '') {
                        continue;
                    }
                    if (!in_array($lang, $langs, true)) {
                        $langs[] = $lang;
                    }
                }
            }
        }
    }

    $normalized = [];
    foreach ((array) $langs as $lang) {
        $value = strtolower(trim((string) $lang));
        if ($value === '') {
            continue;
        }
        if (!in_array($value, $normalized, true)) {
            $normalized[] = $value;
        }
    }

    $normalized = array_values(array_filter($normalized, static function ($value) use ($defaultLang) {
        return $value !== $defaultLang;
    }));

    array_unshift($normalized, $defaultLang);
    return $normalized;
}

function postMeta(
    \Widget\Archive $archive,
    string $metaType = 'archive'
)
{
?>
    <header class="entry-header<?php echo $metaType === 'card' ? '' : ' text-center'; ?>">
        <h1 class="entry-title" itemprop="name headline">
            <a href="<?php $archive->permalink() ?>" itemprop="url"><?php $archive->title() ?></a>
        </h1>
        <?php if ($metaType === 'card'): ?>
        <?php
            $postPermalink = (string) $archive->permalink;
            $postPath = (string) (parse_url($postPermalink, PHP_URL_PATH) ?? '');
        ?>
        <div class="post-card-meta-row">
            <ul class="entry-meta list-inline text-muted">
                <li class="feather-calendar"><time datetime="<?php $archive->date('c'); ?>" itemprop="datePublished"><?php $archive->date(); ?></time></li>
                <li class="feather-user" itemprop="author" itemscope itemtype="http://schema.org/Person"><a itemprop="name" href="<?php $archive->author->permalink(); ?>" rel="author"><?php $archive->author(); ?></a></li>
                <li class="feather-folder"><?php $archive->category(', '); ?></li>
                <li class="feather-message"><a href="<?php $archive->permalink() ?>#comments"  itemprop="discussionUrl"><?php $archive->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></a></li>
            </ul>
            <div class="classic22-live-online-badge feather-activity" data-live-online-card data-page-path="<?php echo htmlspecialchars($postPath, ENT_QUOTES, classic22ArchiveCharset($archive)); ?>" data-online-count="0" aria-label="在线人数">
                <span class="classic22-live-online-number" data-live-online-number>0</span>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($metaType != 'page' && $metaType !== 'card'): ?>
        <?php
            $showTranslateSwitch = $metaType === 'post';
            $translateDefaultLang = 'zh';
            $translateLangOptions = ['zh'];
            $translateLangOptionsJson = '["zh"]';
            $translateCurrentLang = $translateDefaultLang;
            $translateApiUrl = '';
            $translateCid = (int) ($archive->cid ?? 0);
            $translateCtype = $metaType === 'post' ? 'post' : 'post';
            $translateIsMarkdown = 0;
            if ($showTranslateSwitch) {
                $translateLangOptions = classic22PostTranslateLangOptions($archive->options);
                $translateLangOptionsJson = (string) (json_encode($translateLangOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '["zh"]');
                try {
                    // Use current post permalink as base to avoid relying on options->index in theme context.
                    $translateApiUrl = trim((string) ($archive->permalink ?? ''));
                    if ($translateApiUrl === '') {
                        $translateApiUrl = trim((string) ($archive->options->index ?? ''));
                    }
                    if ($translateApiUrl !== '') {
                        $translateApiUrl .= (strpos($translateApiUrl, '?') === false ? '?' : '&') . 'classic22_translate=1';
                    }
                } catch (\Throwable $exception) {
                    $translateApiUrl = '';
                }
                $translateCurrentLang = $translateDefaultLang;
                $translateIsMarkdown = $archive->isMarkdown ? 1 : 0;
                if (count($translateLangOptions) <= 1) {
                    $showTranslateSwitch = false;
                }
            }
        ?>
        <?php if ($showTranslateSwitch): ?>
        <div class="classic22-post-meta-row">
        <?php endif; ?>
        <ul class="entry-meta list-inline text-muted">
            <li class="feather-calendar"><time datetime="<?php $archive->date('c'); ?>" itemprop="datePublished"><?php $archive->date(); ?></time></li>
            <li class="feather-user" itemprop="author" itemscope itemtype="http://schema.org/Person"><a itemprop="name" href="<?php $archive->author->permalink(); ?>" rel="author"><?php $archive->author(); ?></a></li>
            <li class="feather-folder"><?php $archive->category(', '); ?></li>
            <li class="feather-message"><a href="<?php $archive->permalink() ?>#comments"  itemprop="discussionUrl"><?php $archive->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></a></li>
        </ul>
        <?php if ($showTranslateSwitch): ?>
        <label class="classic22-post-meta-lang text-muted" aria-label="切换语言">
            <span class="classic22-post-meta-lang-label">语言</span>
            <select
                data-post-lang-switch
                data-default-lang="<?php echo htmlspecialchars($translateDefaultLang, ENT_QUOTES, classic22ArchiveCharset($archive)); ?>"
                data-current-lang="<?php echo htmlspecialchars($translateCurrentLang, ENT_QUOTES, classic22ArchiveCharset($archive)); ?>"
                data-langs="<?php echo htmlspecialchars($translateLangOptionsJson, ENT_QUOTES, classic22ArchiveCharset($archive)); ?>"
                data-translate-api="<?php echo htmlspecialchars($translateApiUrl, ENT_QUOTES, classic22ArchiveCharset($archive)); ?>"
                data-translate-cid="<?php echo (int) $translateCid; ?>"
                data-translate-ctype="<?php echo htmlspecialchars($translateCtype, ENT_QUOTES, classic22ArchiveCharset($archive)); ?>"
                data-translate-markdown="<?php echo (int) $translateIsMarkdown; ?>"
            >
                <?php foreach ($translateLangOptions as $langOption): ?>
                <?php
                    $langOptionValue = strtolower(trim((string) $langOption));
                ?>
                <option value="<?php echo htmlspecialchars($langOptionValue, ENT_QUOTES, classic22ArchiveCharset($archive)); ?>"<?php echo $langOptionValue === $translateCurrentLang ? ' selected' : ''; ?>><?php echo htmlspecialchars($langOptionValue, ENT_QUOTES, classic22ArchiveCharset($archive)); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        </div>
        <?php endif; ?>
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
        return html_entity_decode($matches[1], ENT_QUOTES, classic22ArchiveCharset($archive));
    }

    if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $html, $matches)) {
        $src = html_entity_decode($matches[1], ENT_QUOTES, classic22ArchiveCharset($archive));

        if (strpos($src, 'data:') === 0) {
            return null;
        }

        return $src;
    }

    return null;
}

function classic22ArchiveCharset($archive): string
{
    try {
        if (class_exists('\\Widget\\Options')) {
            $options = \Widget\Options::alloc();
            $charset = trim((string) ($options->charset ?? 'UTF-8'));
            return $charset !== '' ? $charset : 'UTF-8';
        }
    } catch (\Throwable $exception) {
    }

    return 'UTF-8';
}

function postExcerptText($archive, int $length = 140, string $trim = '...'): string
{
    $charset = classic22ArchiveCharset($archive);
    $text = strip_tags((string) ($archive->excerpt ?? ''));
    $text = html_entity_decode($text, ENT_QUOTES, $charset);
    $text = trim(preg_replace('/\\s+/u', ' ', $text ?? ''));

    if ($text === '') {
        $text = html_entity_decode((string) ($archive->title ?? ''), ENT_QUOTES, $charset);
    }

    return \Typecho\Common::subStr($text, 0, $length, $trim);
}

function classic22AiNumericValue($value): int
{
    $number = (int) $value;
    return $number > 0 ? $number : 0;
}

function classic22AiResolvePostViews($post): int
{
    $viewFields = ['views', 'view', 'hits', 'pv', 'visits'];

    foreach ($viewFields as $field) {
        try {
            if (isset($post->fields->{$field})) {
                $number = classic22AiNumericValue($post->fields->{$field});
                if ($number > 0) {
                    return $number;
                }
            }
        } catch (\Throwable $exception) {
        }

        try {
            if (isset($post->{$field})) {
                $number = classic22AiNumericValue($post->{$field});
                if ($number > 0) {
                    return $number;
                }
            }
        } catch (\Throwable $exception) {
        }
    }

    return 0;
}

function classic22AiFetchPostViewCounts($db, int $limit = 300): array
{
    if (!is_object($db)) {
        return [];
    }

    $counts = [];

    try {
        if (!class_exists('\\TypechoPlugin\\Vue3Admin\\LocalStorage')) {
            $file = __TYPECHO_ROOT_DIR__ . '/usr/plugins/Vue3Admin/LocalStorage.php';
            if (is_file($file)) {
                require_once $file;
            }
        }

        if (class_exists('\\TypechoPlugin\\Vue3Admin\\LocalStorage')) {
            $pdo = \TypechoPlugin\Vue3Admin\LocalStorage::pdo();
            if ($pdo instanceof \PDO) {
                $stmt = $pdo->query('SELECT cid, COUNT(id) AS views FROM v3a_visit_log WHERE cid > 0 GROUP BY cid ORDER BY views DESC LIMIT ' . max(1, (int) $limit));
                foreach ((array) $stmt->fetchAll() as $row) {
                    $cid = (int) ($row['cid'] ?? 0);
                    $views = (int) ($row['views'] ?? 0);
                    if ($cid > 0 && $views > 0) {
                        $counts[$cid] = $views;
                    }
                }
                if (!empty($counts)) {
                    return $counts;
                }
            }
        }
    } catch (\Throwable $exception) {
    }

    try {
        $rows = (array) $db->fetchAll(
            $db->select('cid', ['COUNT(id)' => 'views'])
                ->from('table.v3a_visit_log')
                ->where('cid > ?', 0)
                ->group('cid')
                ->order('views', \Typecho\Db::SORT_DESC)
                ->limit(max(1, (int) $limit))
        );

        foreach ($rows as $row) {
            $cid = (int) ($row['cid'] ?? 0);
            $views = (int) ($row['views'] ?? 0);
            if ($cid > 0 && $views > 0) {
                $counts[$cid] = $views;
            }
        }
    } catch (\Throwable $exception) {
    }

    return $counts;
}

function classic22AiBuildSiteContext($archive): array
{
    $context = [
        'site' => [
            'posts' => 0,
            'comments' => 0,
            'categories' => 0,
        ],
        'topComments' => [],
        'topViews' => [],
        'recentPosts' => [],
        'authors' => [],
        'categories' => [],
        'tags' => [],
    ];

    if (!is_object($archive)) {
        return $context;
    }

    $db = null;
    try {
        $db = \Typecho\Db::get();
    } catch (\Throwable $exception) {
        $db = null;
    }

    try {
        \Widget\Stat::alloc()->to($statWidget);
        $context['site']['posts'] = (int) ($statWidget->publishedPostsNum ?? 0);
        $context['site']['comments'] = (int) ($statWidget->publishedCommentsNum ?? 0);
        $context['site']['categories'] = (int) ($statWidget->categoriesNum ?? 0);
    } catch (\Throwable $exception) {
    }

    $recentPosts = classic22AiBuildArticleListPayload($archive, 120);
    if (!empty($recentPosts)) {
        $context['recentPosts'] = array_map(static function (array $item): array {
            return [
                'id' => (int) ($item['id'] ?? 0),
                'title' => trim((string) ($item['title'] ?? '')),
                'comments' => (int) ($item['comments'] ?? 0),
                'views' => (int) ($item['views'] ?? 0),
                'date' => trim((string) ($item['date'] ?? '')),
                'excerpt' => trim((string) ($item['excerpt'] ?? '')),
            ];
        }, array_slice($recentPosts, 0, 15));

        $commentRank = $recentPosts;
        usort($commentRank, static function ($left, $right): int {
            $leftComments = (int) ($left['comments'] ?? 0);
            $rightComments = (int) ($right['comments'] ?? 0);
            if ($leftComments !== $rightComments) {
                return $rightComments <=> $leftComments;
            }
            return (int) ($right['id'] ?? 0) <=> (int) ($left['id'] ?? 0);
        });
        $context['topComments'] = array_map(static function (array $item): array {
            return [
                'id' => (int) ($item['id'] ?? 0),
                'title' => trim((string) ($item['title'] ?? '')),
                'comments' => (int) ($item['comments'] ?? 0),
                'views' => (int) ($item['views'] ?? 0),
            ];
        }, array_slice($commentRank, 0, 10));

        $viewRank = $recentPosts;
        $viewCounts = classic22AiFetchPostViewCounts($db, 300);
        if (!empty($viewCounts)) {
            foreach ($viewRank as &$itemRef) {
                $cid = (int) ($itemRef['id'] ?? 0);
                if ($cid > 0 && isset($viewCounts[$cid])) {
                    $itemRef['views'] = (int) $viewCounts[$cid];
                }
            }
            unset($itemRef);
        }

        usort($viewRank, static function ($left, $right): int {
            $leftViews = (int) ($left['views'] ?? 0);
            $rightViews = (int) ($right['views'] ?? 0);
            if ($leftViews !== $rightViews) {
                return $rightViews <=> $leftViews;
            }
            return (int) ($right['id'] ?? 0) <=> (int) ($left['id'] ?? 0);
        });

        $context['topViews'] = array_map(static function (array $item): array {
            return [
                'id' => (int) ($item['id'] ?? 0),
                'title' => trim((string) ($item['title'] ?? '')),
                'views' => (int) ($item['views'] ?? 0),
                'comments' => (int) ($item['comments'] ?? 0),
            ];
        }, array_slice($viewRank, 0, 10));
    }

    if (is_object($db)) {
        try {
            $authorRows = (array) $db->fetchAll(
                $db->select('table.users.screenName', ['COUNT(table.contents.cid)' => 'posts'])
                    ->from('table.contents')
                    ->join('table.users', 'table.contents.authorId = table.users.uid')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish')
                    ->group('table.contents.authorId')
                    ->order('posts', \Typecho\Db::SORT_DESC)
                    ->limit(8)
            );

            foreach ($authorRows as $row) {
                $name = trim((string) ($row['screenName'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $context['authors'][] = [
                    'name' => $name,
                    'posts' => (int) ($row['posts'] ?? 0),
                ];
            }
        } catch (\Throwable $exception) {
        }

        try {
            $categoryRows = (array) $db->fetchAll(
                $db->select('table.metas.name', 'table.metas.slug', ['COUNT(table.relationships.cid)' => 'posts'])
                    ->from('table.metas')
                    ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                    ->join('table.contents', 'table.relationships.cid = table.contents.cid')
                    ->where('table.metas.type = ?', 'category')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish')
                    ->group('table.metas.mid')
                    ->order('posts', \Typecho\Db::SORT_DESC)
                    ->limit(12)
            );

            foreach ($categoryRows as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $context['categories'][] = [
                    'name' => $name,
                    'slug' => trim((string) ($row['slug'] ?? '')),
                    'posts' => (int) ($row['posts'] ?? 0),
                ];
            }
        } catch (\Throwable $exception) {
        }

        try {
            $tagRows = (array) $db->fetchAll(
                $db->select('table.metas.name', 'table.metas.slug', ['COUNT(table.relationships.cid)' => 'posts'])
                    ->from('table.metas')
                    ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                    ->join('table.contents', 'table.relationships.cid = table.contents.cid')
                    ->where('table.metas.type = ?', 'tag')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish')
                    ->group('table.metas.mid')
                    ->order('posts', \Typecho\Db::SORT_DESC)
                    ->limit(12)
            );

            foreach ($tagRows as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $context['tags'][] = [
                    'name' => $name,
                    'slug' => trim((string) ($row['slug'] ?? '')),
                    'posts' => (int) ($row['posts'] ?? 0),
                ];
            }
        } catch (\Throwable $exception) {
        }
    }

    return $context;
}

function classic22AiExtractGithubRepos(string $text): array
{
    if (trim($text) === '') {
        return [];
    }

    $repos = [];
    if (preg_match_all('#https?://github\.com/([^/\s]+)/([^/\s#?]+)#i', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $owner = strtolower(trim((string) ($match[1] ?? '')));
            $repo = strtolower(trim((string) ($match[2] ?? '')));
            $repo = preg_replace('/\.git$/i', '', $repo);
            if (!is_string($repo)) {
                $repo = '';
            }
            $repo = trim($repo);

            if ($owner === '' || $repo === '') {
                continue;
            }

            $key = $owner . '/' . $repo;
            $repos[$key] = $key;
        }
    }

    return array_values($repos);
}

function classic22AiBuildGithubContext($archive): array
{
    $result = [
        'repoMentions' => [],
        'topRepos' => [],
    ];

    if (!is_object($archive) || !($archive instanceof \Widget\Archive)) {
        return $result;
    }

    $recentPosts = classic22AiAllocRecentPosts(120);
    if (!is_object($recentPosts)) {
        return $result;
    }

    $repoMap = [];

    try {
        while ($recentPosts->next()) {
            $cid = (int) ($recentPosts->cid ?? 0);
            if ($cid <= 0) {
                continue;
            }

            $title = trim((string) ($recentPosts->title ?? ''));
            $comments = classic22AiNumericValue($recentPosts->commentsNum ?? 0);
            $views = classic22AiResolvePostViews($recentPosts);
            $permalink = trim((string) ($recentPosts->permalink ?? ''));

            $content = trim((string) ($recentPosts->content ?? ''));
            if ($content === '') {
                $content = classic22AiLoadPostTextByCid($cid);
            }

            $repos = classic22AiExtractGithubRepos($content);
            if (empty($repos)) {
                continue;
            }

            $result['repoMentions'][] = [
                'title' => $title,
                'permalink' => $permalink,
                'repos' => $repos,
            ];

            foreach ($repos as $repoKey) {
                if (!isset($repoMap[$repoKey])) {
                    $repoMap[$repoKey] = [
                        'repo' => $repoKey,
                        'count' => 0,
                        'postTitles' => [],
                        'comments' => 0,
                        'views' => 0,
                    ];
                }

                $repoMap[$repoKey]['count']++;
                $repoMap[$repoKey]['comments'] += $comments;
                $repoMap[$repoKey]['views'] += $views;
                if ($title !== '' && !in_array($title, $repoMap[$repoKey]['postTitles'], true)) {
                    $repoMap[$repoKey]['postTitles'][] = $title;
                }
            }
        }
    } catch (\Throwable $exception) {
        return $result;
    }

    if (!empty($repoMap)) {
        $topRepos = array_values($repoMap);
        usort($topRepos, static function ($left, $right): int {
            $leftCount = (int) ($left['count'] ?? 0);
            $rightCount = (int) ($right['count'] ?? 0);
            if ($leftCount !== $rightCount) {
                return $rightCount <=> $leftCount;
            }

            $leftComments = (int) ($left['comments'] ?? 0);
            $rightComments = (int) ($right['comments'] ?? 0);
            if ($leftComments !== $rightComments) {
                return $rightComments <=> $leftComments;
            }

            $leftViews = (int) ($left['views'] ?? 0);
            $rightViews = (int) ($right['views'] ?? 0);
            return $rightViews <=> $leftViews;
        });

        $result['topRepos'] = array_slice($topRepos, 0, 12);
        $result['repoMentions'] = array_slice($result['repoMentions'], 0, 12);
    }

    return $result;
}

function classic22AiAllocRecentPosts(int $limit = 80)
{
    $rule = 'pageSize=' . max(1, $limit);

    try {
        if (class_exists('\\Widget\\Contents\\Post\\Recent')) {
            \Widget\Contents\Post\Recent::alloc($rule)->to($recentPosts);
            if (isset($recentPosts) && is_object($recentPosts)) {
                return $recentPosts;
            }
        }
    } catch (\Throwable $exception) {
    }

    try {
        if (class_exists('\\Widget_Contents_Post_Recent')) {
            \Widget_Contents_Post_Recent::alloc($rule)->to($recentPosts);
            if (isset($recentPosts) && is_object($recentPosts)) {
                return $recentPosts;
            }
        }
    } catch (\Throwable $exception) {
    }

    return null;
}

function classic22AiEnabled($options): bool
{
    return classic22LinuxDoGetOption($options, 'aiEnabled', '1') !== '0';
}

function classic22AiAllowedDomains($options): array
{
    $configured = trim((string) classic22LinuxDoGetOption($options, 'aiAllowedDomains', ''));
    $domains = [];

    if ($configured !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $configured);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $domain = strtolower(trim((string) $line));
                $domain = preg_replace('/^https?:\/\//i', '', $domain);
                $domain = trim((string) $domain, " \t\n\r\0\x0B/");
                if ($domain === '') {
                    continue;
                }

                $domain = explode('/', $domain)[0] ?? '';
                $domain = strtolower(trim((string) $domain));
                if ($domain === '') {
                    continue;
                }

                $domains[] = $domain;
            }
        }
    }

    if (empty($domains)) {
        $siteBase = classic22LinuxDoSiteBaseUrl($options);
        $siteHost = strtolower(trim((string) (parse_url($siteBase, PHP_URL_HOST) ?? '')));
        if ($siteHost !== '') {
            $domains[] = $siteHost;
        }
    }

    return array_values(array_unique($domains));
}

function classic22AiExtractOriginHost(): string
{
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin !== '') {
        $originHost = strtolower(trim((string) (parse_url($origin, PHP_URL_HOST) ?? '')));
        if ($originHost !== '') {
            return $originHost;
        }
    }

    $referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    if ($referer !== '') {
        $refererHost = strtolower(trim((string) (parse_url($referer, PHP_URL_HOST) ?? '')));
        if ($refererHost !== '') {
            return $refererHost;
        }
    }

    return '';
}

function classic22AiIsAllowedDomainRequest($options): bool
{
    $allowedDomains = classic22AiAllowedDomains($options);
    if (empty($allowedDomains)) {
        return false;
    }

    $originHost = classic22AiExtractOriginHost();
    if ($originHost === '') {
        return false;
    }

    foreach ($allowedDomains as $allowed) {
        $allowed = strtolower(trim((string) $allowed));
        if ($allowed === '') {
            continue;
        }

        if ($originHost === $allowed) {
            return true;
        }

        if (strpos($allowed, '*.') === 0) {
            $baseDomain = substr($allowed, 2);
            if ($baseDomain !== '' && substr($originHost, -strlen('.' . $baseDomain)) === '.' . $baseDomain) {
                return true;
            }
        }
    }

    return false;
}

function classic22AiLogsDir(): string
{
    return __DIR__ . '/ai_logs';
}

function classic22AiLogsEnsureStorage(): bool
{
    $dir = classic22AiLogsDir();

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    if (!is_dir($dir)) {
        return false;
    }

    $protectFiles = [
        $dir . '/index.php' => "<?php\nhttp_response_code(403);\nexit;\n",
        $dir . '/.htaccess' => "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n",
        $dir . '/web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <security>\n      <authorization>\n        <add accessType=\"Deny\" users=\"*\" />\n      </authorization>\n    </security>\n  </system.webServer>\n</configuration>\n",
    ];

    foreach ($protectFiles as $path => $content) {
        if (is_file($path)) {
            continue;
        }

        @file_put_contents($path, $content, LOCK_EX);
    }

    return true;
}

function classic22AiExtractClientIp(): string
{
    $candidates = [
        trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '')),
        trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? '')),
        trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')),
        trim((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
    ];

    foreach ($candidates as $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }

        if (strpos($value, ',') !== false) {
            $value = trim((string) (explode(',', $value)[0] ?? ''));
        }

        if ($value !== '' && filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }

    return '';
}

function classic22AiLogChatRequest(string $ip, string $message, array $meta = []): void
{
    if (!classic22AiLogsEnsureStorage()) {
        return;
    }

    $ip = trim($ip);
    if ($ip === '') {
        $ip = 'unknown';
    }

    $record = array_merge([
        'time' => date('Y-m-d H:i:s'),
        'timestamp' => time(),
        'ip' => $ip,
        'message' => $message,
    ], $meta);

    $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded) || $encoded === '') {
        return;
    }

    $path = classic22AiLogsDir() . '/chat-' . date('Y-m-d') . '.jsonl';
    @file_put_contents($path, $encoded . "\n", FILE_APPEND | LOCK_EX);
}

function classic22AiConsumeDailyQuota(string $ip, int $limit = 5): array
{
    $limit = max(0, (int) $limit);

    $ip = trim($ip);
    if ($ip === '') {
        $ip = 'unknown';
    }

    $today = date('Y-m-d');
    $result = [
        'ok' => true,
        'date' => $today,
        'limit' => $limit,
        'used' => 0,
        'remaining' => $limit,
    ];

    if ($limit <= 0) {
        return $result;
    }

    if (!classic22AiLogsEnsureStorage()) {
        return [
            'ok' => false,
            'error' => 'AI 日志目录不可写，无法记录与限额。请检查主题目录权限：' . classic22AiLogsDir(),
        ];
    }

    $path = classic22AiLogsDir() . '/quota-' . $today . '.json';
    $handle = @fopen($path, 'c+');
    if (!is_resource($handle)) {
        return [
            'ok' => false,
            'error' => '无法写入 AI 配额文件：' . $path,
        ];
    }

    if (!@flock($handle, LOCK_EX)) {
        @fclose($handle);
        return [
            'ok' => false,
            'error' => '无法锁定 AI 配额文件：' . $path,
        ];
    }

    $raw = '';
    try {
        $raw = (string) stream_get_contents($handle);
    } catch (\Throwable $exception) {
        $raw = '';
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }

    $current = (int) ($data[$ip] ?? 0);
    if ($current >= $limit) {
        $result['ok'] = false;
        $result['used'] = $current;
        $result['remaining'] = 0;

        @flock($handle, LOCK_UN);
        @fclose($handle);
        return $result;
    }

    $current++;
    $data[$ip] = $current;
    $result['used'] = $current;
    $result['remaining'] = max(0, $limit - $current);

    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (is_string($encoded) && $encoded !== '') {
        @rewind($handle);
        @ftruncate($handle, 0);
        @fwrite($handle, $encoded);
        @fflush($handle);
    }

    @flock($handle, LOCK_UN);
    @fclose($handle);

    return $result;
}

function classic22TimelineStorageDir(): string
{
    return __DIR__ . '/timeline';
}

function classic22TimelineStorageFile(): string
{
    return classic22TimelineStorageDir() . '/timeline.json';
}

function classic22TimelineEnsureStorage(): void
{
    $dir = classic22TimelineStorageDir();

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    if (!is_dir($dir)) {
        return;
    }

    $protectFiles = [
        $dir . '/index.php' => "<?php\nhttp_response_code(403);\nexit;\n",
        $dir . '/.htaccess' => "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n",
        $dir . '/web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <security>\n      <authorization>\n        <add accessType=\"Deny\" users=\"*\" />\n      </authorization>\n    </security>\n  </system.webServer>\n</configuration>\n",
    ];

    foreach ($protectFiles as $path => $content) {
        if (is_file($path)) {
            continue;
        }

        @file_put_contents($path, $content, LOCK_EX);
    }
}

function classic22TimelineReadCache(): array
{
    classic22TimelineEnsureStorage();

    $path = classic22TimelineStorageFile();
    if (!is_file($path)) {
        return [];
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function classic22TimelineWriteCache(array $payload): bool
{
    classic22TimelineEnsureStorage();

    $path = classic22TimelineStorageFile();
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($encoded) || $encoded === '') {
        return false;
    }

    return @file_put_contents($path, $encoded, LOCK_EX) !== false;
}

function classic22TimelinePlainText(string $text, int $limit = 80): string
{
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = strip_tags($text);
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));
    if ($text === '') {
        return '';
    }

    return \Typecho\Common::subStr($text, 0, max(10, $limit), '...');
}

function classic22RenderInlineMarkdown(string $text, string $charset = 'UTF-8'): string
{
    $charset = trim($charset);
    if ($charset === '') {
        $charset = 'UTF-8';
    }

    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $escaped = htmlspecialchars($text, ENT_QUOTES, $charset);

    $codeSegments = [];
    $escapedWithCodePlaceholders = preg_replace_callback(
        '/`([^`]+)`/u',
        static function (array $matches) use (&$codeSegments): string {
            $key = '@@C22CODE' . count($codeSegments) . '@@';
            $codeSegments[$key] = '<code>' . $matches[1] . '</code>';
            return $key;
        },
        $escaped
    );

    if (is_string($escapedWithCodePlaceholders)) {
        $escaped = $escapedWithCodePlaceholders;
    }

    $escaped = (string) preg_replace('/~~(?=\\S)(.+?)(?<=\\S)~~/u', '<del>$1</del>', $escaped);
    $escaped = (string) preg_replace('/\\*\\*(?=\\S)(.+?)(?<=\\S)\\*\\*/u', '<strong>$1</strong>', $escaped);
    $escaped = (string) preg_replace('/(?<!\\*)\\*(?=\\S)(.+?)(?<=\\S)\\*(?!\\*)/u', '<em>$1</em>', $escaped);

    if (!empty($codeSegments)) {
        $escaped = strtr($escaped, $codeSegments);
    }

    return $escaped;
}

function classic22TimelineFormatTime(int $timestamp): string
{
    if ($timestamp <= 0) {
        return '';
    }

    try {
        if (class_exists('\\Typecho\\Date')) {
            $date = new \Typecho\Date($timestamp);
            return trim((string) $date->format('Y-m-d H:i'));
        }
    } catch (\Throwable $exception) {
    }

    return date('Y-m-d H:i', $timestamp);
}

function classic22TimelineRelativeTime(int $timestamp): string
{
    if ($timestamp <= 0) {
        return '';
    }

    $delta = max(0, time() - $timestamp);
    if ($delta < 60) {
        return '刚刚';
    }

    if ($delta < 3600) {
        return floor($delta / 60) . ' 分钟前';
    }

    if ($delta < 86400) {
        return floor($delta / 3600) . ' 小时前';
    }

    if ($delta < 86400 * 30) {
        return floor($delta / 86400) . ' 天前';
    }

    if ($delta < 86400 * 365) {
        return floor($delta / (86400 * 30)) . ' 个月前';
    }

    return floor($delta / (86400 * 365)) . ' 年前';
}

function classic22TimelineFormatVersion(): int
{
    return 3;
}

function classic22TimelinePostFallbackLink(\Widget\Archive $archive, int $cid): string
{
    if ($cid <= 0) {
        return '';
    }

    $baseUrl = rtrim(classic22LinuxDoSiteBaseUrl($archive->options), '/');
    return $baseUrl . '/?p=' . $cid;
}

function classic22TimelinePostCreatedTimestamp(int $cid): int
{
    if ($cid <= 0) {
        return 0;
    }

    static $cache = [];
    if (isset($cache[$cid])) {
        return (int) $cache[$cid];
    }

    $db = classic22LinuxDoDb();
    if (!is_object($db)) {
        $cache[$cid] = 0;
        return 0;
    }

    try {
        $row = $db->fetchRow(
            $db->select('created')
                ->from('table.contents')
                ->where('cid = ?', $cid)
                ->limit(1)
        );

        $created = 0;
        if (is_array($row)) {
            $created = (int) ($row['created'] ?? 0);
        } elseif (is_object($row)) {
            $created = (int) ($row->created ?? 0);
        }

        $cache[$cid] = $created > 0 ? $created : 0;
        return (int) $cache[$cid];
    } catch (\Throwable $exception) {
        $cache[$cid] = 0;
        return 0;
    }
}

function classic22TimelineBuildArticleMap(\Widget\Archive $archive, int $limit = 160): array
{
    $list = classic22AiBuildArticleListPayload($archive, $limit);
    $map = [];

    foreach ($list as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        $map[$id] = [
            'id' => $id,
            'title' => trim((string) ($item['title'] ?? ('文章 #' . $id))),
            'permalink' => trim((string) ($item['permalink'] ?? '')),
            'views' => (int) ($item['views'] ?? 0),
            'comments' => (int) ($item['comments'] ?? 0),
            'date' => trim((string) ($item['date'] ?? '')),
        ];
    }

    return $map;
}

function classic22TimelineBuildRankings(\Widget\Archive $archive, array $articleMap): array
{
    $result = [
        'views' => [],
        'comments' => [],
    ];

    $context = classic22AiBuildSiteContext($archive);
    $viewRows = is_array($context['topViews'] ?? null) ? $context['topViews'] : [];
    $commentRows = is_array($context['topComments'] ?? null) ? $context['topComments'] : [];

    foreach (array_slice($viewRows, 0, 8) as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        $fallback = $articleMap[$id] ?? null;
        $title = trim((string) ($item['title'] ?? ($fallback['title'] ?? '文章 #' . $id)));
        $permalink = trim((string) ($fallback['permalink'] ?? ''));

        $result['views'][] = [
            'id' => $id,
            'title' => $title,
            'count' => (int) ($item['views'] ?? 0),
            'permalink' => $permalink !== '' ? $permalink : classic22TimelinePostFallbackLink($archive, $id),
        ];
    }

    foreach (array_slice($commentRows, 0, 8) as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        $fallback = $articleMap[$id] ?? null;
        $title = trim((string) ($item['title'] ?? ($fallback['title'] ?? '文章 #' . $id)));
        $permalink = trim((string) ($fallback['permalink'] ?? ''));

        $result['comments'][] = [
            'id' => $id,
            'title' => $title,
            'count' => (int) ($item['comments'] ?? 0),
            'permalink' => $permalink !== '' ? $permalink : classic22TimelinePostFallbackLink($archive, $id),
        ];
    }

    return $result;
}

function classic22TimelineTopIds(array $list, int $limit = 3): array
{
    $ids = [];

    foreach (array_slice($list, 0, max(1, $limit)) as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = (int) ($item['id'] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return $ids;
}

function classic22TimelineTitlesByIds(array $ids, array $list): array
{
    $map = [];
    foreach ($list as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = (int) ($item['id'] ?? 0);
        if ($id > 0) {
            $map[$id] = trim((string) ($item['title'] ?? ('文章 #' . $id)));
        }
    }

    $titles = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0 && isset($map[$id])) {
            $titles[] = $map[$id];
        }
    }

    return $titles;
}

function classic22TimelineRankChangeEvents(array $currentRankings, array $previousRankings): array
{
    $events = [];
    $now = time();

    $boards = [
        'views' => '浏览榜',
        'comments' => '评论榜',
    ];

    foreach ($boards as $key => $label) {
        $currentList = is_array($currentRankings[$key] ?? null) ? $currentRankings[$key] : [];
        $previousList = is_array($previousRankings[$key] ?? null) ? $previousRankings[$key] : [];

        $currentTop = classic22TimelineTopIds($currentList, 6);
        $previousTop = classic22TimelineTopIds($previousList, 6);

        if ($currentTop === $previousTop) {
            continue;
        }

        $currentPos = [];
        foreach (array_slice($currentList, 0, 6) as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $currentPos[$id] = [
                'rank' => $idx + 1,
                'title' => trim((string) ($row['title'] ?? ('文章 #' . $id))),
                'permalink' => trim((string) ($row['permalink'] ?? '')),
            ];
        }

        $previousPos = [];
        foreach (array_slice($previousList, 0, 6) as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $previousPos[$id] = $idx + 1;
        }

        if (empty($currentPos)) {
            continue;
        }

        $outsideRank = max(count($previousPos), count($currentPos)) + 1;
        $bestTitle = '';
        $bestRise = 0;
        $bestLink = '';
        $bestRank = 999;

        foreach ($currentPos as $id => $meta) {
            $currentRank = (int) ($meta['rank'] ?? 0);
            if ($currentRank <= 0) {
                continue;
            }

            $previousRank = isset($previousPos[$id]) ? (int) $previousPos[$id] : $outsideRank;
            $rise = $previousRank - $currentRank;
            if ($rise <= 0) {
                continue;
            }

            if (
                $rise > $bestRise
                || ($rise === $bestRise && $currentRank < $bestRank)
            ) {
                $bestRise = $rise;
                $bestRank = $currentRank;
                $bestTitle = trim((string) ($meta['title'] ?? ''));
                $bestLink = trim((string) ($meta['permalink'] ?? ''));
            }
        }

        if ($bestRise <= 0 || $bestTitle === '') {
            continue;
        }

        $events[] = [
            'type' => 'rank',
            'timestamp' => $now,
            'time' => classic22TimelineFormatTime($now),
            'relativeTime' => classic22TimelineRelativeTime($now),
            'title' => $label,
            'summary' => '新榜单：' . $bestTitle . '上升' . $bestRise . '名次',
            'link' => $bestLink,
        ];
    }

    return $events;
}

function classic22TimelineRecentPostEvents(\Widget\Archive $archive, array $articleMap, int $limit = 6): array
{
    $events = [];

    foreach (array_slice(array_values($articleMap), 0, max(1, $limit)) as $article) {
        if (!is_array($article)) {
            continue;
        }

        $id = (int) ($article['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        $timestamp = classic22TimelinePostCreatedTimestamp($id);
        if ($timestamp <= 0) {
            $timestamp = time();
        }

        $title = trim((string) ($article['title'] ?? ('文章 #' . $id)));
        $link = trim((string) ($article['permalink'] ?? ''));
        if ($link === '') {
            $link = classic22TimelinePostFallbackLink($archive, $id);
        }

        $events[] = [
            'type' => 'post',
            'timestamp' => $timestamp,
            'time' => classic22TimelineFormatTime($timestamp),
            'relativeTime' => classic22TimelineRelativeTime($timestamp),
            'title' => $title,
            'summary' => '发布了新文章。',
            'link' => $link,
        ];
    }

    return $events;
}

function classic22TimelineRecentCommentEvents(\Widget\Archive $archive, array $articleMap, int $limit = 6): array
{
    $events = [];
    $db = classic22LinuxDoDb();
    if (!is_object($db)) {
        return [];
    }

    try {
        $rows = (array) $db->fetchAll(
            $db->select(
                'table.comments.coid',
                'table.comments.cid',
                'table.comments.author',
                'table.comments.text',
                'table.comments.created',
                'table.contents.title'
            )
                ->from('table.comments')
                ->join('table.contents', 'table.comments.cid = table.contents.cid')
                ->where('table.comments.status = ?', 'approved')
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.status = ?', 'publish')
                ->order('table.comments.created', \Typecho\Db::SORT_DESC)
                ->limit(max(1, (int) $limit))
        );
    } catch (\Throwable $exception) {
        return [];
    }

    foreach ($rows as $row) {
        $coid = (int) ($row['coid'] ?? 0);
        $cid = (int) ($row['cid'] ?? 0);
        if ($coid <= 0 || $cid <= 0) {
            continue;
        }

        $author = trim((string) ($row['author'] ?? '访客'));
        if ($author === '') {
            $author = '访客';
        }

        $postTitle = trim((string) ($row['title'] ?? ''));
        if ($postTitle === '' && isset($articleMap[$cid])) {
            $postTitle = trim((string) ($articleMap[$cid]['title'] ?? ''));
        }
        if ($postTitle === '') {
            $postTitle = '文章 #' . $cid;
        }

        $timestamp = (int) ($row['created'] ?? 0);
        $postLink = trim((string) (($articleMap[$cid]['permalink'] ?? '') ?: ''));
        if ($postLink === '') {
            $postLink = classic22TimelinePostFallbackLink($archive, $cid);
        }

        $link = $postLink;
        if ($link !== '') {
            $link .= '#comment-' . $coid;
        }

        $summary = classic22TimelinePlainText((string) ($row['text'] ?? ''), 42);
        if ($summary === '') {
            $summary = '发布了新评论。';
        }

        $events[] = [
            'type' => 'comment',
            'timestamp' => $timestamp > 0 ? $timestamp : time(),
            'time' => classic22TimelineFormatTime($timestamp),
            'relativeTime' => classic22TimelineRelativeTime($timestamp),
            'title' => $author . ' 评论了《' . $postTitle . '》',
            'summary' => $summary,
            'link' => $link,
        ];
    }

    return $events;
}

function classic22TimelineFormatSummary(array $event): string
{
    $type = trim((string) ($event['type'] ?? 'timeline'));
    $title = trim((string) ($event['title'] ?? ''));
    $summary = trim((string) ($event['summary'] ?? ''));

    if ($type === 'post') {
        if ($title !== '') {
            $summary = '新发布：《' . $title . '》';
        } elseif ($summary === '') {
            $summary = '发布了新文章';
        }
    } elseif ($type === 'comment') {
        $author = '';
        if ($title !== '' && preg_match('/^(.+?)\s*评论了《.+》$/u', $title, $match)) {
            $author = trim((string) ($match[1] ?? ''));
        }

        $snippet = classic22TimelinePlainText($summary, 40);
        if ($snippet !== '') {
            $summary = $author !== '' ? ($author . ' 评论：' . $snippet) : $snippet;
        } elseif ($title !== '') {
            $summary = $title;
        } else {
            $summary = '收到一条新评论';
        }
    } elseif ($type === 'rank') {
        if ($summary === '') {
            $summary = $title !== '' ? ('新榜单：' . $title) : '新榜单：榜单更新';
        }
    } else {
        if ($summary === '' && $title !== '') {
            $summary = $title;
        }
        if ($summary === '') {
            $summary = '动态更新';
        }
    }

    $summary = classic22TimelinePlainText($summary, 72);
    if ($summary === '') {
        return '';
    }

    if (substr($summary, -3) !== '...') {
        $summary = (string) preg_replace('/[，。.!！]+$/u', '', $summary);
    }

    return trim($summary);
}

function classic22TimelineFormatEvents(array $events): array
{
    if (empty($events)) {
        return [];
    }

    $formatted = [];

    foreach ($events as $item) {
        if (!is_array($item)) {
            continue;
        }

        $timestamp = (int) ($item['timestamp'] ?? 0);
        if ($timestamp <= 0) {
            $timestamp = time();
        }

        $item['timestamp'] = $timestamp;

        $timeText = trim((string) ($item['time'] ?? ''));
        if ($timeText === '') {
            $timeText = classic22TimelineFormatTime($timestamp);
        }
        $item['time'] = $timeText;

        $relativeText = trim((string) ($item['relativeTime'] ?? ''));
        if ($relativeText === '') {
            $relativeText = classic22TimelineRelativeTime($timestamp);
        }
        $item['relativeTime'] = $relativeText;

        $item['summary'] = classic22TimelineFormatSummary($item);
        if ($item['summary'] === '') {
            continue;
        }

        $formatted[] = $item;
    }

    return $formatted;
}

function classic22TimelineGeneratePayload(\Widget\Archive $archive, array $previous = []): array
{
    $articleMap = classic22TimelineBuildArticleMap($archive, 160);

    $rankings = classic22TimelineBuildRankings($archive, $articleMap);
    $previousRankings = is_array($previous['rankings'] ?? null) ? $previous['rankings'] : [];

    $events = [];
    $events = array_merge(
        $events,
        classic22TimelineRecentPostEvents($archive, $articleMap, 6),
        classic22TimelineRecentCommentEvents($archive, $articleMap, 6),
        classic22TimelineRankChangeEvents($rankings, $previousRankings)
    );

    usort($events, static function ($left, $right): int {
        return (int) ($right['timestamp'] ?? 0) <=> (int) ($left['timestamp'] ?? 0);
    });

    $events = array_slice($events, 0, 12);
    $events = classic22TimelineFormatEvents($events);

    return [
        'formatVersion' => classic22TimelineFormatVersion(),
        'generatedAt' => time(),
        'updatedAt' => classic22TimelineFormatTime(time()),
        'timeline' => $events,
        'rankings' => [
            'views' => array_slice(is_array($rankings['views'] ?? null) ? $rankings['views'] : [], 0, 6),
            'comments' => array_slice(is_array($rankings['comments'] ?? null) ? $rankings['comments'] : [], 0, 6),
        ],
    ];
}

function classic22TimelineHomeData(\Widget\Archive $archive): array
{
    $empty = [
        'formatVersion' => classic22TimelineFormatVersion(),
        'generatedAt' => 0,
        'updatedAt' => '',
        'timeline' => [],
        'rankings' => [
            'views' => [],
            'comments' => [],
        ],
    ];

    if (!is_object($archive)) {
        return $empty;
    }

    if (!method_exists($archive, 'is') || !$archive->is('index')) {
        return $empty;
    }

    $cache = classic22TimelineReadCache();
    $expectedFormatVersion = classic22TimelineFormatVersion();
    $cachedFormatVersion = (int) ($cache['formatVersion'] ?? 1);
    if ($cachedFormatVersion !== $expectedFormatVersion) {
        $cache = [];
    }

    $generatedAt = (int) ($cache['generatedAt'] ?? 0);
    $ttl = 900;

    if ($generatedAt > 0 && (time() - $generatedAt) <= $ttl) {
        return array_merge($empty, $cache);
    }

    $generated = classic22TimelineGeneratePayload($archive, $cache);
    if (!empty($generated['timeline']) || !empty($generated['rankings']['views']) || !empty($generated['rankings']['comments'])) {
        classic22TimelineWriteCache($generated);
        return array_merge($empty, $generated);
    }

    if (!empty($cache)) {
        return array_merge($empty, $cache);
    }

    return $empty;
}

function classic22AiGetModels($options): array
{
    $raw = classic22LinuxDoGetOption($options, 'aiModels', "gpt-4o-mini\ngpt-4.1-mini\ngpt-4o");
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);

    $models = [];
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $model = trim((string) $line);
            if ($model === '') {
                continue;
            }

            $models[] = $model;
        }
    }

    if (empty($models)) {
        return ['gpt-4o-mini'];
    }

    return array_values(array_unique($models));
}

function classic22AiDefaultModel($options): string
{
    $models = classic22AiGetModels($options);
    $configured = trim((string) classic22LinuxDoGetOption($options, 'aiDefaultModel', ''));

    if ($configured !== '' && in_array($configured, $models, true)) {
        return $configured;
    }

    return (string) $models[0];
}

function classic22AiSystemPrompt($options): string
{
    $prompt = trim((string) classic22LinuxDoGetOption($options, 'aiSystemPrompt', ''));
    if ($prompt !== '') {
        return $prompt;
    }

    return '你是博客首页 AI 助手，请使用中文简洁回答用户问题。';
}

function classic22AiBuildArticleListPayload(\Widget\Archive $archive, int $limit = 80): array
{
    $items = [];

    $recentPosts = classic22AiAllocRecentPosts($limit);
    if (!is_object($recentPosts)) {
        return [];
    }

    try {
        while ($recentPosts->next()) {
            try {
                $cid = (int) ($recentPosts->cid ?? 0);
                if ($cid <= 0) {
                    continue;
                }

                $title = trim((string) ($recentPosts->title ?? ''));
                if ($title === '') {
                    $title = '文章 #' . $cid;
                }

                $date = '';
                try {
                    $date = trim((string) $recentPosts->date->format('Y-m-d'));
                } catch (\Throwable $exception) {
                    $date = '';
                }

                $excerpt = trim(postExcerptText($recentPosts, 220, '...'));

                $commentsCount = 0;
                try {
                    $commentsCount = classic22AiNumericValue($recentPosts->commentsNum ?? 0);
                } catch (\Throwable $exception) {
                    $commentsCount = 0;
                }

                $items[] = [
                    'id' => $cid,
                    'title' => $title,
                    'permalink' => trim((string) ($recentPosts->permalink ?? '')),
                    'date' => $date,
                    'excerpt' => $excerpt,
                    'views' => classic22AiResolvePostViews($recentPosts),
                    'comments' => $commentsCount,
                ];
            } catch (\Throwable $exception) {
                continue;
            }
        }
    } catch (\Throwable $exception) {
        return [];
    }

    return $items;
}

function classic22AiLoadPostTextByCid(int $cid): string
{
    if ($cid <= 0) {
        return '';
    }

    $db = classic22LinuxDoDb();
    if (!is_object($db)) {
        return '';
    }

    try {
        $row = $db->fetchRow(
            $db->select('text')
                ->from('table.contents')
                ->where('cid = ? AND status = ? AND type = ?', $cid, 'publish', 'post')
                ->limit(1)
        );
    } catch (\Throwable $exception) {
        return '';
    }

    if (is_array($row)) {
        return trim((string) ($row['text'] ?? ''));
    }

    if (is_object($row)) {
        return trim((string) ($row->text ?? ''));
    }

    return '';
}

function classic22AiFindArticleById(\Widget\Archive $archive, int $cid): ?array
{
    if ($cid <= 0) {
        return null;
    }

    $recentPosts = classic22AiAllocRecentPosts(200);
    if (!is_object($recentPosts)) {
        return null;
    }

    try {
        while ($recentPosts->next()) {
            if ((int) ($recentPosts->cid ?? 0) !== $cid) {
                continue;
            }

            $date = '';
            try {
                $date = trim((string) $recentPosts->date->format('Y-m-d'));
            } catch (\Throwable $exception) {
                $date = '';
            }

            $content = trim((string) ($recentPosts->content ?? ''));
            if ($content === '') {
                $content = classic22AiLoadPostTextByCid((int) ($recentPosts->cid ?? 0));
            }

            if ($content === '') {
                $content = trim(postExcerptText($recentPosts, 360, '...'));
            }

            return [
                'id' => (int) ($recentPosts->cid ?? 0),
                'title' => trim((string) ($recentPosts->title ?? '')),
                'permalink' => trim((string) ($recentPosts->permalink ?? '')),
                'date' => $date,
                'content' => $content,
                'excerpt' => trim(postExcerptText($recentPosts, 360, '...')),
                'views' => classic22AiResolvePostViews($recentPosts),
                'comments' => classic22AiNumericValue($recentPosts->commentsNum ?? 0),
            ];
        }
    } catch (\Throwable $exception) {
        return null;
    }

    return null;
}

function classic22AiFindArticleByUrl(\Widget\Archive $archive, string $url): ?array
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $targetPath = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    if ($targetPath === '') {
        return null;
    }

    $recentPosts = classic22AiAllocRecentPosts(200);
    if (!is_object($recentPosts)) {
        return null;
    }

    try {
        while ($recentPosts->next()) {
            $postPath = (string) (parse_url((string) ($recentPosts->permalink ?? ''), PHP_URL_PATH) ?? '');
            if ($postPath !== $targetPath) {
                continue;
            }

            return classic22AiFindArticleById($archive, (int) ($recentPosts->cid ?? 0));
        }
    } catch (\Throwable $exception) {
        return null;
    }

    return null;
}

function classic22AiSendJson(array $payload, int $status = 200): void
{
    if (!headers_sent()) {
        if (function_exists('http_response_code')) {
            http_response_code($status);
        }
        header('Content-Type: application/json; charset=UTF-8');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function classic22AiNormalizeBaseUrl(string $baseUrl): string
{
    $trimmed = trim($baseUrl);
    if ($trimmed === '') {
        return 'https://api.openai.com/v1';
    }

    return rtrim($trimmed, '/');
}

function classic22AiSanitizeBaseUrl(string $baseUrl, string $provider): string
{
    $normalized = classic22AiNormalizeBaseUrl($baseUrl);

    $normalized = preg_replace('#/(chat/completions|responses)$#i', '', $normalized);
    if (!is_string($normalized)) {
        $normalized = classic22AiNormalizeBaseUrl($baseUrl);
    }

    if ($provider !== 'rightcode') {
        return $normalized;
    }

    $host = strtolower((string) (parse_url($normalized, PHP_URL_HOST) ?? ''));
    $path = strtolower((string) (parse_url($normalized, PHP_URL_PATH) ?? ''));

    if ($host === 'www.right.codes' || $host === 'right.codes') {
        if ($path === '' || $path === '/' || $path === '/codex' || $path === '/codex/') {
            return rtrim($normalized, '/') . '/v1';
        }
    }

    return $normalized;
}

function classic22AiResolveApiMode($options): string
{
    $mode = strtolower(trim((string) classic22LinuxDoGetOption($options, 'aiApiMode', 'chat_completions')));
    if (!in_array($mode, ['chat_completions', 'responses'], true)) {
        $mode = 'chat_completions';
    }

    return $mode;
}

function classic22AiBuildChatCompletionsPayload(string $model, array $messages): ?string
{
    $payload = json_encode([
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'stream' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!is_string($payload) || $payload === '') {
        return null;
    }

    return $payload;
}

function classic22AiBuildResponsesInput(array $messages): array
{
    $userParts = [];

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $role = trim((string) ($message['role'] ?? 'user'));
        $text = trim((string) ($message['content'] ?? ''));
        if ($text === '') {
            continue;
        }

        if ($role === 'user') {
            $userParts[] = $text;
        }
    }

    if (empty($userParts)) {
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $text = trim((string) ($message['content'] ?? ''));
            if ($text !== '') {
                $userParts[] = $text;
            }
        }
    }

    $userText = trim(implode("\n\n", $userParts));
    if ($userText === '') {
        $userText = '你好';
    }

    return [[
        'role' => 'user',
        'content' => $userText,
    ]];
}

function classic22AiBuildResponsesUserInput(array $messages): string
{
    $userParts = [];

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $role = trim((string) ($message['role'] ?? ''));
        if ($role !== 'user') {
            continue;
        }

        $text = trim((string) ($message['content'] ?? ''));
        if ($text !== '') {
            $userParts[] = $text;
        }
    }

    if (empty($userParts)) {
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $text = trim((string) ($message['content'] ?? ''));
            if ($text !== '') {
                $userParts[] = $text;
            }
        }
    }

    $userText = trim(implode("\n\n", $userParts));
    return $userText !== '' ? $userText : '你好';
}

function classic22AiBuildResponsesInstructions(array $messages): string
{
    $parts = [];

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $role = trim((string) ($message['role'] ?? ''));
        if ($role !== 'system') {
            continue;
        }

        $text = trim((string) ($message['content'] ?? ''));
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    return trim(implode("\n\n", $parts));
}

function classic22AiBuildResponsesPayload(string $model, array $messages): ?string
{
    $input = classic22AiBuildResponsesInput($messages);

    $payloadArray = [
        'model' => $model,
        'input' => $input,
        'stream' => false,
    ];

    $instructions = classic22AiBuildResponsesInstructions($messages);
    if ($instructions !== '') {
        $payloadArray['instructions'] = $instructions;
    }

    $payload = json_encode($payloadArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!is_string($payload) || $payload === '') {
        return null;
    }

    return $payload;
}

function classic22AiExtractTextFromResponsesOutput(array $decoded): string
{
    if (isset($decoded['output_text']) && is_string($decoded['output_text'])) {
        $outputText = trim((string) $decoded['output_text']);
        if ($outputText !== '') {
            return $outputText;
        }
    }

    foreach (['data', 'response'] as $key) {
        $nested = $decoded[$key] ?? null;
        if (is_array($nested)) {
            $nestedText = classic22AiExtractTextFromResponsesOutput($nested);
            if ($nestedText !== '') {
                return $nestedText;
            }
        }
    }

    $output = $decoded['output'] ?? null;
    if (!is_array($output)) {
        return '';
    }

    $texts = [];
    foreach ($output as $item) {
        if (!is_array($item)) {
            continue;
        }

        $content = $item['content'] ?? null;
        if (is_string($content)) {
            $text = trim($content);
            if ($text !== '') {
                $texts[] = $text;
            }
            continue;
        }

        if (!is_array($content)) {
            $message = $item['message'] ?? null;
            if (is_array($message) && isset($message['content'])) {
                $mc = $message['content'];
                if (is_string($mc)) {
                    $text = trim($mc);
                    if ($text !== '') {
                        $texts[] = $text;
                    }
                } elseif (is_array($mc)) {
                    foreach ($mc as $part) {
                        if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                            $text = trim((string) $part['text']);
                            if ($text !== '') {
                                $texts[] = $text;
                            }
                        }
                    }
                }
            }
            continue;
        }

        foreach ($content as $contentItem) {
            if (is_string($contentItem)) {
                $text = trim($contentItem);
                if ($text !== '') {
                    $texts[] = $text;
                }
                continue;
            }

            if (!is_array($contentItem)) {
                continue;
            }

            $rawText = $contentItem['text'] ?? '';
            if (is_array($rawText) && isset($rawText['value']) && is_string($rawText['value'])) {
                $rawText = $rawText['value'];
            }
            if (!is_string($rawText)) {
                $rawText = (string) ($contentItem['content'] ?? '');
            }

            $text = trim((string) $rawText);
            if ($text !== '') {
                $texts[] = $text;
            }
        }
    }

    return trim(implode("\n", $texts));
}

function classic22AiExtractAnswerByMode(array $decoded, string $mode): string
{
    if ($mode === 'responses') {
        $text = classic22AiExtractTextFromResponsesOutput($decoded);
        if ($text !== '') {
            return $text;
        }
    }

    $text = classic22AiExtractTextFromResponse($decoded);
    if ($text !== '') {
        return $text;
    }

    foreach (['data', 'response'] as $key) {
        $nested = $decoded[$key] ?? null;
        if (is_array($nested)) {
            $nestedText = classic22AiExtractAnswerByMode($nested, $mode);
            if ($nestedText !== '') {
                return $nestedText;
            }
        }
    }

    return '';
}

function classic22AiRequest(string $apiUrl, string $payload, string $apiKey): array
{
    return classic22LinuxDoHttpRequest(
        $apiUrl,
        'POST',
        $payload,
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
            'x-api-key: ' . $apiKey,
        ]
    );
}

function classic22AiDecodeJsonBody(string $raw): ?array
{
    $rawString = (string) $raw;
    if (function_exists('gzdecode') && strlen($rawString) >= 2 && ord($rawString[0]) === 0x1f && ord($rawString[1]) === 0x8b) {
        $decodedGzip = @gzdecode($rawString);
        if (is_string($decodedGzip) && $decodedGzip !== '') {
            $rawString = $decodedGzip;
        }
    }

    $text = ltrim($rawString);
    if ($text === '') {
        return null;
    }

    if (strncmp($text, "\xEF\xBB\xBF", 3) === 0) {
        $text = ltrim(substr($text, 3));
    }

    if (strncmp($text, ")]}'", 4) === 0) {
        $text = ltrim(substr($text, 4));
    }

    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    if (strpos($text, 'data:') !== false) {
        $lines = preg_split("/\r\n|\n|\r/", $text);
        if (is_array($lines)) {
            $candidates = [];
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if ($line === '' || stripos($line, 'data:') !== 0) {
                    continue;
                }

                $payload = trim(substr($line, 5));
                if ($payload === '' || $payload === '[DONE]') {
                    continue;
                }

                $candidates[] = $payload;
            }

            for ($i = count($candidates) - 1; $i >= 0; $i--) {
                $decoded = json_decode((string) $candidates[$i], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
    }

    $first = strpos($text, '{');
    $last = strrpos($text, '}');
    if ($first !== false && $last !== false && $last > $first) {
        $snippet = substr($text, $first, $last - $first + 1);
        $decoded = json_decode((string) $snippet, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $first = strpos($text, '[');
    $last = strrpos($text, ']');
    if ($first !== false && $last !== false && $last > $first) {
        $snippet = substr($text, $first, $last - $first + 1);
        $decoded = json_decode((string) $snippet, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

function classic22AiExtractErrorMessageFromDecoded($decoded): string
{
    if (!is_array($decoded)) {
        return '';
    }

    $error = $decoded['error'] ?? null;
    if (is_string($error)) {
        $msg = trim($error);
        if ($msg !== '') {
            return $msg;
        }
    }

    if (is_array($error)) {
        foreach (['message', 'detail', 'error_description'] as $key) {
            if (isset($error[$key]) && is_string($error[$key])) {
                $msg = trim((string) $error[$key]);
                if ($msg !== '') {
                    return $msg;
                }
            }
        }
    }

    foreach (['message', 'detail', 'error_description'] as $key) {
        if (isset($decoded[$key]) && is_string($decoded[$key])) {
            $msg = trim((string) $decoded[$key]);
            if ($msg !== '') {
                return $msg;
            }
        }
    }

    $errors = $decoded['errors'] ?? null;
    if (is_array($errors) && !empty($errors[0]) && is_array($errors[0]) && isset($errors[0]['message']) && is_string($errors[0]['message'])) {
        $msg = trim((string) $errors[0]['message']);
        if ($msg !== '') {
            return $msg;
        }
    }

    return '';
}

function classic22AiExtractRemoteErrorMessage(array $response): string
{
    $body = function_exists('classic22AiDecodeJsonBody')
        ? classic22AiDecodeJsonBody((string) ($response['body'] ?? ''))
        : json_decode((string) ($response['body'] ?? ''), true);
    if (is_array($body) && isset($body['error']) && is_array($body['error']) && isset($body['error']['message'])) {
        return trim((string) $body['error']['message']);
    }

    if (is_array($body) && isset($body['message']) && is_string($body['message'])) {
        return trim((string) $body['message']);
    }

    if (is_array($body) && isset($body['detail']) && is_string($body['detail'])) {
        return trim((string) $body['detail']);
    }

    $error = trim((string) ($response['error'] ?? ''));
    if ($error !== '') {
        return $error;
    }

    $rawBody = trim((string) ($response['body'] ?? ''));
    if ($rawBody !== '') {
        $clean = trim(preg_replace('/\s+/u', ' ', strip_tags($rawBody)));
        if ($clean !== '') {
            return \Typecho\Common::subStr($clean, 0, 240, '...');
        }
    }

    return '';
}

function classic22AiResponseBodyExcerpt(array $response, int $limit = 240): string
{
    $rawBody = trim((string) ($response['body'] ?? ''));
    if ($rawBody === '') {
        return '';
    }

    $clean = trim(preg_replace('/\s+/u', ' ', strip_tags($rawBody)));
    if ($clean === '') {
        return '';
    }

    return \Typecho\Common::subStr($clean, 0, $limit, '...');
}

function classic22AiBuildMessages($archive, string $prompt, string $question, ?array $article): array
{
    $context = classic22AiBuildSiteContext($archive);
    $githubContext = classic22AiBuildGithubContext($archive);

    $systemContent = $prompt;

    $siteLines = [];
    $siteLines[] = '站点文章数：' . (int) ($context['site']['posts'] ?? 0);
    $siteLines[] = '站点评论数：' . (int) ($context['site']['comments'] ?? 0);
    $siteLines[] = '分类数：' . (int) ($context['site']['categories'] ?? 0);
    $systemContent .= "\n\n【站点概况】\n" . implode("\n", $siteLines);

    if (!empty($context['topComments'])) {
        $commentLines = [];
        foreach ($context['topComments'] as $index => $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $commentLines[] = (string) ($index + 1)
                . '. ' . $title
                . '（评论 ' . (int) ($item['comments'] ?? 0)
                . '，浏览 ' . (int) ($item['views'] ?? 0) . '）';
        }
        if (!empty($commentLines)) {
            $systemContent .= "\n\n【文章评论排行】\n" . implode("\n", $commentLines);
        }
    }

    if (!empty($context['topViews'])) {
        $viewLines = [];
        foreach ($context['topViews'] as $index => $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $viewLines[] = (string) ($index + 1)
                . '. ' . $title
                . '（浏览 ' . (int) ($item['views'] ?? 0)
                . '，评论 ' . (int) ($item['comments'] ?? 0) . '）';
        }
        if (!empty($viewLines)) {
            $systemContent .= "\n\n【文章浏览排行】\n" . implode("\n", $viewLines);
        }
    }

    if (!empty($context['authors'])) {
        $authorLines = [];
        foreach ($context['authors'] as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $authorLines[] = $name . '（文章 ' . (int) ($item['posts'] ?? 0) . '）';
        }
        if (!empty($authorLines)) {
            $systemContent .= "\n\n【作者活跃度】\n" . implode('、', $authorLines);
        }
    }

    if (!empty($context['categories'])) {
        $categoryLines = [];
        foreach ($context['categories'] as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $categoryLines[] = $name . '（文章 ' . (int) ($item['posts'] ?? 0) . '）';
        }
        if (!empty($categoryLines)) {
            $systemContent .= "\n\n【分类分布】\n" . implode('、', $categoryLines);
        }
    }

    if (!empty($context['tags'])) {
        $tagLines = [];
        foreach ($context['tags'] as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $tagLines[] = $name . '（文章 ' . (int) ($item['posts'] ?? 0) . '）';
        }
        if (!empty($tagLines)) {
            $systemContent .= "\n\n【标签分布】\n" . implode('、', $tagLines);
        }
    }

    if (!empty($context['recentPosts'])) {
        $recentLines = [];
        foreach ($context['recentPosts'] as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $excerpt = trim((string) ($item['excerpt'] ?? ''));
            $recentLines[] = $title
                . '（' . trim((string) ($item['date'] ?? ''))
                . '，评论 ' . (int) ($item['comments'] ?? 0)
                . '，浏览 ' . (int) ($item['views'] ?? 0)
                . '）摘要：' . $excerpt;
        }
        if (!empty($recentLines)) {
            $systemContent .= "\n\n【近期文章摘要】\n" . implode("\n", $recentLines);
        }
    }

    if (!empty($githubContext['topRepos'])) {
        $repoLines = [];
        foreach ($githubContext['topRepos'] as $item) {
            $repo = trim((string) ($item['repo'] ?? ''));
            if ($repo === '') {
                continue;
            }

            $repoLines[] = $repo
                . '（被提及 ' . (int) ($item['count'] ?? 0)
                . ' 次，相关文章总评论 ' . (int) ($item['comments'] ?? 0)
                . '，总浏览 ' . (int) ($item['views'] ?? 0) . '）';
        }

        if (!empty($repoLines)) {
            $systemContent .= "\n\n【GitHub 仓库提及统计】\n" . implode("\n", $repoLines);
        }
    }

    $messages = [
        [
            'role' => 'system',
            'content' => $systemContent,
        ],
    ];

    if (is_array($article) && !empty($article['title'])) {
        $articleContext = "【文章信息】\n"
            . '标题：' . (string) ($article['title'] ?? '') . "\n"
            . '链接：' . (string) ($article['permalink'] ?? '') . "\n"
            . '发布日期：' . (string) ($article['date'] ?? '') . "\n"
            . '浏览量：' . (int) ($article['views'] ?? 0) . "\n"
            . '评论数：' . (int) ($article['comments'] ?? 0) . "\n"
            . "【文章内容】\n"
            . trim(strip_tags((string) ($article['content'] ?? $article['excerpt'] ?? '')));

        $messages[] = [
            'role' => 'system',
            'content' => $articleContext,
        ];
    }

    $messages[] = [
        'role' => 'user',
        'content' => $question,
    ];

    return $messages;
}

function classic22AiExtractTextFromResponse(array $decoded): string
{
    $choices = $decoded['choices'] ?? null;
    if (!is_array($choices) || empty($choices[0]) || !is_array($choices[0])) {
        return '';
    }

    $first = $choices[0];
    $message = $first['message'] ?? null;

    if (is_array($message) && isset($message['content'])) {
        $content = $message['content'];
        if (is_string($content)) {
            return trim($content);
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $parts[] = $part['text'];
                }
            }
            return trim(implode("\n", $parts));
        }
    }

    if (isset($first['text']) && is_string($first['text'])) {
        return trim($first['text']);
    }

    return '';
}

function classic22AiNormalizeRemoteError(string $message): string
{
    $normalized = trim($message);
    if ($normalized === '') {
        return 'AI 服务暂不可用，请稍后重试。';
    }

    $lower = strtolower($normalized);

    if (
        strpos($lower, 'country, region, or territory not supported') !== false
        || strpos($lower, 'unsupported_country_region_territory') !== false
        || strpos($lower, 'region is not supported') !== false
    ) {
        return '当前地区不可使用该 AI 服务。请到主题设置将「AI 接口地址」改为可用的 OpenAI 兼容服务，并更换对应 API Key。';
    }

    return $normalized;
}

function classic22AiIsRegionBlockedError(string $message): bool
{
    $lower = strtolower(trim($message));
    if ($lower === '') {
        return false;
    }

    return strpos($lower, 'country, region, or territory not supported') !== false
        || strpos($lower, 'unsupported_country_region_territory') !== false
        || strpos($lower, 'region is not supported') !== false;
}

function classic22AiHandleRequest($archive): void
{
    if (!is_object($archive) || !isset($archive->request)) {
        return;
    }

    $action = trim((string) $archive->request->get('classic22_ai'));
    if ($action === '') {
        return;
    }

    if (!classic22AiEnabled($archive->options)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '首页 AI 对话未开启。',
        ], 403);
    }

    if (!classic22AiIsAllowedDomainRequest($archive->options)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '当前域名未被允许使用 AI 对话。请在主题设置中检查「AI 允许域名」。',
        ], 403);
    }

    if ($action === 'articles') {
        classic22AiSendJson([
            'ok' => true,
            'items' => classic22AiBuildArticleListPayload($archive),
        ]);
    }

    if ($action !== 'chat') {
        classic22AiSendJson([
            'ok' => false,
            'message' => '无效请求。',
        ], 400);
    }

    $rawBody = '';
    try {
        $rawBody = (string) file_get_contents('php://input');
    } catch (\Throwable $exception) {
        $rawBody = '';
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '请求参数错误。',
        ], 400);
    }

    $question = trim((string) ($data['message'] ?? ''));
    if ($question === '') {
        classic22AiSendJson([
            'ok' => false,
            'message' => '请输入问题内容。',
        ], 400);
    }

    $allowedModels = classic22AiGetModels($archive->options);
    $requestedModel = trim((string) ($data['model'] ?? ''));

    if ($requestedModel === '') {
        $model = classic22AiDefaultModel($archive->options);
    } elseif (in_array($requestedModel, $allowedModels, true)) {
        $model = $requestedModel;
    } elseif (preg_match('/^[a-zA-Z0-9._:-]{2,120}$/', $requestedModel)) {
        $model = $requestedModel;
    } else {
        $model = classic22AiDefaultModel($archive->options);
    }

    $selectedArticleId = (int) ($data['articleId'] ?? 0);
    $selectedArticleUrl = trim((string) ($data['articleUrl'] ?? ''));

    $clientIp = classic22AiExtractClientIp();
    $dailyLimit = 5;
    $quota = classic22AiConsumeDailyQuota($clientIp, $dailyLimit);
    if (!empty($quota['error'])) {
        classic22AiLogChatRequest($clientIp, $question, [
            'ok' => false,
            'blocked' => 'quota_error',
            'error' => (string) $quota['error'],
            'limit' => $dailyLimit,
            'model' => $model,
            'articleId' => $selectedArticleId,
            'articleUrl' => $selectedArticleUrl,
        ]);
        classic22AiSendJson([
            'ok' => false,
            'message' => (string) $quota['error'],
        ], 500);
    }

    if (empty($quota['ok'])) {
        classic22AiLogChatRequest($clientIp, $question, [
            'ok' => false,
            'blocked' => 'daily_quota',
            'date' => (string) ($quota['date'] ?? ''),
            'used' => (int) ($quota['used'] ?? 0),
            'limit' => $dailyLimit,
            'model' => $model,
            'articleId' => $selectedArticleId,
            'articleUrl' => $selectedArticleUrl,
        ]);

        classic22AiSendJson([
            'ok' => false,
            'message' => '今日 AI 使用次数已达上限（' . $dailyLimit . ' 次/天）。请明天再试。',
        ], 429);
    }

    classic22AiLogChatRequest($clientIp, $question, [
        'ok' => true,
        'date' => (string) ($quota['date'] ?? ''),
        'used' => (int) ($quota['used'] ?? 0),
        'remaining' => (int) ($quota['remaining'] ?? 0),
        'limit' => $dailyLimit,
        'model' => $model,
        'articleId' => $selectedArticleId,
        'articleUrl' => $selectedArticleUrl,
    ]);

    $article = null;
    if ($selectedArticleId > 0) {
        $article = classic22AiFindArticleById($archive, $selectedArticleId);
    }

    if ($article === null && $selectedArticleUrl !== '') {
        $article = classic22AiFindArticleByUrl($archive, $selectedArticleUrl);
    }

    $configuredProvider = strtolower(trim((string) classic22LinuxDoGetOption($archive->options, 'aiProvider', 'openai')));
    $provider = $configuredProvider;
    if (!in_array($provider, ['openai', 'rightcode'], true)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => '当前 AI 接口类型不受支持。',
        ], 400);
    }

    $apiKey = trim((string) classic22LinuxDoGetOption($archive->options, 'aiApiKey', ''));
    if ($apiKey === '') {
        classic22AiSendJson([
            'ok' => false,
            'message' => '请先在主题设置中配置 AI API Key。',
        ], 500);
    }

    $configuredBaseUrl = classic22LinuxDoGetOption($archive->options, 'aiApiBaseUrl', 'https://api.openai.com/v1');
    $baseUrl = classic22AiSanitizeBaseUrl((string) $configuredBaseUrl, $provider);

    if ($provider === 'rightcode') {
        $baseLower = strtolower($baseUrl);
        if ($baseLower === '' || strpos($baseLower, 'api.openai.com') !== false) {
            $baseUrl = 'https://www.right.codes/codex/v1';
        }
    }

    $configuredMode = classic22AiResolveApiMode($archive->options);
    $mode = $configuredMode;

    if ($provider === 'rightcode' && $mode !== 'responses') {
        $mode = 'responses';
    }

    $apiUrl = $baseUrl . ($mode === 'responses' ? '/responses' : '/chat/completions');

    $messages = classic22AiBuildMessages($archive, classic22AiSystemPrompt($archive->options), $question, $article);

    $payload = $mode === 'responses'
        ? classic22AiBuildResponsesPayload($model, $messages)
        : classic22AiBuildChatCompletionsPayload($model, $messages);

    if (!is_string($payload) || trim($payload) === '') {
        classic22AiSendJson([
            'ok' => false,
            'message' => '请求构造失败。',
        ], 500);
    }

    $response = classic22AiRequest($apiUrl, $payload, $apiKey);

    $fallbackTried = false;
    $fallbackUsed = false;
    $fallbackInfo = null;

    if (empty($response['ok']) && $provider === 'openai') {
        $rawRemoteError = classic22AiExtractRemoteErrorMessage($response);
        if (classic22AiIsRegionBlockedError($rawRemoteError)) {
            $fallbackTried = true;
            $fallbackProvider = 'rightcode';
            $fallbackMode = 'responses';
            $fallbackBaseUrl = classic22AiSanitizeBaseUrl((string) $configuredBaseUrl, $fallbackProvider);
            $fallbackBaseLower = strtolower($fallbackBaseUrl);
            if ($fallbackBaseLower === '' || strpos($fallbackBaseLower, 'api.openai.com') !== false) {
                $fallbackBaseUrl = 'https://www.right.codes/codex/v1';
            }

            $fallbackApiUrl = $fallbackBaseUrl . '/responses';
            $fallbackPayload = classic22AiBuildResponsesPayload($model, $messages);

            if (is_string($fallbackPayload) && trim($fallbackPayload) !== '') {
                $fallbackResponse = classic22AiRequest($fallbackApiUrl, $fallbackPayload, $apiKey);
                $fallbackInfo = [
                    'provider' => $fallbackProvider,
                    'mode' => $fallbackMode,
                    'apiUrl' => $fallbackApiUrl,
                    'httpStatus' => (int) ($fallbackResponse['status'] ?? 0),
                    'error' => classic22AiExtractRemoteErrorMessage($fallbackResponse),
                    'bodyExcerpt' => classic22AiResponseBodyExcerpt($fallbackResponse),
                ];

                if (empty($fallbackResponse['ok'])) {
                    $fallbackCompatApiUrl = $fallbackBaseUrl . '/chat/completions';
                    $fallbackCompatPayload = classic22AiBuildChatCompletionsPayload($model, $messages);
                    if (is_string($fallbackCompatPayload) && trim($fallbackCompatPayload) !== '') {
                        $fallbackCompatResponse = classic22AiRequest($fallbackCompatApiUrl, $fallbackCompatPayload, $apiKey);
                        $fallbackInfo['compat'] = [
                            'provider' => $fallbackProvider,
                            'mode' => 'chat_completions',
                            'apiUrl' => $fallbackCompatApiUrl,
                            'httpStatus' => (int) ($fallbackCompatResponse['status'] ?? 0),
                            'error' => classic22AiExtractRemoteErrorMessage($fallbackCompatResponse),
                            'bodyExcerpt' => classic22AiResponseBodyExcerpt($fallbackCompatResponse),
                        ];

                        if (!empty($fallbackCompatResponse['ok'])) {
                            $provider = $fallbackProvider;
                            $mode = 'chat_completions';
                            $baseUrl = $fallbackBaseUrl;
                            $apiUrl = $fallbackCompatApiUrl;
                            $payload = $fallbackCompatPayload;
                            $response = $fallbackCompatResponse;
                            $fallbackUsed = true;
                        }
                    }
                }

                if (!empty($fallbackResponse['ok'])) {
                    $provider = $fallbackProvider;
                    $mode = $fallbackMode;
                    $baseUrl = $fallbackBaseUrl;
                    $apiUrl = $fallbackApiUrl;
                    $payload = $fallbackPayload;
                    $response = $fallbackResponse;
                    $fallbackUsed = true;
                }
            }
        }
    }

    if (empty($response['ok'])) {
        $message = '';
        $status = 502;
        $rawRemoteError = classic22AiExtractRemoteErrorMessage($response);
        if ($rawRemoteError !== '') {
            $message = classic22AiNormalizeRemoteError($rawRemoteError);
            if (classic22AiIsRegionBlockedError($rawRemoteError)) {
                $status = 403;
            }
        } else {
            $message = 'AI 服务暂不可用，请稍后重试。';
        }

        classic22AiSendJson([
            'ok' => false,
            'message' => $message,
        ], $status);
    }

    $decoded = classic22AiDecodeJsonBody((string) ($response['body'] ?? ''));
    if (!is_array($decoded)) {
        classic22AiSendJson([
            'ok' => false,
            'message' => 'AI 返回格式错误。',
        ], 502);
    }

    $decodedError = classic22AiExtractErrorMessageFromDecoded($decoded);
    if ($decodedError !== '') {
        classic22AiSendJson([
            'ok' => false,
            'message' => classic22AiNormalizeRemoteError($decodedError),
        ], classic22AiIsRegionBlockedError($decodedError) ? 403 : 502);
    }

    $answer = classic22AiExtractAnswerByMode($decoded, $mode);
    if ($answer === '' && $mode === 'responses') {
        $fallbackApiUrl = $baseUrl . '/chat/completions';
        $fallbackPayload = classic22AiBuildChatCompletionsPayload($model, $messages);
        if (is_string($fallbackPayload) && trim($fallbackPayload) !== '') {
            $fallbackResponse = classic22AiRequest($fallbackApiUrl, $fallbackPayload, $apiKey);
            if (!empty($fallbackResponse['ok'])) {
                $fallbackDecoded = classic22AiDecodeJsonBody((string) ($fallbackResponse['body'] ?? ''));
                if (is_array($fallbackDecoded) && classic22AiExtractErrorMessageFromDecoded($fallbackDecoded) === '') {
                    $fallbackAnswer = classic22AiExtractAnswerByMode($fallbackDecoded, 'chat_completions');
                    if ($fallbackAnswer !== '') {
                        classic22AiSendJson([
                            'ok' => true,
                            'answer' => $fallbackAnswer,
                            'model' => $model,
                        ]);
                    }
                }
            }
        }
    }

    if ($answer === '') {
        classic22AiSendJson([
            'ok' => false,
            'message' => $mode === 'responses'
                ? 'AI 未返回可用内容（responses）。请在主题设置中检查接口地址是否正确，例如 https://www.right.codes/codex/v1。'
                : 'AI 未返回可用内容。',
        ], 502);
    }

    classic22AiSendJson([
        'ok' => true,
        'answer' => $answer,
        'model' => $model,
    ]);
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
