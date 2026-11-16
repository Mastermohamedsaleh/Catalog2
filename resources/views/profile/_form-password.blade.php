<form action="{{ route('admin.profile.updatePassword') }}" method="POST">
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