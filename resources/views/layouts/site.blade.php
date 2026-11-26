<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AksSap – Dropshipping Platform')</title>
    <meta name="description" content="Browse our product catalog by category.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
  <link href="{{ asset('assets/css/brand.css') }}" rel="stylesheet">
    <style>
        :root {
            --brand: #f26a00;
            --brand-dark: #d95a00;
            --brand-soft: #fff1e6;
  --navy: #0a1f5c;
  --navy-deep: #050f35;
  --amber: #ffb000;
            --ink: #0f172a;
            --muted: #64748b;
            --canvas: #f4f6fb;
            --sidebar: #07143f;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, .06), 0 1px 3px rgba(15, 23, 42, .06);
            --shadow-md: 0 8px 24px rgba(15, 23, 42, .08);
            --bs-primary: var(--brand);
            --bs-primary-rgb: 242, 106, 0;
            --bs-body-font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        body { background: var(--canvas); color: var(--ink); -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; }
        .btn-primary { --bs-btn-bg: var(--brand); --bs-btn-border-color: var(--brand);
            --bs-btn-hover-bg: var(--brand-dark); --bs-btn-hover-border-color: var(--brand-dark); }
        .btn-outline-primary { --bs-btn-color: var(--brand); --bs-btn-border-color: var(--brand);
            --bs-btn-hover-bg: var(--brand); --bs-btn-hover-border-color: var(--brand); }
        .text-primary { color: var(--brand) !important; }
        .site-nav { background: rgba(255, 255, 255, .92); backdrop-filter: blur(8px); box-shadow: var(--shadow-sm); }
        .hero { background: linear-gradient(135deg, #050f35 0%, #0a1f5c 62%, #17389a 100%); color: #fff; border-radius: 1.5rem; position: relative; overflow: hidden; }
        .hero::after { content: ""; position: absolute; width: 340px; height: 340px; right: -80px; top: -100px; border-radius: 50%; background: rgba(255, 255, 255, .09); }
        .cat-card { border: 0; border-radius: 1.1rem; background: #fff; box-shadow: var(--shadow-sm); overflow: hidden; transition: transform .2s, box-shadow .2s; height: 100%; color: inherit; display: block; }
        .cat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); color: inherit; }
        .ph { display: grid; place-items: center; color: rgba(255, 255, 255, .92); }
        .ph-cat { height: 130px; font-size: 3rem; }
        .g1 { background: linear-gradient(135deg, #0a1f5c, #1b3a9a); }
        .g2 { background: linear-gradient(135deg, #ff7a00, #ffb000); }
        .g3 { background: linear-gradient(135deg, #06133f, #0a1f5c); }
        .g4 { background: linear-gradient(135deg, #ffb000, #f26a00); }
        .g5 { background: linear-gradient(135deg, #1b3a9a, #2a56d0); }
        .g6 { background: linear-gradient(135deg, #d95a00, #f26a00); }
        .site-footer { background: var(--sidebar); color: #94a3b8; }
    </style>
    @stack('styles')
@include('partials.theme-head')
</head>
<body>
@include('partials.delivery-bar')

<!-- Navbar -->
<nav class="navbar navbar-expand-md site-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold brand-ink" href="{{ url('/') }}"><img src="{{ asset('assets/img/logo.png') }}" alt="AksSap logo" class="brand-logo"><span>Aks<span class="brand-accent">Sap</span></span></a>
        <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto ms-md-3">
                <li class="nav-item"><a class="nav-link active" href="{{ url('/') }}">Home</a></li>
            </ul>
<div class="d-flex gap-2 align-items-center">
    @auth
        @if((isset(auth()->user()->is_admin) && auth()->user()->is_admin)) 
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm">My Dashboard</a>
        @endif
        
        <form action="{{ route('logout') }}" method="POST" class="d-inline mb-0">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
        </form>
    @else
        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign in</a>
        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
    @endauth
</div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="container py-4 py-lg-5">
    @yield('content')
</main>

<!-- Footer -->
<footer class="site-footer py-4 mt-5">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 small">
        <span>© 2026 AksSap. All rights reserved.</span>
        <span><a href="{{ route('login') }}" class="text-reset">Admin / Marketer login</a></span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
@include('partials.theme-toggle')
</body>
</html>