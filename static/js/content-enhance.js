(() => {
  const CONTENT_SELECTOR = '.entry-content.fmt';
  const GITHUB_REPO_PATTERN = /^https?:\/\/github\.com\/([^\/#?\s]+)\/([^\/#?\s]+)\/?$/i;
  const STAR_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.81 6.63L22 9.24l-5.46 4.73L18.18 21 12 17.27 5.82 21l1.64-7.03L2 9.24l7.19-.61L12 2Z"/></svg>';

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

  function run() {
    const contents = document.querySelectorAll(CONTENT_SELECTOR);
    if (!contents.length) {
      return;
    }

    contents.forEach((content) => {
      highlightCodeBlocks(content);
      transformGithubLinks(content);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  document.addEventListener('classic22:postContentUpdated', run);
})();
