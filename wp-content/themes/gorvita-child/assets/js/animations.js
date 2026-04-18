(function () {
  'use strict';

  const isMobile = () => window.innerWidth <= 768;
  const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function initParallax() {
    const heroBg = document.querySelector('.gorvita-hero .wp-block-cover__image-background');
    if (!heroBg || isMobile() || prefersReducedMotion()) return;
    let ticking = false;
    function update() {
      heroBg.style.transform = 'translateY(' + (window.scrollY * 0.3) + 'px) scale(1.05)';
      ticking = false;
    }
    window.addEventListener('scroll', function () {
      if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
  }

  function initStream() {
    const el = document.querySelector('.gorvita-stream-section');
    if (!el || prefersReducedMotion()) { if (el) el.classList.add('is-ready'); return; }
    const img = new Image();
    img.onload = () => el.classList.add('is-ready');
    img.onerror = () => el.classList.add('is-ready');
    img.src = el.dataset.bg || '';
    if (!el.dataset.bg) el.classList.add('is-ready');
  }

  function initFadeIn() {
    if (prefersReducedMotion()) {
      document.querySelectorAll('.fade-in-up').forEach(el => el.classList.add('visible'));
      return;
    }
    const els = document.querySelectorAll('.fade-in-up');
    if (!els.length) return;
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          const siblings = entry.target.parentElement.querySelectorAll('.fade-in-up');
          siblings.forEach(function (el, i) {
            if (!el.classList.contains('visible'))
              el.style.transitionDelay = (i * 0.1) + 's';
          });
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function (el) { observer.observe(el); });
  }

  function initHeaderScroll() {
    const header = document.querySelector('header, .site-header, #masthead');
    if (!header) return;
    let last = 0;
    window.addEventListener('scroll', function () {
      const cur = window.scrollY;
      header.classList.toggle('scrolled', cur > 80);
      if (isMobile()) header.classList.toggle('hidden', cur > last && cur > 200);
      last = cur;
    }, { passive: true });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initParallax();
    initStream();
    initFadeIn();
    initHeaderScroll();
  });
})();
