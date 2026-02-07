(() => {
  const ROTATOR_SELECTOR = '[data-announcement-rotator]';
  const ACTIVE_CLASS = 'is-active';
  const EXIT_CLASS = 'is-exit';
  const READY_CLASS = 'is-ready';

  function prefersReducedMotion() {
    return (
      typeof window !== 'undefined' &&
      window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
  }

  function initRotator(rotator) {
    const items = Array.from(rotator.querySelectorAll('.classic22-announcement-item'));
    if (!items.length) return;

    items.forEach((item, index) => {
      item.classList.toggle(ACTIVE_CLASS, index === 0);
      item.classList.remove(EXIT_CLASS);
    });

    if (items.length < 2 || prefersReducedMotion()) {
      return;
    }

    rotator.classList.add(READY_CLASS);

    const interval = Number(rotator.getAttribute('data-interval')) || 3000;
    const transitionMs = 320;
    let index = 0;
    let timer = null;

    function tick() {
      const current = items[index];
      const nextIndex = (index + 1) % items.length;
      const next = items[nextIndex];

      current.classList.remove(ACTIVE_CLASS);
      current.classList.add(EXIT_CLASS);

      next.classList.add(ACTIVE_CLASS);
      next.classList.remove(EXIT_CLASS);

      window.setTimeout(() => {
        current.classList.remove(EXIT_CLASS);
      }, transitionMs);

      index = nextIndex;
    }

    function start() {
      if (timer) return;
      timer = window.setInterval(tick, interval);
    }

    function stop() {
      if (!timer) return;
      window.clearInterval(timer);
      timer = null;
    }

    start();

    rotator.addEventListener('mouseenter', stop);
    rotator.addEventListener('mouseleave', start);
    rotator.addEventListener('focusin', stop);
    rotator.addEventListener('focusout', () => {
      if (!rotator.contains(document.activeElement)) {
        start();
      }
    });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stop();
      } else {
        start();
      }
    });
  }

  function run() {
    document.querySelectorAll(ROTATOR_SELECTOR).forEach(initRotator);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
