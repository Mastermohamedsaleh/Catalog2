{{-- Shared by the merchant and admin profile pages. Needs $user. --}}
<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PATCH')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="p_name">Full name</label>
            <input id="p_name" name="name" type="text" maxlength="255" required autocomplete="name"
                   class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="p_phone">Phone number</label>
            <input id="p_phone" name="phone" type="tel" maxlength="20" required autocomplete="tel" dir="ltr" placeholder="0501234567"
                   class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold" for="p_email">Email</label>
            <input id="p_email" name="email" type="email" maxlength="255" required autocomplete="email" dir="ltr"
                   class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check2-circle me-1"></i>Save changes</button>
</form>
