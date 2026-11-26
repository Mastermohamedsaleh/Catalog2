{{-- شريط توصيل ثابت أعلى الصفحات العامة: الرسوم من config/shop.php (SHOP_DELIVERY_FEE) فلو اتغيرت بتتغير في كل حتة. --}}
<div class="delivery-bar text-center small fw-semibold py-2 px-3" style="background:var(--navy-deep,#050f35);color:#fff">
  <i class="bi bi-truck me-1" style="color:#ff8a1f"></i>
  Delivery: flat <span style="color:#ffb000">{{ number_format(config('shop.delivery_fee'), 0) }} {{ config('shop.currency') }}</span> per delivery,
  charged separately on top of the product price.
</div>
