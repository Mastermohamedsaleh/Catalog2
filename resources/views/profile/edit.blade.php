@extends('layouts.admin')

@section('title', 'My profile')
@section('page_title', 'My profile')

@section('content')
<div class="row g-4">
  <div class="col-xl-4">
    <div class="card-soft p-4 text-center mb-4">
      <span class="thumb g1 mx-auto mb-3" style="width:84px;height:84px;border-radius:50%;font-size:2rem">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
      <h2 class="h5 fw-bold mb-1">{{ $user->name }}</h2>
      <div class="text-muted small" dir="ltr">{{ $user->email }}</div>
      @if($user->isSuperAdmin())
        <span class="badge badge-soft-danger mt-2"><i class="bi bi-shield-fill-check me-1"></i>Super admin</span>
      @else
        <span class="badge badge-soft-primary mt-2"><i class="bi bi-shield-check me-1"></i>Admin</span>
      @endif
      <div class="text-muted small mt-3">Account created {{ $user->created_at->format('d M Y') }}</div>
    </div>

    <div class="card-soft p-3 p-lg-4">
      <h2 class="h6 fw-bold mb-2">Access</h2>
      <ul class="list-unstyled small text-muted mb-0">
        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Manage catalog, orders and customers</li>
        @if($user->isSuperAdmin())
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Manage other admin accounts</li>
        @endif
        <li><i class="bi bi-info-circle me-2"></i>Your role can only be changed by a super admin.</li>
      </ul>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card-soft p-3 p-lg-4 mb-4">
      <h2 class="h6 fw-bold mb-3">Account information</h2>
      @include('profile._form-info')
    </div>

    <div class="card-soft p-3 p-lg-4">
      <h2 class="h6 fw-bold mb-1">Change password</h2>
      <p class="text-muted small mb-3">Admin accounts control money and stock — use a strong, unique password.</p>
      @include('profile._form-password')
    </div>
  </div>
</div>
@endsection
