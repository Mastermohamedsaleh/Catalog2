/* زرار الوضع الداكن. الاختيار بيتحفظ في المتصفح (try/catch عشان بعض المتصفحات بتمنع التخزين). */
(function () {
  var KEY = 'akssap-theme';
  function current() { return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light'; }
  function apply(t) { document.documentElement.setAttribute('data-bs-theme', t); }
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    var next = current() === 'dark' ? 'light' : 'dark';
    apply(next);
    try { localStorage.setItem(KEY, next); } catch (err) {}
    btn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
  });
})();
