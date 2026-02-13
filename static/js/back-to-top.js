(() => {
  const BUTTON_SELECTOR = '[data-back-to-top]';
  const AI_FAB_SELECTOR = '.classic22-post-ai-fab';
  const SHOW_AT_TOP_THRESHOLD_PX = 0;

  function canScroll() {
    const doc = document.documentElement;
    const viewport = window.innerHeight || doc.clientHeight || 0;
    return doc.scrollHeight > viewport + 20;
  }

  function isNotAtTop() {
    const doc = document.documentElement;
    const scrollTop = window.pageYOffset || doc.scrollTop || 0;
    return scrollTop > SHOW_AT_TOP_THRESHOLD_PX;
  }

  function run() {
    const button = document.querySelector(BUTTON_SELECTOR);
    if (!button) {
      return;
    }

    if (document.querySelector(AI_FAB_SELECTOR)) {
      button.classList.add('classic22-back-to-top--offset');
    }

    const updateVisibility = () => {
      const show = canScroll() && isNotAtTop();
      button.hidden = !show;
    };

    button.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    let ticking = false;
    const requestUpdate = () => {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(() => {
        ticking = false;
        updateVisibility();
      });
    };

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
    document.addEventListener('classic22:postContentUpdated', requestUpdate);

    updateVisibility();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
