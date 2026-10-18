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

  // Home hero carousel: crossfades every few seconds, pauses on the controls/keyboard focus and while
  // the tab is hidden, and never auto-advances for visitors who prefer reduced motion.
  var hero = document.querySelector('[data-hero-carousel]');
  if (hero) {
    var slides = hero.querySelectorAll('[data-hero-slide]');
    var dots = hero.querySelectorAll('[data-hero-dot]');
    var interval = 6000;
    var current = 0, timer = null, paused = false;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    hero.style.setProperty('--hero-interval', interval + 'ms');

    var show = function (i) {
      current = (i + slides.length) % slides.length;
      slides.forEach(function (s, n) {
        s.classList.toggle('is-active', n === current);
        s.setAttribute('aria-hidden', n === current ? 'false' : 'true');
      });
      dots.forEach(function (d, n) {
        // Re-trigger the progress bar animation by toggling the class off first.
        d.classList.remove('is-active');
        d.removeAttribute('aria-current');
        if (n === current) {
          void d.offsetWidth;
          d.classList.add('is-active');
          d.setAttribute('aria-current', 'true');
        }
      });
    };
    var stop = function () { clearInterval(timer); timer = null; };
    var start = function () {
      stop();
      if (reduceMotion || paused || slides.length < 2) return;
      timer = setInterval(function () { show(current + 1); }, interval);
    };
    var go = function (i) { show(i); start(); };

    if (slides.length < 2) {
      hero.querySelector('.hero-controls').hidden = true;
    } else {
      hero.querySelector('[data-hero-prev]').addEventListener('click', function () { go(current - 1); });
      hero.querySelector('[data-hero-next]').addEventListener('click', function () { go(current + 1); });
      dots.forEach(function (d) {
        d.addEventListener('click', function () { go(parseInt(d.getAttribute('data-hero-dot'), 10)); });
      });
      var pause = function (on) {
        paused = on;
        hero.classList.toggle('is-paused', on);
        if (on) { stop(); } else { show(current); start(); }
      };
      // Only the controls pause on hover: the hero fills the screen, so pausing on the whole
      // of it would stop the carousel for anyone whose mouse happened to rest there.
      var controls = hero.querySelector('.hero-controls');
      controls.addEventListener('mouseenter', function () { pause(true); });
      controls.addEventListener('mouseleave', function () { pause(false); });
      hero.addEventListener('focusin', function () { pause(true); });
      hero.addEventListener('focusout', function (e) { if (!hero.contains(e.relatedTarget)) pause(false); });
      hero.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') go(current - 1);
        if (e.key === 'ArrowRight') go(current + 1);
      });
      document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
      if (reduceMotion) hero.classList.add('is-paused');
      show(0);
      start();
    }
  }

  // Impact counters: count up once, the first time they are seen. Without
  // IntersectionObserver the figure simply stands as rendered by the server.
  var counters = document.querySelectorAll('[data-count-to]');
  if (counters.length && 'IntersectionObserver' in window) {
    var countIo = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        var el = entry.target;
        countIo.unobserve(el);
        var target = parseInt(el.getAttribute('data-count-to'), 10) || 0;
        var suffix = el.getAttribute('data-count-suffix') || '';
        if (target <= 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
        var started = null, dur = 1400;
        var step = function (now) {
          if (started === null) { started = now; }
          var p = Math.min((now - started) / dur, 1);
          var eased = 1 - Math.pow(1 - p, 3);
          el.textContent = Math.round(target * eased).toLocaleString() + suffix;
          if (p < 1) { requestAnimationFrame(step); }
        };
        el.textContent = '0' + suffix;
        requestAnimationFrame(step);
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { countIo.observe(el); });
  }

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
