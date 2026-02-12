(function () {
  function parseLangs(raw) {
    if (!raw) {
      return [];
    }

    try {
      var parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) {
        return [];
      }

      var out = [];
      parsed.forEach(function (item) {
        var value = String(item || '').trim().toLowerCase();
        value = value.replace(/[^0-9a-z-]+/g, '');
        if (!value) {
          return;
        }
        if (out.indexOf(value) === -1) {
          out.push(value);
        }
      });
      return out;
    } catch (err) {
      return [];
    }
  }

  function sanitizeLang(raw) {
    var value = String(raw || '').trim().toLowerCase();
    value = value.replace(/[^0-9a-z-]+/g, '');
    return value;
  }

  function buildUrl(baseUrl, params) {
    try {
      var url = new URL(baseUrl, window.location.href);
      Object.keys(params || {}).forEach(function (key) {
        url.searchParams.set(key, params[key]);
      });
      return url.toString();
    } catch (err) {
      return '';
    }
  }

  function getTitleNode() {
    return document.querySelector('.entry-title a') || document.querySelector('.entry-title');
  }

  function getContentBodyNode() {
    return document.querySelector('[data-post-content-body]') || document.querySelector('[data-post-content]');
  }

  function dispatchUpdated(lang) {
    try {
      document.dispatchEvent(new CustomEvent('classic22:postContentUpdated', { detail: { lang: lang } }));
    } catch (err) {
    }
  }

  function run() {
    var select = document.querySelector('[data-post-lang-switch]');
    if (!select) {
      return;
    }

    var defaultLang = sanitizeLang(select.getAttribute('data-default-lang') || 'zh') || 'zh';
    var langs = parseLangs(select.getAttribute('data-langs') || '');
    if (langs.indexOf(defaultLang) === -1) {
      langs.unshift(defaultLang);
    }

    var apiUrl = String(select.getAttribute('data-translate-api') || '').trim();
    var cid = parseInt(String(select.getAttribute('data-translate-cid') || '0'), 10) || 0;
    var ctype = sanitizeLang(select.getAttribute('data-translate-ctype') || 'post') || 'post';

    var titleNode = getTitleNode();
    var contentNode = getContentBodyNode();
    if (!titleNode || !contentNode || !apiUrl || cid <= 0) {
      return;
    }

    var originalTitle = titleNode.textContent || '';
    var originalHtml = contentNode.innerHTML;
    var cache = {};
    var controller = null;

    function setBusy(busy) {
      select.disabled = Boolean(busy);
      select.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function applyContent(nextTitle, nextHtml, lang) {
      if (typeof nextTitle === 'string' && nextTitle.trim()) {
        titleNode.textContent = nextTitle;
      } else {
        titleNode.textContent = originalTitle;
      }

      if (typeof nextHtml === 'string') {
        contentNode.innerHTML = nextHtml;
      }

      dispatchUpdated(lang);
    }

    function restore() {
      select.value = defaultLang;
      applyContent(originalTitle, originalHtml, defaultLang);
    }

    function fetchAndApply(lang) {
      if (cache[lang]) {
        applyContent(cache[lang].title, cache[lang].html, lang);
        return;
      }

      if (controller && controller.abort) {
        controller.abort();
      }
      controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

      var url = buildUrl(apiUrl, {
        cid: String(cid),
        ctype: ctype,
        lang: lang
      });
      if (!url) {
        restore();
        return;
      }

      setBusy(true);

      fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json'
        },
        signal: controller ? controller.signal : undefined
      })
        .then(function (response) {
          if (!response.ok) {
            return null;
          }
          return response.json();
        })
        .then(function (data) {
          if (!data || !data.ok) {
            restore();
            return;
          }

          var title = typeof data.title === 'string' ? data.title : '';
          var html = typeof data.html === 'string' ? data.html : '';
          cache[lang] = { title: title, html: html };
          applyContent(title, html, lang);
        })
        .catch(function () {
          restore();
        })
        .finally(function () {
          setBusy(false);
        });
    }

    select.addEventListener('change', function () {
      var lang = sanitizeLang(select.value || defaultLang) || defaultLang;
      if (langs.indexOf(lang) === -1) {
        lang = defaultLang;
      }

      if (lang === defaultLang) {
        restore();
        return;
      }

      fetchAndApply(lang);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
