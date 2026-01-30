<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendanceManagement</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css')}}">
    @yield('css')
</head>

<body>
    <div class="admin-page">
        <header class="admin-header">
            <a href="/">
                <img class="admin-header__logo" src="{{ asset('images/logo.png') }}" alt="logo">
            </a>
            <nav class="admin-header__nav">
                <ul class="admin-header__list">
                    <li class="admin-header__list-item">
                        <form action="{{ route('admin.attendance.list') }}" class="admin-header__form" method="get">
                            <button class="admin-header__form--link" type="submit">勤怠一覧</button>
                        </form>
                    </li>
                    <li class="admin-header__list-item">
                        <form action="{{ route('admin.staff.list') }}" class="admin-header__form" method="get">
                            <button class="admin-header__form--link" type="submit">スタッフ一覧</button>
                        </form>
                    </li>
                    <li class="admin-header__list-item">
                        <form action="{{ route('admin.stamp_correction_request.list') }}" class="admin-header__form" method="get">
                            <button class="admin-header__form--link" type="submit">申請一覧</button>
                        </form>
                    </li>
                    <li class="admin-header__list-item">
                        <form class="admin-header__form" action="{{ route('admin.logout') }}" method="post">
                        @csrf
                            <button class="admin-header__form--link" type="submit">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
            @yield('link')
        </header>

        <main class="admin-container">
            <!-- エラーフラッシュメッセージ -->
            @if(session('error'))
                <div class="admin__error">
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