<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendanceManagement</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/app.css')}}">
    @yield('css')
</head>

<body>
    <div class="user-page">
        <header class="user-header">
            <a href="/">
                <img class="user-header__logo" src="{{ asset('images/logo.png') }}" alt="logo">
            </a>
            <nav class="user-header__nav">
                <ul class="user-header__list">
                    <li class="user-header__list-item">
                        <form action="{{ route('attendance.index') }}" class="user-header__form" method="get">
                            <button class="user-header__form--link" type="submit">勤怠</button>
                        </form>
                    </li>
                    <li class="user-header__list-item">
                        <form action="{{ route('attendance.list') }}" class="user-header__form" method="get">
                            <button class="user-header__form--link" type="submit">勤怠一覧</button>
                        </form>
                    </li>
                    <li class="user-header__list-item">
                        <form action="{{ route('stamp_correction_request.list') }}" class="user-header__form" method="get">
                            <button class="user-header__form--link" type="submit">申請</button>
                        </form>
                    </li>
                    <li class="user-header__list-item">
                        <form class="user-header__form" action="{{ route('logout') }}" method="post">
                        @csrf
                            <button class="user-header__form--link" type="submit">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
            @yield('link')
        </header>

        <main class="user-container">
            <!-- エラーフラッシュメッセージ -->
            @if(session('error'))
                <div class="user__error">
                    {{ session('error') }}
                </div>
            @endif

            <div class="card @yield('cardClass')">
            @yield('content')
            </div>
        </main>
    </div>
</body>
</html>