<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendanceManagement</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/auth.css')}}">
    @yield('css')
</head>

<body>
    <div class="auth-page">
        <header class="auth-header">
            <a href="/">
                <img class="auth-header__logo" src="{{ asset('images/logo.png') }}" alt="logo">
            </a>
            @yield('link')
        </header>

        <main class="auth-container">
            <div class="auth-card">
            @yield('content')
            </div>
        </main>
    </div>
</body>

</html>