(function () {
  'use strict';

  var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function animateCounter(el) {
    var raw = el.getAttribute('data-target');
    if (!raw) return;
    var target = parseInt(raw, 10);
    if (isNaN(target)) { el.textContent = raw; return; }
    if (prefersReduced) { el.textContent = target.toLocaleString('pt-BR'); return; }
    var duration = 1800;
    var start = null;
    var suffix = el.getAttribute('data-suffix') || '';
    function step(timestamp) {
      if (!start) start = timestamp;
      var progress = Math.min((timestamp - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var current = Math.round(eased * target);
      el.textContent = current.toLocaleString('pt-BR');
      if (progress < 1) requestAnimationFrame(step);
      else if (suffix) el.textContent = target.toLocaleString('pt-BR');
    }
    requestAnimationFrame(step);
  }

  var counters = document.querySelectorAll('[data-counter]');
  if (counters.length && 'IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });
    counters.forEach(function (el) { observer.observe(el); });
  } else {
    counters.forEach(function (el) { animateCounter(el); });
  }

  var saved = localStorage.getItem('tannus-theme');
  if (saved) document.documentElement.setAttribute('data-theme', saved);

  var toggle = document.getElementById('themeToggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var current = document.documentElement.getAttribute('data-theme');
      var next = current === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('tannus-theme', next);
    });
  }
})();
