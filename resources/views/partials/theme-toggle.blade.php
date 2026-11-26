{{-- زرار القمر/الشمس. بيتحط قبل </body>. $class اختياري (tt-admin في لوحة الأدمن). --}}
<button type="button" class="theme-toggle {{ $class ?? '' }}" data-theme-toggle aria-label="Toggle dark mode" title="Dark / Light mode">
  <i class="bi bi-moon-stars-fill"></i><i class="bi bi-sun-fill"></i>
</button>
<script src="{{ asset('assets/js/theme.js') }}"></script>
