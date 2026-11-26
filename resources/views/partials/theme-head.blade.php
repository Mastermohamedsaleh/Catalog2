{{-- الوضع الداكن: بيتحط في <head>. السكريبت الصغير بيحدد الثيم قبل ما الصفحة تترسم (من غير وميض أبيض).
     الأولوية: اختيار الزائر المحفوظ، وبعده إعداد جهازه (فاتح/داكن). --}}
<link href="{{ asset('assets/css/dark.css') }}" rel="stylesheet">
<script>
(function () {
  var t = 'light';
  try {
    t = localStorage.getItem('akssap-theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  } catch (e) {}
  document.documentElement.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light');
})();
</script>
