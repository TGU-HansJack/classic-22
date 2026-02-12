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

  function trimSlashes(value) {
    return String(value || '').replace(/^\/+|\/+$/g, '');
  }

  function normalizeRootPath(raw) {
    var value = String(raw || '').trim();
    if (!value || value === '/') {
      return '';
    }

    value = value.replace(/\\/g, '/');
    if (value.charAt(0) !== '/') {
      value = '/' + value;
    }
    value = value.replace(/\/+$/, '');
    return value === '/' ? '' : value;
  }

  function splitPath(pathname, rootPath, langs, defaultLang) {
    var path = String(pathname || '/');

    if (rootPath && (path === rootPath || path.indexOf(rootPath + '/') === 0)) {
      path = path.slice(rootPath.length);
    }

    path = trimSlashes(path);
    if (!path) {
      return { lang: defaultLang, rest: '' };
    }

    var parts = path.split('/').filter(function (item) {
      return item !== '';
    });

    var lang = defaultLang;
    if (parts.length > 0 && langs.indexOf(parts[0]) !== -1) {
      lang = parts.shift();
    }

    return {
      lang: lang,
      rest: parts.join('/')
    };
  }

  function buildPath(rootPath, rest, lang, defaultLang) {
    var relParts = [];
    if (lang !== defaultLang) {
      relParts.push(lang);
    }
    if (rest) {
      relParts.push(trimSlashes(rest));
    }

    var rel = relParts.join('/');
    if (rootPath) {
      return rel ? rootPath + '/' + rel : rootPath + '/';
    }

    return rel ? '/' + rel : '/';
  }

  function run() {
    var select = document.querySelector('[data-post-lang-switch]');
    if (!select) {
      return;
    }

    var defaultLang = String(select.getAttribute('data-default-lang') || 'zh').trim().toLowerCase();
    if (!defaultLang) {
      defaultLang = 'zh';
    }

    var langs = parseLangs(select.getAttribute('data-langs') || '');
    if (langs.indexOf(defaultLang) === -1) {
      langs.unshift(defaultLang);
    }

    var rootPath = normalizeRootPath(select.getAttribute('data-root-path') || '');
    var current = splitPath(window.location.pathname, rootPath, langs, defaultLang);
    select.value = langs.indexOf(current.lang) !== -1 ? current.lang : defaultLang;

    select.addEventListener('change', function () {
      var selectedLang = String(select.value || defaultLang).trim().toLowerCase();
      if (langs.indexOf(selectedLang) === -1) {
        selectedLang = defaultLang;
      }

      var now = splitPath(window.location.pathname, rootPath, langs, defaultLang);
      var nextPath = buildPath(rootPath, now.rest, selectedLang, defaultLang);
      var nextUrl = nextPath + window.location.search + window.location.hash;
      var currentUrl = window.location.pathname + window.location.search + window.location.hash;
      if (nextUrl !== currentUrl) {
        window.location.assign(nextUrl);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
