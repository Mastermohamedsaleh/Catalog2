<form action="{{ route('admin.profile.updateInfo') }}" method="POST">
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