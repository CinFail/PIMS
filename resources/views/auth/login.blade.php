<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PIMS - Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="auth-page">
<div class="auth-wrap">
    <div style="text-align:center;margin-bottom:24px;">
        <img src="{{ asset('images/logo.jpg') }}" alt="EGBC Diagnostic and Medical Services"
             style="height:80px;width:auto;display:inline-block;">
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="form-group">
            <div class="input-icon-wrap">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       placeholder="Email" required autofocus>
            </div>
        </div>
        <div class="form-group">
            <div class="input-icon-wrap">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" id="password"
                       placeholder="Password" required>
            </div>
        </div>
        <div class="checkbox-row" style="margin-bottom:16px;">
            <input type="checkbox" name="remember" id="remember" value="1">
            <label for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn btn-full">Log In</button>
    </form>

    <div class="center-link">
        New patient? <a href="{{ route('register') }}">Create an account</a>
    </div>
</div>
</body>
</html>
