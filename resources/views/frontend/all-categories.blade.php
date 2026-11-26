<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>All Categories – AksSap</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
  <link href="{{ asset('assets/css/brand.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@include('partials.theme-head')
</head>
<body class="d-flex flex-column min-vh-100">
@include('partials.delivery-bar')

<nav class="navbar navbar-expand-md site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold brand-ink" href="{{ url('/') }}"><img src="{{ asset('assets/img/logo.png') }}" alt="AksSap logo" class="brand-logo"><span>Aks<span class="brand-accent">Sap</span></span></a>
    <div class="d-flex gap-2">
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
</nav>

<main class="container py-4 py-lg-5 flex-grow-1">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">All Categories</li>
    </ol>
  </nav>

  <header class="mb-4">
    <h1 class="h2 fw-bold mb-1">All Categories</h1>
    <p class="text-muted mb-0">Browse through all our available product categories.</p>
  </header>

  <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-xl-4">
    @forelse($allCategories as $index => $c)
        @php
            $colors = ['g1', 'g2', 'g3', 'g4', 'g5', 'g6'];
            $colorClass = $colors[$index % count($colors)];
        @endphp
        <div class="col">
            <a href="{{ route('shop.category', $c->slug) }}" class="cat-card">
                @if($c->image)
                    <div class="position-relative overflow-hidden" style="height: 130px;">
                        <img src="{{ asset('storage/' . $c->image) }}" alt="{{ $c->name }}" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                @else
                    <div class="ph ph-cat {{ $colorClass }}">
                        <i class="bi bi-grid-fill"></i>
                    </div>
                @endif
                <div class="p-3">
                    <h3 class="h6 fw-bold mb-1">{{ $c->name }}</h3>
                    <p class="small text-muted mb-0">{{ $c->products_count ?? 0 }} products</p>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12 text-center py-5">
            <p class="text-muted">No categories are available right now.</p>
        </div>
    @endforelse
  </div>
</main>

<footer class="site-footer pt-5 pb-4 mt-auto">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-4"><div class="fs-5 fw-bold text-white mb-2"><img src="{{ asset('assets/img/logo.png') }}" alt="AksSap logo" class="brand-logo"><span>Aks<span class="brand-accent">Sap</span></span></div><p class="small mb-0">Quality products, honest prices and real support since 2015.</p></div>
      <div class="col-6 col-lg-2"><div class="text-white fw-semibold mb-2">Links</div><ul class="list-unstyled small mb-0"><li><a class="text-reset" href="{{ url('/') }}#about">About us</a></li><li><a class="text-reset" href="{{ url('/') }}#services">What we offer</a></li><li><a class="text-reset" href="{{ url('/') }}#categories">Categories</a></li><li><a class="text-reset" href="{{ url('/') }}#contact">Contact</a></li></ul></div>
      <div class="col-6 col-lg-3"><div class="text-white fw-semibold mb-2">Contact</div><ul class="list-unstyled small mb-0"><li><i class="bi bi-telephone me-2"></i>{{ \App\Support\Phone::display(config('shop.phone')) }}</li><li><i class="bi bi-envelope me-2"></i>{{ config('shop.email') }}</li><li><i class="bi bi-geo-alt me-2"></i>{{ config('shop.city') }}</li></ul></div>
      <div class="col-lg-3"><div class="text-white fw-semibold mb-2">Follow us</div>@include('partials.social-links')</div>
    </div>

  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.theme-toggle')
</body>
</html>