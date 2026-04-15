(function () {
  'use strict';

  var sidebar = document.getElementById('pitchSidebar');
  var sidebarToggle = document.getElementById('sidebarToggle');
  var sidebarClose = document.getElementById('sidebarClose');
  var sidebarOverlay = document.getElementById('sidebarOverlay');
  var mobileFab = document.getElementById('mobileSidebarFab');
  var backToTop = document.getElementById('backToTop');
  var progress = document.getElementById('readingProgress');
  var sidebarProgress = document.getElementById('sidebarProgressBar');
  var navLabel = document.getElementById('navSectionLabel');
  var themeToggle = document.getElementById('themeToggle');
  var pitchContent = document.getElementById('pitchContent');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('pitch-sidebar--open');
    if (sidebarOverlay) sidebarOverlay.classList.add('active');
    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
    if (mobileFab) mobileFab.setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('pitch-sidebar--open');
    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
    if (mobileFab) mobileFab.setAttribute('aria-expanded', 'false');
  }

  if (sidebarToggle) sidebarToggle.addEventListener('click', function () {
    sidebar.classList.contains('pitch-sidebar--open') ? closeSidebar() : openSidebar();
  });
  if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
  if (mobileFab) mobileFab.addEventListener('click', function () {
    sidebar.classList.contains('pitch-sidebar--open') ? closeSidebar() : openSidebar();
  });

  if (themeToggle) {
    var stored = localStorage.getItem('tannus-theme');
    if (stored) document.documentElement.setAttribute('data-theme', stored);

    themeToggle.addEventListener('click', function () {
      var current = document.documentElement.getAttribute('data-theme');
      var next = current === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('tannus-theme', next);
    });
  }

  var headings = [];
  function collectHeadings() {
    headings = [];
    var els = (pitchContent || document).querySelectorAll('h2[id], h3[id], h4[id]');
    els.forEach(function (h) { headings.push({ el: h, id: h.id, text: h.textContent.trim() }); });
  }

  function updateProgress() {
    var scrollTop = window.scrollY || document.documentElement.scrollTop;
    var docHeight = document.documentElement.scrollHeight - window.innerHeight;
    var pct = docHeight > 0 ? Math.min(100, Math.round((scrollTop / docHeight) * 100)) : 0;
    if (progress) { progress.style.width = pct + '%'; progress.setAttribute('aria-valuenow', String(pct)); }
    if (sidebarProgress) sidebarProgress.style.width = pct + '%';
  }

  function updateActiveSection() {
    if (headings.length === 0) return;
    var current = null;
    for (var i = headings.length - 1; i >= 0; i--) {
      var rect = headings[i].el.getBoundingClientRect();
      if (rect.top <= 120) { current = headings[i]; break; }
    }
    document.querySelectorAll('.pitch-toc__item').forEach(function (li) {
      var isActive = current && li.getAttribute('data-toc-id') === current.id;
      li.classList.toggle('pitch-toc__item--active', isActive);
    });
    if (navLabel && current) navLabel.textContent = current.text;
  }

  if (backToTop) {
    window.addEventListener('scroll', function () {
      backToTop.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  var tocLinks = document.querySelectorAll('.pitch-toc__link');
  tocLinks.forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      var target = document.getElementById(a.getAttribute('href').slice(1));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (window.innerWidth < 1024) closeSidebar();
      }
    });
  });

  var ticking = false;
  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(function () { updateProgress(); updateActiveSection(); ticking = false; });
      ticking = true;
    }
  }, { passive: true });

  collectHeadings();
  updateProgress();
  updateActiveSection();

  var phaseCards = document.querySelectorAll('.pitch-phase-card');
  if (phaseCards.length > 0 && 'IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('pitch-phase-card--visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    phaseCards.forEach(function (card) { obs.observe(card); });
  }

  var metricCards = document.querySelectorAll('.pitch-metric-card');
  if (metricCards.length > 0 && 'IntersectionObserver' in window) {
    var mobs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('pitch-metric-card--visible');
          mobs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    metricCards.forEach(function (card) { mobs.observe(card); });
  }
})();
