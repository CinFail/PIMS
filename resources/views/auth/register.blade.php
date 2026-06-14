<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PIMS - Register</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="auth-page">
<div class="auth-wrap">
    <div style="text-align:center;margin-bottom:20px;">
        <img src="{{ asset('images/logo.jpg') }}" alt="EGBC Diagnostic and Medical Services"
             style="height:64px;width:auto;display:inline-block;">
    </div>
    <h1>Register</h1>
    <p class="page-subtitle">Create a patient account</p>

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('register') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="first_name">First Name <span class="req">*</span></label>
            <input type="text" name="first_name" id="first_name"
                   value="{{ old('first_name') }}"
                   class="{{ $errors->has('first_name') ? 'is-error' : '' }}" required>
            @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="middle_name">Middle Name</label>
            <input type="text" name="middle_name" id="middle_name"
                   value="{{ old('middle_name') }}"
                   class="{{ $errors->has('middle_name') ? 'is-error' : '' }}">
            @error('middle_name') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="last_name">Last Name <span class="req">*</span></label>
            <input type="text" name="last_name" id="last_name"
                   value="{{ old('last_name') }}"
                   class="{{ $errors->has('last_name') ? 'is-error' : '' }}" required>
            @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="email">Email <span class="req">*</span></label>
            <div class="input-icon-wrap">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" id="email"
                       value="{{ old('email') }}"
                       class="{{ $errors->has('email') ? 'is-error' : '' }}" required>
            </div>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="mobile_number">Mobile Number</label>
            <input type="text" name="mobile_number" id="mobile_number"
                   value="{{ old('mobile_number') }}"
                   class="{{ $errors->has('mobile_number') ? 'is-error' : '' }}"
                   data-mobile placeholder="09XX-XXX-XXXX" maxlength="13">
            @error('mobile_number') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="date_of_birth">Date of Birth <span class="req">*</span></label>
            <input type="date" name="date_of_birth" id="date_of_birth"
                   value="{{ old('date_of_birth') }}"
                   max="{{ date('Y-m-d') }}"
                   class="{{ $errors->has('date_of_birth') ? 'is-error' : '' }}" required>
            @error('date_of_birth') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="password">Password <span class="req">*</span></label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" id="password"
                       class="{{ $errors->has('password') ? 'is-error' : '' }}" required>
            </div>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm Password <span class="req">*</span></label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock-fill"></i>
                <input type="password" name="password_confirmation" id="password_confirmation" required>
            </div>
        </div>
        <button type="submit" class="btn btn-full">Register</button>
    </form>

    <div class="center-link">
        Already have an account? <a href="{{ route('login') }}">Log in</a>
    </div>
</div>
<script>
/* Mobile mask for register page (layout.js not loaded here) */
(function () {
    function fmt(raw) {
        var d = raw.replace(/\D/g, '').substring(0, 11);
        if (d.length > 7) return d.substring(0,4)+'-'+d.substring(4,7)+'-'+d.substring(7);
        if (d.length > 4) return d.substring(0,4)+'-'+d.substring(4);
        return d;
    }
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.querySelector('[data-mobile]');
        if (!el) return;
        if (el.value) el.value = fmt(el.value);
        el.addEventListener('input', function () { this.value = fmt(this.value); });
        el.addEventListener('keydown', function (e) {
            if (e.key.length === 1 && !/\d/.test(e.key) && !e.ctrlKey && !e.metaKey) e.preventDefault();
        });
        el.closest('form').addEventListener('submit', function () {
            el.value = el.value.replace(/\D/g, '');
        });
    });
})();
</script>
</body>
</html>
