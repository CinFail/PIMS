<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PIMS - Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-wrap">
    <h1>PIMS</h1>
    <p class="page-subtitle" style="text-align:center;">Clinic Patient Information Management System</p>

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
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="checkbox-row">
            <input type="checkbox" name="remember" id="remember" value="1">
            <label for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn" style="width:100%;">Log In</button>
    </form>

    <div class="center-link">
        New patient? <a href="{{ route('register') }}">Create an account</a>
    </div>
</div>
</body>
</html>
