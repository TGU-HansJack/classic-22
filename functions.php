<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('网站 Logo'),
        _t('在这里填写图片 URL，网站将显示 Logo')
    );

    $form->addInput($logoUrl->addRule('url', _t('请填写正确的 URL 地址')));

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

          var emojiInput = el('input', { type: 'text', placeholder: '📢', value: item.emoji || '' });
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

    // Authorization-related theme settings removed.
    return;

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
