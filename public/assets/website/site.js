(function () {
  'use strict';

  // Header shadow once the page scrolls
  var header = document.querySelector('[data-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // Mobile menu drawer
  var drawer = document.querySelector('[data-mobile-nav]');
  var openBtn = document.querySelector('[data-menu-open]');
  var closeBtn = document.querySelector('[data-menu-close]');
  function openMenu() {
    drawer.hidden = false;
    openBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    if (closeBtn) closeBtn.focus();
  }
  function closeMenu() {
    drawer.hidden = true;
    openBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    openBtn.focus();
  }
  if (drawer && openBtn) {
    openBtn.addEventListener('click', openMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    drawer.addEventListener('click', function (e) { if (e.target === drawer) closeMenu(); });
  }

  // Gallery lightbox
  var lightbox = document.querySelector('[data-lightbox]');
  var lightboxImg = document.querySelector('[data-lightbox-img]');
  var lastTrigger = null;
  function closeLightbox() {
    lightbox.hidden = true;
    lightboxImg.removeAttribute('src');
    document.body.style.overflow = '';
    if (lastTrigger) lastTrigger.focus();
  }
  if (lightbox && lightboxImg) {
    document.querySelectorAll('[data-lightbox-src]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        lastTrigger = btn;
        lightboxImg.src = btn.getAttribute('data-lightbox-src');
        lightboxImg.alt = btn.getAttribute('aria-label') || '';
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
        lightbox.querySelector('[data-lightbox-close]').focus();
      });
    });
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox || e.target.closest('[data-lightbox-close]')) closeLightbox();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (lightbox && !lightbox.hidden) closeLightbox();
    else if (drawer && !drawer.hidden) closeMenu();
  });

  // Reveal-on-scroll; everything is simply shown if IntersectionObserver is missing
  var revealEls = document.querySelectorAll('[data-reveal]');
  if (!('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      }
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
  revealEls.forEach(function (el) { io.observe(el); });
})();
