(() => {
  const ROOT_SELECTOR = '[data-home-ai-root]';
  const ARTICLE_LABEL_MAX = 14;
  const MODEL_LABEL_MAX = 16;

  function parseBootstrap(root) {
    const node = root.querySelector('[data-home-ai-bootstrap]');
    if (!node) return null;

    const raw = String(node.textContent || '').trim();
    if (!raw) return null;

    try {
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (e) {
      return null;
    }
  }

  function isNonEmptyString(value) {
    return typeof value === 'string' && value.trim() !== '';
  }

  function truncateLabel(value, maxLength) {
    const text = String(value || '').trim();
    if (!text) return '';

    const chars = Array.from(text);
    if (chars.length <= maxLength) {
      return text;
    }

    const safeLength = Math.max(1, maxLength - 1);
    return `${chars.slice(0, safeLength).join('')}…`;
  }

  function normalizeModels(rawModels) {
    if (!Array.isArray(rawModels)) {
      return ['gpt-4o-mini'];
    }

    const values = rawModels
      .map((item) => String(item || '').trim())
      .filter((item) => item !== '');

    if (!values.length) {
      return ['gpt-4o-mini'];
    }

    return Array.from(new Set(values));
  }

  function normalizeArticles(rawArticles) {
    if (!Array.isArray(rawArticles)) {
      return [];
    }

    return rawArticles
      .map((item) => {
        if (!item || typeof item !== 'object') return null;

        const id = Number(item.id || 0);
        if (!Number.isFinite(id) || id <= 0) return null;

        return {
          id,
          title: String(item.title || '').trim() || `文章 #${id}`,
          permalink: String(item.permalink || '').trim(),
          excerpt: String(item.excerpt || '').trim(),
          date: String(item.date || '').trim(),
        };
      })
      .filter(Boolean);
  }

  function buildArticleMap(articles) {
    const map = new Map();
    if (!Array.isArray(articles)) {
      return map;
    }

    articles.forEach((article) => {
      if (!article || typeof article !== 'object') return;
      const id = Number(article.id || 0);
      if (!Number.isFinite(id) || id <= 0) return;
      map.set(id, article);
    });

    return map;
  }

  function attachArticleSelectHandler(state) {
    const { articleSelect, input } = state;
    if (!articleSelect || !input) return;

    articleSelect.addEventListener('change', () => {
      const selectedId = Number(articleSelect.value || 0);
      const selectedArticle = state.articleMap && state.articleMap.get(selectedId)
        ? state.articleMap.get(selectedId)
        : null;
      const text = selectedArticle
        ? String(selectedArticle.title || '').trim()
        : String((articleSelect.options[articleSelect.selectedIndex] || {}).textContent || '').trim();

      if (!text || articleSelect.value === '0') {
        return;
      }

      const current = String(input.value || '').trim();
      if (current !== '') {
        return;
      }

      input.value = `请帮我总结《${text}》这篇文章的重点`;
      input.focus();
    });
  }

  async function refreshArticlesIfNeeded(state) {
    const { articleSelect, labels } = state;
    if (!articleSelect) return;

    const hasOptions = articleSelect.options.length > 1;
    if (hasOptions || !isNonEmptyString(state.articlesApiUrl)) {
      return;
    }

    try {
      const response = await fetch(state.articlesApiUrl, { method: 'GET' });
      if (!response.ok) return;

      const data = await response.json();
      if (!data || data.ok !== true || !Array.isArray(data.items)) {
        return;
      }

      const articles = normalizeArticles(data.items);
      const selectedBefore = String(articleSelect.value || '0');
      state.articleMap = buildArticleMap(articles);
      fillArticleSelect(articleSelect, articles, labels);
      if (selectedBefore !== '0' && state.articleMap.has(Number(selectedBefore))) {
        articleSelect.value = selectedBefore;
      }
    } catch (e) {
    }
  }

  function fillArticleSelect(select, articles, labels) {
    if (!select) return;

    select.innerHTML = '';

    const allOption = document.createElement('option');
    allOption.value = '0';
    allOption.textContent = labels.allArticles || '全部文章';
    select.appendChild(allOption);

    articles.forEach((article) => {
      const option = document.createElement('option');
      option.value = String(article.id);
      option.textContent = truncateLabel(article.title, ARTICLE_LABEL_MAX) || article.title;
      option.title = article.title;
      select.appendChild(option);
    });
  }

  function fillModelSelect(select, models, defaultModel) {
    if (!select) return;

    select.innerHTML = '';
    let selected = models[0] || 'gpt-4o-mini';

    models.forEach((model) => {
      const option = document.createElement('option');
      option.value = model;
      option.textContent = truncateLabel(model, MODEL_LABEL_MAX) || model;
      option.title = model;
      if (model === defaultModel) {
        option.selected = true;
        selected = model;
      }
      select.appendChild(option);
    });

    if (!select.value) {
      select.value = selected;
    }
  }

  function safeText(value) {
    return String(value == null ? '' : value);
  }

  function escapeHtml(value) {
    return safeText(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function normalizeLinkUrl(rawUrl) {
    const value = String(rawUrl || '').replace(/&amp;/g, '&').trim();
    if (!/^https?:\/\//i.test(value)) {
      return '#';
    }
    return value;
  }

  function renderInlineMarkdown(escapedText) {
    const tokens = [];

    const makeToken = (html) => {
      const id = `@@MD_INLINE_${tokens.length}@@`;
      tokens.push(html);
      return id;
    };

    let html = String(escapedText || '');

    html = html.replace(/`([^`\n]+)`/g, (_, code) => makeToken(`<code>${code}</code>`));
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, label, url) => {
      const href = normalizeLinkUrl(url);
      const safeHref = escapeHtml(href);
      return makeToken(`<a href="${safeHref}" target="_blank" rel="noopener noreferrer">${label}</a>`);
    });
    html = html.replace(/\*\*([^*][\s\S]*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*([^*][\s\S]*?)\*/g, '<em>$1</em>');
    html = html.replace(/~~([^~][\s\S]*?)~~/g, '<del>$1</del>');

    return html.replace(/@@MD_INLINE_(\d+)@@/g, (_, index) => tokens[Number(index)] || '');
  }

  function renderMarkdown(text) {
    const source = safeText(text).replace(/\r\n?/g, '\n');
    if (!source.trim()) {
      return '';
    }

    const codeBlocks = [];
    const textWithTokens = source.replace(/```([^`\n]*)\n([\s\S]*?)```/g, (_, lang, code) => {
      const token = `@@MD_CODE_${codeBlocks.length}@@`;
      codeBlocks.push({
        lang: String(lang || '').trim(),
        code: String(code || ''),
      });
      return token;
    });

    const escaped = escapeHtml(textWithTokens);
    const segments = escaped
      .split(/\n{2,}/)
      .map((segment) => segment.trim())
      .filter((segment) => segment !== '');

    const htmlSegments = segments.map((segment) => {
      const codeMatch = segment.match(/^@@MD_CODE_(\d+)@@$/);
      if (codeMatch) {
        const index = Number(codeMatch[1]);
        const block = codeBlocks[index] || { lang: '', code: '' };
        const lang = escapeHtml(block.lang.replace(/[^a-zA-Z0-9_-]/g, ''));
        const className = lang ? ` class="language-${lang}"` : '';
        return `<pre><code${className}>${escapeHtml(block.code)}</code></pre>`;
      }

      const lines = segment.split('\n');
      if (lines.every((line) => /^\s*>\s?/.test(line))) {
        const quoteHtml = lines
          .map((line) => line.replace(/^\s*>\s?/, ''))
          .map((line) => renderInlineMarkdown(line))
          .join('<br>');
        return `<blockquote>${quoteHtml}</blockquote>`;
      }

      if (lines.every((line) => /^\s*[-*+]\s+/.test(line))) {
        const items = lines
          .map((line) => line.replace(/^\s*[-*+]\s+/, ''))
          .map((line) => `<li>${renderInlineMarkdown(line)}</li>`)
          .join('');
        return `<ul>${items}</ul>`;
      }

      if (lines.every((line) => /^\s*\d+[.)]\s+/.test(line))) {
        const items = lines
          .map((line) => line.replace(/^\s*\d+[.)]\s+/, ''))
          .map((line) => `<li>${renderInlineMarkdown(line)}</li>`)
          .join('');
        return `<ol>${items}</ol>`;
      }

      const heading = segment.match(/^\s*(#{1,6})\s+([\s\S]+)$/);
      if (heading) {
        const level = Math.min(6, Math.max(1, heading[1].length));
        return `<h${level}>${renderInlineMarkdown(heading[2])}</h${level}>`;
      }

      const paragraph = lines.map((line) => renderInlineMarkdown(line)).join('<br>');
      return `<p>${paragraph}</p>`;
    });

    return htmlSegments.join('');
  }

  function highlightMessageCode(root) {
    if (!root || !window.hljs) {
      return;
    }

    root.querySelectorAll('pre code').forEach((block) => {
      try {
        window.hljs.highlightElement(block);
      } catch (e) {
      }
    });
  }

  function setMessageContent(node, role, text, forcePlain = false) {
    if (!node) return;

    const value = safeText(text);
    if (forcePlain || role !== 'assistant') {
      node.textContent = value;
      return;
    }

    node.innerHTML = renderMarkdown(value);
    highlightMessageCode(node);
  }

  function appendMessage(list, role, text) {
    if (!list) return null;

    const item = document.createElement('div');
    item.className = `classic22-home-ai-message classic22-home-ai-message--${role}`;
    setMessageContent(item, role, text, false);
    list.appendChild(item);
    return item;
  }

  function enterChatMode(state) {
    const { layout, root, chatTop, messageList } = state;
    if (!layout || !root) return;

    layout.classList.add('is-ai-chatting');
    root.classList.add('is-chat-mode');

    if (chatTop) {
      chatTop.hidden = false;
    }

    if (messageList) {
      messageList.hidden = false;
    }
  }

  function leaveChatMode(state) {
    const { layout, root, chatTop, messageList, input } = state;
    if (!layout || !root) return;

    layout.classList.remove('is-ai-chatting');
    root.classList.remove('is-chat-mode');

    if (chatTop) {
      chatTop.hidden = true;
    }

    if (messageList) {
      messageList.hidden = true;
      messageList.innerHTML = '';
    }

    if (input) {
      input.value = '';
      input.focus();
    }
  }

  function setSending(state, sending) {
    const { sendButton, input, modelSelect, articleSelect } = state;
    state.sending = !!sending;

    if (sendButton) {
      sendButton.disabled = !!sending;
    }

    if (input) {
      input.disabled = !!sending;
    }

    if (modelSelect) {
      modelSelect.disabled = !!sending;
    }

    if (articleSelect) {
      articleSelect.disabled = !!sending;
    }
  }

  function isModelNotConfiguredError(data) {
    const messageText = data && isNonEmptyString(data.message) ? String(data.message) : '';
    const merged = `${messageText}`;

    return /未配置模型|model.+not.+configured|configured model/i.test(merged);
  }

  function bindSelectCarets(state) {
    const root = state && state.root ? state.root : null;
    if (!root) return;

    const wrappers = Array.from(root.querySelectorAll('.classic22-home-ai-select'));
    if (!wrappers.length) return;

    const closeAll = (except = null) => {
      wrappers.forEach((wrapper) => {
        if (wrapper !== except) {
          wrapper.classList.remove('is-open');
        }
      });
    };

    wrappers.forEach((wrapper) => {
      const select = wrapper.querySelector('select');
      if (!select) return;

      const openCurrent = () => {
        if (select.disabled) return;
        closeAll(wrapper);
        wrapper.classList.add('is-open');
      };

      const closeCurrent = () => {
        wrapper.classList.remove('is-open');
      };

      wrapper.addEventListener('pointerdown', openCurrent);
      select.addEventListener('focus', openCurrent);
      select.addEventListener('click', openCurrent);
      select.addEventListener('change', closeCurrent);
      select.addEventListener('blur', () => {
        setTimeout(closeCurrent, 80);
      });
      select.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' || event.key === 'Tab') {
          closeCurrent();
          return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter' || event.key === ' ') {
          openCurrent();
        }
      });
    });

    document.addEventListener('click', (event) => {
      const target = event && event.target ? event.target : null;
      if (!target || !(target instanceof Element)) {
        closeAll();
        return;
      }

      if (!root.contains(target)) {
        closeAll();
        return;
      }

      if (!target.closest('.classic22-home-ai-select')) {
        closeAll();
      }
    });
  }

  async function requestChatOnce(chatUrl, payload) {
    const response = await fetch(chatUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    let data = null;
    try {
      data = await response.json();
    } catch (e) {
      data = null;
    }

    return { response, data };
  }

  async function sendChat(state) {
    const {
      chatUrl,
      input,
      modelSelect,
      articleSelect,
      messageList,
      labels,
    } = state;

    if (!isNonEmptyString(chatUrl) || !input || !modelSelect || !articleSelect || !messageList) {
      return;
    }

    const message = String(input.value || '').trim();
    if (!message || state.sending) {
      return;
    }

    enterChatMode(state);
    appendMessage(messageList, 'user', message);
    const waitingNode = appendMessage(messageList, 'assistant', labels.thinking || '正在思考中...');

    input.value = '';
    setSending(state, true);

    try {
      const selectedArticleId = Number(articleSelect.value || 0);
      const selectedArticle = state.articleMap && state.articleMap.get(selectedArticleId)
        ? state.articleMap.get(selectedArticleId)
        : null;
      const selectedModel = String(modelSelect.value || '').trim();

      const basePayload = {
        message,
        model: selectedModel,
        articleId: selectedArticleId,
        articleUrl: selectedArticle ? String(selectedArticle.permalink || '') : '',
      };

      let { response, data } = await requestChatOnce(chatUrl, basePayload);

      if ((!response.ok || !data || data.ok !== true) && isModelNotConfiguredError(data)) {
        const fallbackModel = String((modelSelect.options[0] || {}).value || '').trim();
        if (fallbackModel && fallbackModel !== selectedModel) {
          const retried = await requestChatOnce(chatUrl, {
            ...basePayload,
            model: fallbackModel,
          });

          if (retried.data && retried.data.ok === true) {
            response = retried.response;
            data = retried.data;
            modelSelect.value = fallbackModel;
          }
        }
      }

      const ok = response.ok && data && data.ok === true && isNonEmptyString(data.answer);
      if (!ok) {
        if (waitingNode) {
          waitingNode.classList.add('classic22-home-ai-message--error');
          let errorText = data && isNonEmptyString(data.message) ? data.message : '未知错误';

          const hasNativePrefix = /^(请求失败|AI 请求失败|当前地区不可使用|AI 服务暂不可用)/.test(errorText);
          setMessageContent(
            waitingNode,
            'assistant',
            hasNativePrefix
              ? errorText
              : `${labels.errorPrefix || '请求失败：'}${errorText}`,
            true
          );
        }
      } else if (waitingNode) {
        setMessageContent(waitingNode, 'assistant', String(data.answer || '').trim(), false);
      }
    } catch (e) {
      if (waitingNode) {
        waitingNode.classList.add('classic22-home-ai-message--error');
        setMessageContent(waitingNode, 'assistant', `${labels.errorPrefix || '请求失败：'}网络异常`, true);
      }
    } finally {
      setSending(state, false);
      messageList.scrollTop = messageList.scrollHeight;
      if (state.input) {
        state.input.focus();
      }
    }
  }

  function bindSubmit(state) {
    if (!state.form) return;

    state.form.addEventListener('submit', (event) => {
      event.preventDefault();
      sendChat(state);
    });

    if (state.input) {
      state.input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.shiftKey) {
          return;
        }

        event.preventDefault();
        sendChat(state);
      });
    }
  }

  function bindBack(state) {
    if (!state.backButton) return;

    state.backButton.addEventListener('click', () => {
      if (state.sending) {
        return;
      }
      leaveChatMode(state);
    });
  }

  function initRoot(root) {
    const bootstrap = parseBootstrap(root);
    if (!bootstrap) return;

    const layout = document.querySelector('[data-home-layout]');
    const form = root.querySelector('[data-home-ai-form]');
    const input = root.querySelector('[data-home-ai-input]');
    const articleSelect = root.querySelector('[data-home-ai-article]');
    const modelSelect = root.querySelector('[data-home-ai-model]');
    const sendButton = root.querySelector('[data-home-ai-send]');
    const chatTop = root.querySelector('[data-home-ai-chat-top]');
    const backButton = root.querySelector('[data-home-ai-back]');
    const messageList = root.querySelector('[data-home-ai-messages]');

    const labels = {
      allArticles: '全部文章',
      thinking: '正在思考中...',
      errorPrefix: '请求失败：',
      ...(bootstrap.labels && typeof bootstrap.labels === 'object' ? bootstrap.labels : {}),
    };

    const models = normalizeModels(bootstrap.models);
    const articles = normalizeArticles(bootstrap.articles);
    const articleMap = buildArticleMap(articles);

    fillArticleSelect(articleSelect, articles, labels);
    fillModelSelect(modelSelect, models, String(bootstrap.defaultModel || '').trim());

    const state = {
      root,
      layout,
      form,
      input,
      articleSelect,
      modelSelect,
      sendButton,
      chatTop,
      backButton,
      messageList,
      labels,
      chatUrl: String(bootstrap.chatUrl || '').trim(),
      articlesApiUrl: String(bootstrap.articlesApiUrl || '').trim(),
      articleMap,
      sending: false,
    };

    bindSubmit(state);
    bindBack(state);
    bindSelectCarets(state);
    attachArticleSelectHandler(state);
    if (articleSelect) {
      articleSelect.addEventListener('focus', () => {
        refreshArticlesIfNeeded(state);
      });
      articleSelect.addEventListener('click', () => {
        refreshArticlesIfNeeded(state);
      });
    }
    refreshArticlesIfNeeded(state);
  }

  function run() {
    document.querySelectorAll(ROOT_SELECTOR).forEach(initRoot);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
