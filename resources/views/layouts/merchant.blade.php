<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Merchant') · CatalogPro Merchant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
<div id="toastTop" aria-live="polite"></div>

<aside class="sidebar" id="sidebar">
  <a href="{{ route('merchant.orders.index') }}" class="brand"><i class="bi bi-grid-fill me-2"></i>CatalogPro</a>
  <nav class="nav flex-column flex-grow-1 overflow-auto">
    <div class="nav-label">Merchant</div>
    <a class="nav-link {{ request()->routeIs('merchant.orders.*') ? 'active' : '' }}" href="{{ route('merchant.orders.index') }}"><i class="bi bi-cart-plus"></i>New order</a>
    <a class="nav-link {{ request()->routeIs('merchant.wallet*') ? 'active' : '' }}" href="{{ route('merchant.wallet') }}"><i class="bi bi-wallet2"></i>My wallet</a>
    <a class="nav-link {{ request()->routeIs('merchant.profile') ? 'active' : '' }}" href="{{ route('merchant.profile') }}"><i class="bi bi-person-circle"></i>My profile</a>
    <div class="nav-label">Site</div>
    <a class="nav-link" href="{{ url('/') }}" target="_blank"><i class="bi bi-globe2"></i>View catalog</a>
  </nav>
  <div class="p-3 border-top border-secondary-subtle">
    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit" class="nav-link m-0 bg-transparent border-0 text-danger w-100 text-start">
        <i class="bi bi-box-arrow-left me-2"></i>Log out
      </button>
    </form>
  </div>
</aside>
<div class="sidebar-backdrop" data-sidebar-toggle></div>

<div class="main">
  <header class="topbar d-flex align-items-center gap-3 px-3 px-lg-4 py-2">
    <button class="btn btn-light d-lg-none" data-sidebar-toggle aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
    <h1 class="h5 fw-bold mb-0 me-auto">@yield('page_title')</h1>
    <a href="{{ route('merchant.orders.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New order</span></a>
  </header>

  <main class="p-3 p-lg-4">
    @yield('content')
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
@if(session('success'))<script>showToast(@json(session('success')), 'success');</script>@endif
@if(session('error'))<script>showToast(@json(session('error')), 'error', 6000);</script>@endif
@stack('scripts')
</body>
</html>
