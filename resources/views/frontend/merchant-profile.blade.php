<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile · CatalogPro Merchant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<div id="toastTop" aria-live="polite"></div>

<!-- ============ Sidebar ============ -->
<aside class="sidebar" id="sidebar">
  <a href="{{ route('merchant.orders.index') }}" class="brand"><i class="bi bi-grid-fill me-2"></i>CatalogPro</a>
  <nav class="nav flex-column flex-grow-1 overflow-auto">
    <div class="nav-label">Merchant</div>
    <a class="nav-link" href="{{ route('merchant.orders.index') }}"><i class="bi bi-cart-plus"></i>New order</a>
    <a class="nav-link" href="{{ route('merchant.wallet') }}"><i class="bi bi-wallet2"></i>My wallet</a>
    <a class="nav-link active" href="{{ route('merchant.profile.edit') }}"><i class="bi bi-person me-2"></i>My Profile</a>
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
  <!-- ============ Topbar ============ -->
  <header class="topbar d-flex align-items-center gap-3 px-3 px-lg-4 py-2">
    <button class="btn btn-light d-lg-none" data-sidebar-toggle aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
    <h1 class="h5 fw-bold mb-0 me-auto">My Profile</h1>
    <a href="{{ route('merchant.wallet') }}" class="btn btn-light btn-sm d-flex align-items-center gap-2" title="Wallet balance">
      <i class="bi bi-wallet2 text-primary"></i><span class="fw-semibold">{{ \App\Models\Order::where('user_id', Auth::id())->where('status', 'delivered')->sum('profit') }} EGP</span>
    </a>
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center gap-2 text-dark" data-bs-toggle="dropdown">
        <span class="thumb g3" style="width:36px;height:36px;border-radius:50%">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
        <span class="d-none d-sm-block small fw-semibold">{{ Auth::user()->name }}</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
        <li>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-left me-2"></i>Log out</button>
            </form>
        </li>
      </ul>
    </div>
  </header>

  <main class="p-3 p-lg-4 flex-grow-1">
    <!-- الأخطاء والرسائل العامة -->
    @if($errors->any() && !$errors->hasBag('userDeletion'))
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success mb-4 small">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4 small">{{ session('error') }}</div>
    @endif

    <!-- ============ PROFILE CONTENT ============ -->
    <div class="row g-4">
      {{-- Summary --}}
      <div class="col-lg-4">
        <div class="card-soft p-4 text-center mb-4">
          <span class="thumb g1 mx-auto mb-3 d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width:84px;height:84px;border-radius:50%;font-size:2rem">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
          <h2 class="h5 fw-bold mb-1">{{ $user->name }}</h2>
          <div class="text-muted small" dir="ltr">{{ $user->email }}</div>
          <span class="badge badge-soft-primary mt-2">Merchant</span>
          <div class="text-muted small mt-3">Member since {{ $user->created_at->format('M Y') }}</div>
        </div>

        <div class="row g-3">
          <div class="col-12">
            <a href="{{ route('merchant.wallet') }}" class="card-soft p-3 d-flex align-items-center gap-3 text-dark text-decoration-none stat-card">
              <span class="stat-icon bg-brand-soft text-primary"><i class="bi bi-wallet2"></i></span>
              <div><div class="text-muted small">Wallet balance</div><div class="h5 fw-bold mb-0 text-dark">{{ \App\Models\Order::where('user_id', Auth::id())->where('status', 'delivered')->sum('profit') }} EGP</div></div>
            </a>
          </div>
          <div class="col-6">
            <div class="card-soft p-3 h-100"><div class="text-muted small">Orders</div><div class="h5 fw-bold mb-0">{{ $ordersTotal }}</div></div>
          </div>
          <div class="col-6">
            <div class="card-soft p-3 h-100"><div class="text-muted small">Delivered</div><div class="h5 fw-bold mb-0">{{ $ordersDelivered }}</div></div>
          </div>
        </div>
      </div>

      {{-- Forms --}}
      <div class="col-lg-8">
        <!-- 1. نموذج تحديث معلومات الحساب -->
        <div class="card-soft p-3 p-lg-4 mb-4">
          <h2 class="h6 fw-bold mb-1">Personal information</h2>
          <p class="text-muted small mb-3">Your phone number is used by the team to reach you about orders and payouts.</p>
          
          <form action="{{ route('merchant.profile.updateInfo') }}" method="POST">
              @csrf
              @method('PUT')

              <div class="mb-3">
                  <label class="form-label small fw-semibold">Name</label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
                  @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="mb-3">
                  <label class="form-label small fw-semibold">Email address</label>
                  <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                  @error('email')
                      <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="text-end">
                  <button type="submit" class="btn btn-primary btn-sm px-4">Save changes</button>
              </div>
          </form>
        </div>

        <!-- 2. نموذج تغيير كلمة المرور -->
        <div class="card-soft p-3 p-lg-4 mb-4">
          <h2 class="h6 fw-bold mb-1">Change password</h2>
          <p class="text-muted small mb-3">Choose a long, unique password.</p>
          
          <form action="{{ route('merchant.profile.updatePassword') }}" method="POST">
              @csrf
              @method('PUT')

              <div class="mb-3">
                  <label class="form-label small fw-semibold">Current password</label>
                  <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required>
                  @error('current_password')
                      <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="mb-3">
                  <label class="form-label small fw-semibold">New password</label>
                  <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                  @error('password')
                      <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="mb-3">
                  <label class="form-label small fw-semibold">Confirm new password</label>
                  <input type="password" class="form-control" name="password_confirmation" required>
              </div>

              <div class="text-end">
                  <button type="submit" class="btn btn-primary btn-sm px-4">Update password</button>
              </div>
          </form>
        </div>

        <!-- 3. قسم حذف الحساب -->
        <div class="card-soft p-3 p-lg-4 border border-danger-subtle">
          <h2 class="h6 fw-bold text-danger mb-1">Delete account</h2>
          @if($canDelete)
            <p class="text-muted small mb-3">This permanently removes your account. It cannot be undone.</p>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="bi bi-trash me-1"></i>Delete my account</button>
          @else
            <p class="text-muted small mb-0">Accounts with orders or wallet history can't be deleted because those records are kept for accounting. Contact support if you need help.</p>
          @endif
        </div>
      </div>
    </div>
  </main>
</div>

@if($canDelete)
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('merchant.profile.destroy') }}" class="modal-content">
      @csrf
      @method('DELETE')
      <div class="modal-header border-0"><h5 class="modal-title">Delete your account?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body text-start">
        <p class="small text-muted">Enter your password to confirm.</p>
        <input type="password" name="password" required autocomplete="current-password" placeholder="Password"
               class="form-control @error('password', 'userDeletion') is-invalid @enderror">
        @error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete account</button>
      </div>
    </form>
  </div>
</div>
@endif

<!-- ============ Scripts ============ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>

@if($errors->userDeletion->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var deleteModalEl = document.getElementById('deleteModal');
        if (deleteModalEl) {
            new bootstrap.Modal(deleteModalEl).show();
        }
    });
</script>
@endif
</body>
</html>