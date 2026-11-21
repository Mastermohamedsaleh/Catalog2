{{-- Shared by the merchant and admin profile pages. --}}
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label small fw-semibold" for="pw_current">Current password</label>
            <input id="pw_current" name="current_password" type="password" required autocomplete="current-password"
                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
            @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="pw_new">New password</label>
            <input id="pw_new" name="password" type="password" required autocomplete="new-password"
                   class="form-control @error('password', 'updatePassword') is-invalid @enderror">
            @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="pw_confirm">Confirm new password</label>
            <input id="pw_confirm" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control">
        </div>
    </div>
    <div class="form-text">Use at least 8 characters. After changing it, keep using the same session.</div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-shield-lock me-1"></i>Update password</button>
</form>
