<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AksSap – Affiliate marketing for the UAE market</title>
  <meta name="description" content="AksSap is an affiliate marketing platform for the UAE market: pick premium products, set your own commission and get paid 3 times a month.">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
  <link href="{{ asset('assets/css/brand.css') }}" rel="stylesheet">
  
  <!-- ربط ملف الـ CSS الخاص بالمشروع بالطريقة الصحيحة في لارافيل -->
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body>
<div id="toastTop" aria-live="polite"></div>

<!-- ============ Navbar ============ -->
<nav class="navbar navbar-expand-lg site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold brand-ink" href="{{ url('/') }}"><img src="{{ asset('assets/img/logo.png') }}" alt="AksSap logo" class="brand-logo"><span>Aks<span class="brand-accent">Sap</span></span></a>
    <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto ms-lg-3">
        <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About us</a></li>
        <li class="nav-item"><a class="nav-link" href="#services">Why AksSap</a></li>
        <li class="nav-item"><a class="nav-link" href="#categories">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact us</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('merchant.orders.index') }}">Create New Order</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
        <a href="tel:+{{ config('shop.phone') }}" class="btn btn-light btn-sm d-none d-xl-inline-flex gap-1"><i class="bi bi-telephone-fill text-primary"></i>{{ \App\Support\Phone::display(config('shop.phone')) }}</a>
        @auth
    @if(Auth::user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">My Dashboard</a>
    @else
        <a href="{{ route('merchant.orders.index') }}" class="btn btn-primary">My Dashboard</a>
    @endif
@else
   <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign in</a>
     <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
@endauth
      </div>
    </div>
  </div>
</nav>

<main>
  <!-- ============ HERO ============ -->
  <section id="home" class="container pt-4 pt-lg-5">
    <div class="hero hero-lg">
      <div class="row align-items-center g-4 position-relative" style="z-index:1">
        <div class="col-lg-7">
          <span class="badge rounded-pill text-bg-light text-primary mb-3 px-3 py-2"><i class="bi bi-patch-check-fill me-1"></i>Affiliate marketing for the UAE market</span>
          <h1 class="display-4 fw-bold mb-3">Premium products. Unbeatable prices. You set your profit.</h1>
          <p class="lead opacity-75 mb-4">Sell high-quality watches, clothing, shoes, home items and more to UAE customers. Choose your products, set your commission, and get paid 3 times a month.</p>
          <div class="d-flex flex-wrap gap-2">
            <a href="#categories" class="btn btn-light btn-lg fw-semibold px-4">Browse products</a>
            <a href="https://wa.me/{{ config('shop.whatsapp') }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-lg px-4"><i class="bi bi-whatsapp me-2"></i>Chat on WhatsApp</a>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="row g-3">
            <div class="col-6"><div class="hero-stat"><div class="h2 fw-bold mb-0">3x</div><div class="small opacity-75">Payouts every month</div></div></div>
            <div class="col-6"><div class="hero-stat"><div class="h2 fw-bold mb-0">Next day</div><div class="small opacity-75">Delivery to your customer</div></div></div>
            <div class="col-6"><div class="hero-stat"><div class="h2 fw-bold mb-0">Your own</div><div class="small opacity-75">Commission margin</div></div></div>
            <div class="col-6"><div class="hero-stat"><div class="h2 fw-bold mb-0">Course</div><div class="small opacity-75">Step-by-step training</div></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ ABOUT ============ -->
  <section id="about" class="container sec">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6">
        <div class="about-visual ph g1"><i class="bi bi-buildings"></i>
          <div class="about-badge"><span class="fw-bold text-primary">Built for Egyptian marketers</span><br><span class="small text-muted">Selling to the UAE market</span></div>
        </div>
      </div>
      <div class="col-lg-6">
        <span class="eyebrow">Who we are</span>
        <h2 class="fw-bold mb-3">Helping young marketers build real brands in the UAE</h2>
        <p class="text-muted">AksSap is an affiliate marketing platform for the UAE market. We offer high-quality products across many categories at prices you won't find elsewhere, so you can focus on marketing and building your brand.</p>
        <p class="text-muted mb-4">Whether you're a student, an employee, or already working in marketing, we give you the products, training and support to start earning on the side.</p>
        <div class="row g-3">
          <div class="col-sm-6 d-flex gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="small fw-semibold">High-quality, carefully selected products</span></div>
          <div class="col-sm-6 d-flex gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="small fw-semibold">Prices no one else in the UAE matches</span></div>
          <div class="col-sm-6 d-flex gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="small fw-semibold">Supervisors who guide you step by step</span></div>
          <div class="col-sm-6 d-flex gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="small fw-semibold">You set your own commission</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ WHAT WE OFFER ============ -->
  <section id="services" class="container sec pt-0">
    <div class="text-center mb-5">
      <span class="eyebrow">What we offer</span>
      <h2 class="fw-bold mb-2">Everything you need to start and grow, in one place.</h2>
    </div>
    <div class="row g-3 g-lg-4 row-cols-1 row-cols-md-2 row-cols-lg-3">
      <div class="col"><div class="feature-card"><div class="feature-icon"><i class="bi bi-box-seam"></i></div><h3 class="h5 fw-bold">Wide product range</h3><p class="text-muted small mb-0">Watches, clothing, shoes, home items and more, chosen for what the market actually wants.</p></div></div>
      <div class="col"><div class="feature-card"><div class="feature-icon"><i class="bi bi-cash-coin"></i></div><h3 class="h5 fw-bold">Set your own commission</h3><p class="text-muted small mb-0">Add any amount on top of the product price. We tell you the best selling price for each product.</p></div></div>
      <div class="col"><div class="feature-card"><div class="feature-icon"><i class="bi bi-truck"></i></div><h3 class="h5 fw-bold">Next-day delivery</h3><p class="text-muted small mb-0">Our shipping partner delivers your customer's order the day after it's placed.</p></div></div>
      <div class="col"><div class="feature-card"><div class="feature-icon"><i class="bi bi-stars"></i></div><h3 class="h5 fw-bold">Product data &amp; AI designs</h3><p class="text-muted small mb-0">Get product data, and learn to find more products and generate designs with AI.</p></div></div>
      <div class="col"><div class="feature-card"><div class="feature-icon"><i class="bi bi-calendar-check"></i></div><h3 class="h5 fw-bold">Paid 3 times a month</h3><p class="text-muted small mb-0">Receive your commission in EGP on the 1st, 10th and 20th.</p></div></div>
      <div class="col"><div class="feature-card"><div class="feature-icon"><i class="bi bi-mortarboard"></i></div><h3 class="h5 fw-bold">Supervisors &amp; training</h3><p class="text-muted small mb-0">Your own account, supervisors to help you build your brand, and a recorded course from store setup to ads.</p></div></div>
    </div>
  </section>

  <!-- ============ CATEGORIES (Dynamic from Database - Max 8) ============ -->
  <section id="categories" class="container sec pt-0">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
      <div>
        <span class="eyebrow">Products</span>
        <h2 class="fw-bold mb-1">Shop by category</h2>
        <p class="text-muted mb-0">Pick a category to see its products.</p>
      </div>
      <!-- زرار الانتقال لصفحة عرض كل الأقسام -->
      <a href="{{ route('shop.categories.index') }}" class="btn btn-outline-primary">View all categories <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-xl-4">
      @forelse($categories as $index => $c)
          @php
              $colors = ['g1', 'g2', 'g3', 'g4', 'g5', 'g6'];
              $colorClass = $colors[$index % count($colors)];
          @endphp
          <div class="col">
              <a href="{{ route('shop.category', $c->slug) }}" class="cat-card">
                  @if($c->image)
                      <!-- عرض صورة القسم الحقيقية لو مرفوعة -->
                      <div class="position-relative overflow-hidden" style="height: 130px;">
                          <img src="{{ asset('storage/' . $c->image) }}" alt="{{ $c->name }}" class="w-100 h-100" style="object-fit: cover;">
                      </div>
                  @else
                      <!-- التصميم الافتراضي (Gradient Placeholder) لو مفيش صورة -->
                      <div class="ph ph-cat {{ $colorClass }}">
                          <i class="bi bi-grid-fill"></i>
                      </div>
                  @endif

                  <div class="p-3">
                      <h3 class="h6 fw-bold mb-1">{{ $c->name }}</h3>
                      <p class="small text-muted mb-0">{{ $c->products_count }} products</p>
                  </div>
              </a>
          </div>
      @empty
          <div class="col-12 text-center py-4">
              <p class="text-muted">لا توجد أقسام مضافة حالياً في قاعدة البيانات.</p>
          </div>
      @endforelse
    </div>
  </section>

  <!-- ============ HOW IT WORKS ============ -->
  <section class="container sec pt-0">
    <div class="text-center mb-5"><span class="eyebrow">How it works</span><h2 class="fw-bold">Start earning in 3 easy steps</h2></div>
    <div class="row g-4">
      <div class="col-md-4"><div class="feature-card"><div class="step-n">1</div><h3 class="h5 fw-bold">Register</h3><p class="text-muted small mb-0">Create your account and meet your supervisor.</p></div></div>
      <div class="col-md-4"><div class="feature-card"><div class="step-n">2</div><h3 class="h5 fw-bold">Market</h3><p class="text-muted small mb-0">Pick your products, build your brand and promote on social media.</p></div></div>
      <div class="col-md-4"><div class="feature-card"><div class="step-n">3</div><h3 class="h5 fw-bold">Order &amp; earn</h3><p class="text-muted small mb-0">Upload your customers' orders on the website. We ship next day and pay your commission.</p></div></div>
    </div>
  </section>

  <!-- ============ FAQ (بدل الآراء الوهمية: معلومات مؤكدة بس) ============ -->
  <section class="container sec pt-0">
    <div class="text-center mb-5"><span class="eyebrow">FAQ</span><h2 class="fw-bold">Common questions</h2></div>
    <div class="accordion mx-auto" id="faq" style="max-width:820px">
      @foreach([
        ['Do I need experience?', 'No. The course takes you from zero.'],
        ['How do I get paid?', 'In EGP, 3 times a month: the 1st, 10th and 20th.'],
        ['Who handles shipping?', 'Our shipping partner, delivering the next day.'],
        ['Who sets my selling price?', 'You add your margin, and we recommend the best price for each product.'],
      ] as $i => [$q, $a])
        <div class="accordion-item border-0 shadow-sm rounded-3 mb-2 overflow-hidden">
          <h3 class="accordion-header"><button class="accordion-button {{ $i ? 'collapsed' : '' }} fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}">{{ $q }}</button></h3>
          <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#faq"><div class="accordion-body text-muted">{{ $a }}</div></div>
        </div>
      @endforeach
    </div>
  </section>

  <!-- ============ CONTACT & LOCATION ============ -->
  <section id="contact" class="container sec pt-0">
    <div class="text-center mb-5"><span class="eyebrow">Contact us</span><h2 class="fw-bold mb-2">Get in touch</h2><p class="text-muted mb-0">Questions? Our team is here to help.</p></div>
    <div class="row g-4">
      <div class="col-lg-5 d-flex flex-column gap-3">
        <a class="contact-item" href="tel:+{{ config('shop.phone') }}"><div class="feature-icon"><i class="bi bi-telephone-fill"></i></div><div><div class="fw-bold">Phone</div><div class="text-muted small">{{ \App\Support\Phone::display(config('shop.phone')) }}</div></div></a>
        <a class="contact-item" href="https://wa.me/{{ config('shop.whatsapp') }}" target="_blank" rel="noopener"><div class="feature-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-whatsapp"></i></div><div><div class="fw-bold">WhatsApp</div><div class="text-muted small">Chat with us instantly</div></div></a>
        <a class="contact-item" href="mailto:{{ config('shop.email') }}"><div class="feature-icon"><i class="bi bi-envelope-fill"></i></div><div><div class="fw-bold">Email</div><div class="text-muted small">{{ config('shop.email') }}</div></div></a>
        <div class="contact-item"><div class="feature-icon"><i class="bi bi-geo-alt-fill"></i></div><div><div class="fw-bold">Address</div><div class="text-muted small">{{ config('shop.address') }}</div></div></div>
        <div class="contact-item"><div class="feature-icon"><i class="bi bi-clock-fill"></i></div><div><div class="fw-bold">Working hours</div><div class="text-muted small">{!! nl2br(e(config('shop.hours'))) !!}</div></div></div>
      </div>
      <div class="col-lg-7">
        <div class="row g-4">
          <div class="col-12" style="min-height:300px">
            <iframe class="map-frame shadow-sm" title="Our location" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              src="https://maps.google.com/maps?q={{ rawurlencode(config('shop.map_query')) }}&output=embed"></iframe>
          </div>
          <div class="col-12">
            {{-- الفورم ده بيفتح واتساب برسالة جاهزة (القديم كان بيظهر "تم الإرسال" من غير ما يبعت حاجة فعلياً) --}}
            <form class="card-soft p-3 p-lg-4" id="waForm" data-wa="{{ config('shop.whatsapp') }}">
              <h3 class="h5 fw-bold mb-3">Send us a message</h3>
              <div class="row g-3">
                <div class="col-sm-6"><label class="form-label small fw-semibold" for="cName">Name</label><input class="form-control" id="cName" required minlength="2" placeholder="Your name"></div>
                <div class="col-sm-6"><label class="form-label small fw-semibold" for="cPhone">Phone</label><input class="form-control" id="cPhone" type="tel" placeholder="+20 1X XXXX XXXX"></div>
                <div class="col-12"><label class="form-label small fw-semibold" for="cMsg">Message</label><textarea class="form-control" id="cMsg" rows="3" required placeholder="How can we help?"></textarea></div>
                <div class="col-12"><button class="btn btn-primary px-4" type="submit"><i class="bi bi-whatsapp me-2"></i>Send on WhatsApp</button></div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- ============ Footer ============ -->
<footer class="site-footer pt-5 pb-4">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-4"><div class="fs-5 fw-bold text-white mb-2"><img src="{{ asset('assets/img/logo.png') }}" alt="AksSap logo" class="brand-logo"><span>Aks<span class="brand-accent">Sap</span></span></div><p class="small mb-0">Affiliate marketing platform for the UAE market: premium products, your own commission, paid 3 times a month.</p></div>
      <div class="col-6 col-lg-2"><div class="text-white fw-semibold mb-2">Links</div><ul class="list-unstyled small mb-0"><li><a class="text-reset" href="#about">About us</a></li><li><a class="text-reset" href="#services">Why AksSap</a></li><li><a class="text-reset" href="#categories">Products</a></li><li><a class="text-reset" href="#contact">Contact us</a></li></ul></div>
      <div class="col-6 col-lg-3"><div class="text-white fw-semibold mb-2">Contact</div><ul class="list-unstyled small mb-0"><li><i class="bi bi-telephone me-2"></i>{{ \App\Support\Phone::display(config('shop.phone')) }}</li><li><i class="bi bi-envelope me-2"></i>{{ config('shop.email') }}</li><li><i class="bi bi-geo-alt me-2"></i>{{ config('shop.city') }}</li></ul></div>
      <div class="col-lg-3"><div class="text-white fw-semibold mb-2">Chat with us</div><div class="d-flex gap-3 fs-5"><a class="text-reset" href="https://wa.me/{{ config('shop.whatsapp') }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a></div></div>
    </div>
  </div>
</footer>

<a class="wa-float" href="https://wa.me/{{ config('shop.whatsapp') }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="bi bi-whatsapp"></i></a>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- ربط ملف الـ JS الخاص بالمشروع بطريقة لارافيل -->
<script src="{{ asset('assets/js/app.js') }}"></script>
<script>
  // يفتح واتساب برسالة جاهزة من بيانات الفورم
  document.getElementById('waForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.currentTarget;
    if (!f.reportValidity()) return;
    var text = 'Hello AksSap, I am ' + f.cName.value.trim() + (f.cPhone.value.trim() ? ' (' + f.cPhone.value.trim() + ')' : '') + '.\n' + f.cMsg.value.trim();
    window.open('https://wa.me/' + f.dataset.wa + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
  });
</script>
</body>
</html>