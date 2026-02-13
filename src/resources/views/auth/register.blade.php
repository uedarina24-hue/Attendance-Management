@extends('layouts.guest')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css')}}">
@endsection


@section('content')
    <div class="auth-form">
        <div class="auth-form__text">
            <!-- タイトル -->
            <div class="auth-form__head">
                <h1 class="auth-form__heading">会員登録</h1>
            </div>

            <form class="register-form" action="{{ route('register') }}" method="POST" novalidate>
                @csrf

                <!-- 名前入力 -->
                <div class="form-group">
                    <label class="form-label" for="name">名前</label>
                    <input class="form-input" type="text" id="name" name="name"
                        value="{{ old('name') }}" required placeholder="山田 太郎">
                    <div class="auth__error">
                        @error('name')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
                <!-- メールアドレス入力 -->
                <div class="form-group">
                    <label class="form-label" for="email">メールアドレス</label>
                    <input class="form-input" type="email" id="email" name="email"
                        value="{{ old('email') }}" required placeholder="example@pigly.com">
                    <div class="auth__error">
                        @error('email')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <!-- パスワード入力 -->
                <div class="form-group">
                    <label class="form-label" for="password">パスワード</label>
                    <input class="form-input" type="password" id="password" name="password"
                        required placeholder="8文字以上で入力">
                    <div class="auth__error">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <!-- パスワード確認 -->
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">パスワード確認</label>
                    <input class="form-input" type="password" id="password_confirmation" name="password_confirmation"
                        required placeholder="8文字以上で入力">
                    <div class="auth__error">
                        @error('password_confirmation')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <!-- 登録ボタン -->
                <div class="form-button">
                    <button class="auth-button" type="submit">
                        登録する
                    </button>
                </div>
            </form>

            <!-- ログインリンク -->
            <div class="form-link">
                <a class="auth-link" href="{{ route('login') }}">
                    ログインはこちら
                </a>
            </div>
        </div>
    </div>
@endsection