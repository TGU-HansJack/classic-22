(() => {
  const CONTENT_SELECTOR = '[data-post-content]';
  const TOC_SELECTOR = '[data-post-toc]';
  const TOC_LIST_SELECTOR = '[data-post-toc-list]';
  const HEADING_SELECTOR = 'h1, h2, h3, h4, h5, h6';

  function slugify(text, index) {
    const normalized = (text || '')
      .trim()
      .toLowerCase()
      .replace(/[\s\u3000]+/g, '-')
      .replace(/[^\w\-\u4e00-\u9fa5]/g, '')
      .replace(/-+/g, '-');

    if (normalized) {
      return `toc-${normalized}-${index + 1}`;
    }

    return `toc-heading-${index + 1}`;
  }

  function buildToc(content, toc, tocList) {
    const headings = Array.from(content.querySelectorAll(HEADING_SELECTOR));

    if (!headings.length) {
      toc.style.display = 'none';
      return;
    }

    const links = [];

    headings.forEach((heading, index) => {
      const text = (heading.textContent || '').trim();
      if (!text) {
        return;
      }

      if (!heading.id) {
        heading.id = slugify(text, index);
      }

      const level = Number(heading.tagName.slice(1));
      const item = document.createElement('li');
      item.className = 'classic22-post-toc-item';
      item.dataset.level = String(level);

      const link = document.createElement('a');
      link.className = 'classic22-post-toc-link';
      link.href = `#${heading.id}`;
      link.textContent = text;

      item.appendChild(link);
      tocList.appendChild(item);
      links.push({ heading, link });
    });

    if (!links.length) {
      toc.style.display = 'none';
      return;
    }

    function setActiveByScroll() {
      const anchorTop = 110;
      let activeIndex = 0;

      for (let index = 0; index < links.length; index += 1) {
        if (links[index].heading.getBoundingClientRect().top <= anchorTop) {
          activeIndex = index;
        } else {
          break;
        }
      }

      links.forEach((entry, index) => {
        entry.link.classList.toggle('is-active', index === activeIndex);
      });
    }

    setActiveByScroll();
    document.addEventListener('scroll', setActiveByScroll, { passive: true });

    tocList.addEventListener('click', (event) => {
      const link = event.target.closest('a.classic22-post-toc-link');
      if (!link) {
        return;
      }

      const hash = link.getAttribute('href');
      if (!hash || hash.charAt(0) !== '#') {
        return;
      }

      const target = document.getElementById(hash.slice(1));
      if (!target) {
        return;
      }

      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', hash);
      }
    });
  }

  function run() {
    const content = document.querySelector(CONTENT_SELECTOR);
    const toc = document.querySelector(TOC_SELECTOR);
    const tocList = document.querySelector(TOC_LIST_SELECTOR);

    if (!content || !toc || !tocList) {
      return;
    }

    buildToc(content, toc, tocList);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();

