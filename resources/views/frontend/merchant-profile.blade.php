@extends('layouts.merchant')

@section('title', 'My profile')
@section('page_title', 'My profile')

@section('content')
<div class="row g-4">
  {{-- Summary --}}
  <div class="col-lg-4">
    <div class="card-soft p-4 text-center mb-4">
      <span class="thumb g1 mx-auto mb-3" style="width:84px;height:84px;border-radius:50%;font-size:2rem">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
      <h2 class="h5 fw-bold mb-1">{{ $user->name }}</h2>
      <div class="text-muted small" dir="ltr">{{ $user->email }}</div>
      <span class="badge badge-soft-primary mt-2">Merchant</span>
      <div class="text-muted small mt-3">Member since {{ $user->created_at->format('M Y') }}</div>
    </div>

    <div class="row g-3">
      <div class="col-12">
        <a href="{{ route('merchant.wallet') }}" class="card-soft p-3 d-flex align-items-center gap-3 text-dark stat-card">
          <span class="stat-icon bg-brand-soft text-primary"><i class="bi bi-wallet2"></i></span>
          <div><div class="text-muted small">Wallet balance</div><div class="h5 fw-bold mb-0">{{ number_format($balance, 2) }} {{ config('shop.currency') }}</div></div>
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
    <div class="card-soft p-3 p-lg-4 mb-4">
      <h2 class="h6 fw-bold mb-1">Personal information</h2>
      <p class="text-muted small mb-3">Your phone number is used by the team to reach you about orders and payouts.</p>
      @include('profile._form-info')
    </div>

    <div class="card-soft p-3 p-lg-4 mb-4">
      <h2 class="h6 fw-bold mb-1">Change password</h2>
      <p class="text-muted small mb-3">Choose a long, unique password.</p>
      @include('profile._form-password')
    </div>

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

@if($canDelete)
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('profile.destroy') }}" class="modal-content">
      @csrf
      @method('DELETE')
      <div class="modal-header border-0"><h5 class="modal-title">Delete your account?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
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
@if($errors->userDeletion->isNotEmpty())
@push('scripts')
<script>document.addEventListener('DOMContentLoaded', function () { new bootstrap.Modal(document.getElementById('deleteModal')).show(); });</script>
@endpush
@endif
@endsection
