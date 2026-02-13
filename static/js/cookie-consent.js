(() => {
  const BANNER_SELECTOR = '[data-cookie-banner]';
  const BOOTSTRAP_SELECTOR = '[data-cookie-consent-bootstrap]';
  const ACCEPT_SELECTOR = '[data-cookie-accept]';
  const REJECT_SELECTOR = '[data-cookie-reject]';

  const BODY_OPEN_CLASS = 'classic22-cookie-banner-open';
  const OFFSET_CSS_VAR = '--classic22-cookie-banner-offset';

  function safeJsonParse(text) {
    if (typeof text !== 'string') {
      return {};
    }

    try {
      const parsed = JSON.parse(text);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (e) {
      return {};
    }
  }

  function getCookie(name) {
    if (!name) {
      return '';
    }

    const cookies = document.cookie ? document.cookie.split(';') : [];
    for (const entry of cookies) {
      const [rawKey, ...rawValue] = entry.trim().split('=');
      if (!rawKey) continue;
      if (rawKey === name) {
        return decodeURIComponent(rawValue.join('='));
      }
    }

    return '';
  }

  function setCookie(name, value, maxAgeDays) {
    if (!name) {
      return;
    }

    const maxAge = Math.max(1, Math.floor(Number(maxAgeDays) || 180)) * 24 * 60 * 60;
    const encoded = encodeURIComponent(String(value || ''));
    document.cookie = `${name}=${encoded}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
  }

  function getStorage(key) {
    if (!key) {
      return '';
    }

    try {
      return window.localStorage.getItem(key) || '';
    } catch (e) {
      return '';
    }
  }

  function setStorage(key, value) {
    if (!key) {
      return;
    }

    try {
      window.localStorage.setItem(key, String(value || ''));
    } catch (e) {
    }
  }

  function setOffset(px) {
    const value = Number(px) > 0 ? `${Math.ceil(Number(px))}px` : '0px';
    document.documentElement.style.setProperty(OFFSET_CSS_VAR, value);
  }

  function showBanner(banner) {
    banner.hidden = false;
    document.body.classList.add(BODY_OPEN_CLASS);
    setOffset(banner.getBoundingClientRect().height || banner.offsetHeight || 0);
  }

  function hideBanner(banner) {
    banner.hidden = true;
    document.body.classList.remove(BODY_OPEN_CLASS);
    setOffset(0);
  }

  function run() {
    const banner = document.querySelector(BANNER_SELECTOR);
    if (!banner) {
      return;
    }

    const bootstrapNode = document.querySelector(BOOTSTRAP_SELECTOR);
    const bootstrap = safeJsonParse(bootstrapNode ? bootstrapNode.textContent : '');

    const cookieName = typeof bootstrap.cookieName === 'string' ? bootstrap.cookieName : 'classic22_cookie_consent';
    const storageKey = typeof bootstrap.storageKey === 'string' ? bootstrap.storageKey : 'classic22_cookie_consent';
    const maxAgeDays = Number(bootstrap.maxAgeDays) || 180;

    const stored = getCookie(cookieName) || getStorage(storageKey);
    if (stored) {
      hideBanner(banner);
      return;
    }

    const acceptBtn = banner.querySelector(ACCEPT_SELECTOR);
    const rejectBtn = banner.querySelector(REJECT_SELECTOR);

    const onChoice = (value) => {
      setCookie(cookieName, value, maxAgeDays);
      setStorage(storageKey, value);
      hideBanner(banner);
    };

    if (acceptBtn) {
      acceptBtn.addEventListener('click', () => onChoice('accepted'));
    }

    if (rejectBtn) {
      rejectBtn.addEventListener('click', () => onChoice('rejected'));
    }

    showBanner(banner);

    let ticking = false;
    const updateOffset = () => {
      if (banner.hidden) return;
      setOffset(banner.getBoundingClientRect().height || banner.offsetHeight || 0);
    };

    const requestUpdate = () => {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(() => {
        ticking = false;
        updateOffset();
      });
    };

    window.addEventListener('resize', requestUpdate);
    document.addEventListener('classic22:postContentUpdated', requestUpdate);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
