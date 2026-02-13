(() => {
  const CONTENT_SELECTOR = '.entry-content.fmt';
  const GITHUB_REPO_PATTERN = /^https?:\/\/github\.com\/([^\/#?\s]+)\/([^\/#?\s]+)\/?$/i;
  const STAR_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.81 6.63L22 9.24l-5.46 4.73L18.18 21 12 17.27 5.82 21l1.64-7.03L2 9.24l7.19-.61L12 2Z"/></svg>';
  const IMAGE_LINK_PATTERN = /\.(?:avif|bmp|gif|jpe?g|png|svg|webp)(?:$|[?#])/i;
  const CLASSIC22_FANCYBOX_GALLERY_ATTR = 'data-classic22-fancybox-gallery';
  const CLASSIC22_FANCYBOX_BOUND_FLAG = '__classic22FancyboxBound';

  function highlightCodeBlocks(root) {
    if (!window.hljs) {
      return;
    }

    const codeBlocks = root.querySelectorAll('pre code');
    codeBlocks.forEach((block) => {
      window.hljs.highlightElement(block);
    });
  }

  function isStandaloneGithubLink(paragraph) {
    if (!paragraph || paragraph.tagName !== 'P') {
      return false;
    }

    const text = paragraph.textContent ? paragraph.textContent.trim() : '';
    const links = paragraph.querySelectorAll('a[href]');
    if (links.length !== 1) {
      return false;
    }

    const link = links[0];
    const href = (link.getAttribute('href') || '').trim();
    if (!GITHUB_REPO_PATTERN.test(href)) {
      return false;
    }

    const sanitized = paragraph.innerHTML
      .replace(/<a[^>]*>[\s\S]*?<\/a>/i, '')
      .replace(/<br\s*\/?>(\s*)/gi, '')
      .replace(/&nbsp;/gi, '')
      .trim();

    if (sanitized !== '' || text === '') {
      return false;
    }

    return true;
  }

  function createMetaItem(value, extraClass = '', iconSvg = '') {
    const item = document.createElement('span');
    item.className = `classic22-github-card-meta-item${extraClass ? ` ${extraClass}` : ''}`;

    if (iconSvg) {
      const iconNode = document.createElement('span');
      iconNode.className = 'classic22-github-card-meta-icon';
      iconNode.setAttribute('aria-hidden', 'true');
      iconNode.innerHTML = iconSvg;
      item.appendChild(iconNode);
    }

    const valueNode = document.createElement('span');
    valueNode.className = 'classic22-github-card-meta-value';
    valueNode.textContent = value;
    item.appendChild(valueNode);

    return item;
  }

  function formatStarCount(count) {
    const number = Number(count) || 0;
    if (number >= 1000) {
      return `${(number / 1000).toFixed(1).replace(/\.0$/, '')}k`;
    }

    return String(number);
  }

  function getGithubIconUrl() {
    const script = document.currentScript
      || document.querySelector('script[src*="/static/js/content-enhance.js"]')
      || document.querySelector('script[src*="static/js/content-enhance.js"]');

    if (!script || !script.src) {
      return './static/img/github.svg';
    }

    return new URL('../img/github.svg', script.src).toString();
  }

  function buildGithubCard(link) {
    const href = (link.getAttribute('href') || '').trim();
    const match = href.match(GITHUB_REPO_PATTERN);
    if (!match) {
      return null;
    }

    const owner = match[1];
    const repo = match[2].replace(/\.git$/i, '');
    const apiUrl = `https://api.github.com/repos/${owner}/${repo}`;

    const card = document.createElement('a');
    card.className = 'classic22-github-card';
    card.href = href;
    card.rel = 'noopener noreferrer';

    const icon = document.createElement('span');
    icon.className = 'classic22-github-card-icon';
    icon.setAttribute('aria-hidden', 'true');

    const iconImage = document.createElement('img');
    iconImage.src = getGithubIconUrl();
    iconImage.alt = '';
    iconImage.width = 24;
    iconImage.height = 24;
    iconImage.decoding = 'async';
    iconImage.loading = 'lazy';
    icon.appendChild(iconImage);

    const body = document.createElement('span');
    body.className = 'classic22-github-card-body';

    const title = document.createElement('span');
    title.className = 'classic22-github-card-title';
    title.textContent = `${owner}/${repo}`;

    const desc = document.createElement('span');
    desc.className = 'classic22-github-card-desc';
    desc.textContent = 'GitHub Repository';

    const meta = document.createElement('span');
    meta.className = 'classic22-github-card-meta';

    body.appendChild(title);
    body.appendChild(desc);

    card.appendChild(icon);
    card.appendChild(body);
    card.appendChild(meta);

    fetch(apiUrl, {
      headers: {
        Accept: 'application/vnd.github+json'
      }
    })
      .then((response) => {
        if (!response.ok) {
          return null;
        }

        return response.json();
      })
      .then((data) => {
        if (!data) {
          return;
        }

        if (data.description) {
          desc.textContent = data.description;
        }

        meta.innerHTML = '';
        meta.appendChild(createMetaItem(formatStarCount(data.stargazers_count), 'is-star', STAR_ICON_SVG));
      })
      .catch(() => {
      });

    return card;
  }

  function transformGithubLinks(root) {
    const paragraphs = Array.from(root.querySelectorAll('p'));

    paragraphs.forEach((paragraph) => {
      if (!isStandaloneGithubLink(paragraph)) {
        return;
      }

      const link = paragraph.querySelector('a[href]');
      if (!link) {
        return;
      }

      const card = buildGithubCard(link);
      if (!card) {
        return;
      }

      const wrapper = document.createElement('div');
      wrapper.className = 'classic22-github-card-wrap';
      wrapper.appendChild(card);

      paragraph.replaceWith(wrapper);
    });
  }

  function getFancybox() {
    const fancybox = window.Fancybox;
    if (!fancybox || typeof fancybox.bind !== 'function') {
      return null;
    }

    return fancybox;
  }

  function ensureFancyboxBound(fancybox) {
    fancybox = fancybox || getFancybox();
    if (!fancybox) {
      return;
    }

    if (window[CLASSIC22_FANCYBOX_BOUND_FLAG]) {
      return;
    }

    window[CLASSIC22_FANCYBOX_BOUND_FLAG] = true;
    fancybox.bind('[data-fancybox^="classic22-content-"]', {});
  }

  function isImageHref(href) {
    const normalized = typeof href === 'string' ? href.trim() : '';
    return normalized !== '' && IMAGE_LINK_PATTERN.test(normalized);
  }

  function firstUsableUrl(...candidates) {
    for (const candidate of candidates) {
      if (typeof candidate !== 'string') {
        continue;
      }

      const normalized = candidate.trim();
      if (!normalized) {
        continue;
      }

      if (normalized.startsWith('data:') || normalized === 'about:blank') {
        continue;
      }

      return normalized;
    }

    return '';
  }

  function pickUrlFromSrcset(srcset) {
    if (typeof srcset !== 'string') {
      return '';
    }

    const items = srcset
      .split(',')
      .map((part) => part.trim())
      .filter(Boolean);

    if (!items.length) {
      return '';
    }

    const last = items[items.length - 1];
    const url = last.split(/\s+/)[0];
    return typeof url === 'string' ? url.trim() : '';
  }

  function resolveImageSource(img) {
    if (!img || img.tagName !== 'IMG') {
      return '';
    }

    const currentSrc = typeof img.currentSrc === 'string' ? img.currentSrc : '';
    const src = typeof img.src === 'string' ? img.src : '';
    const attrSrc = img.getAttribute('src') || '';
    const dataSrc = img.getAttribute('data-src') || '';
    const dataOriginal = img.getAttribute('data-original') || '';
    const dataLazy = img.getAttribute('data-lazy-src') || '';
    const dataLazySrc = img.dataset && typeof img.dataset.lazySrc === 'string' ? img.dataset.lazySrc : '';
    const srcset = img.getAttribute('srcset') || '';
    const dataSrcset = img.getAttribute('data-srcset') || '';

    return firstUsableUrl(
      currentSrc,
      src,
      attrSrc,
      dataOriginal,
      dataLazy,
      dataLazySrc,
      dataSrc,
      pickUrlFromSrcset(srcset),
      pickUrlFromSrcset(dataSrcset)
    );
  }

  function resolveImageCaption(img) {
    if (!img || img.tagName !== 'IMG') {
      return '';
    }

    const figure = img.closest('figure');
    if (figure) {
      const caption = figure.querySelector('figcaption');
      const text = caption && caption.textContent ? caption.textContent.trim() : '';
      if (text) {
        return text;
      }
    }

    const alt = (img.getAttribute('alt') || '').trim();
    if (alt) {
      return alt;
    }

    const title = (img.getAttribute('title') || '').trim();
    if (title) {
      return title;
    }

    return '';
  }

  function wrapNodeWithFancybox(target, href, gallery, caption) {
    if (!target || !target.parentNode) {
      return;
    }

    const link = document.createElement('a');
    link.href = href;
    link.className = 'classic22-fancybox-link';
    link.setAttribute('data-fancybox', gallery);
    link.setAttribute('data-src', href);
    link.setAttribute('data-type', 'image');
    if (caption) {
      link.setAttribute('data-caption', caption);
    }

    target.parentNode.insertBefore(link, target);
    link.appendChild(target);
  }

  function toAbsoluteUrl(url) {
    const normalized = typeof url === 'string' ? url.trim() : '';
    if (!normalized) {
      return '';
    }

    try {
      return new URL(normalized, document.baseURI).toString();
    } catch (e) {
      return normalized;
    }
  }

  function enhanceImagesWithFancybox(root, contentIndex) {
    const fancybox = getFancybox();
    if (!fancybox) {
      return;
    }

    if (!root) {
      return;
    }

    ensureFancyboxBound(fancybox);

    const storedGallery = root.getAttribute(CLASSIC22_FANCYBOX_GALLERY_ATTR);
    const gallery = storedGallery || `classic22-content-${contentIndex + 1}`;
    if (!storedGallery) {
      root.setAttribute(CLASSIC22_FANCYBOX_GALLERY_ATTR, gallery);
    }

    const images = Array.from(root.querySelectorAll('img'));
    images.forEach((img) => {
      if (img.closest('a[data-fancybox]')) {
        return;
      }

      if (img.closest('[data-no-fancybox]')) {
        return;
      }

      if (img.closest('pre, code')) {
        return;
      }

      const parentLink = img.closest('a[href]');
      if (parentLink) {
        const href = (parentLink.getAttribute('href') || '').trim();
        const imgHref = resolveImageSource(img);
        const isSameAsImage = imgHref
          ? toAbsoluteUrl(href) === toAbsoluteUrl(imgHref)
          : false;

        if (!isImageHref(href) && !isSameAsImage) {
          return;
        }

        parentLink.classList.add('classic22-fancybox-link');
        parentLink.setAttribute('data-fancybox', gallery);
        if (!parentLink.getAttribute('data-src') && imgHref) {
          parentLink.setAttribute('data-src', imgHref);
        }
        parentLink.setAttribute('data-type', 'image');

        if (!parentLink.getAttribute('data-caption')) {
          const caption = resolveImageCaption(img);
          if (caption) {
            parentLink.setAttribute('data-caption', caption);
          }
        }

        return;
      }

      const href = resolveImageSource(img);
      if (!href) {
        return;
      }

      const caption = resolveImageCaption(img);
      const target = img.parentElement && img.parentElement.tagName === 'PICTURE'
        ? img.parentElement
        : img;
      wrapNodeWithFancybox(target, href, gallery, caption);
    });
  }

  function run() {
    const contents = document.querySelectorAll(CONTENT_SELECTOR);
    if (!contents.length) {
      return;
    }

    contents.forEach((content, contentIndex) => {
      highlightCodeBlocks(content);
      transformGithubLinks(content);
      enhanceImagesWithFancybox(content, contentIndex);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  document.addEventListener('classic22:postContentUpdated', run);
})();
