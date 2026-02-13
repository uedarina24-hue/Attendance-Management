@extends('layouts.guest')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css')}}">
@endsection


@section('content')
    <div class="auth-form">
        <div class="auth-form__text">
            <!-- タイトル -->
            <div class="auth-form__head">
                <h1 class="auth-form__heading">ログイン</h1>
            </div>
            <form class="login-form" action="{{ route('login') }}" method="POST" novalidate>
                @csrf

                <!-- メールアドレス入力 -->
                <div class="form-group">
                    <label class="form-label" for="email">メールアドレス</label>
                    <input class="form-input" type="email" id="email" name="email" placeholder="メールアドレスを入力" required>
                    <div class="auth__error">
                        @error('email')
                            {{ $message }}
                        @enderror

                        @error('email', 'login')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <!-- パスワード入力 -->
                <div class="form-group">
                    <label class="form-label" for="password">パスワード</label>
                    <input class="form-input" type="password" id="password" name="password"  placeholder="パスワードを入力" required>
                    <div class="auth__error">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <!-- 次の画面にすすむボタン -->
                <div class="form-button">
                    <button class="auth-button" type="submit">
                        ログインする
                    </button>
                </div>
            </form>

            <!-- アカウント作成リンク -->
            <div class="form-link">
                <a class="auth-link" href="{{ route('register') }}">
                    会員登録はこちら
                </a>
            </div>
        </div>
    </div>
@endsection