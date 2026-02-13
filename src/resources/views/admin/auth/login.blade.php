@extends('layouts.admin-guest')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css')}}">
@endsection


@section('content')
    <div class="auth-form">
        <div class="auth-form__text">
            <!-- タイトル -->
            <div class="auth-form__head">
                <h1 class="auth-form__heading">管理者ログイン</h1>
            </div>
            <form class="login-form" action="{{ route('admin.login.store') }}" method="POST" novalidate>
                @csrf

                <!-- メールアドレス入力 -->
                <div class="form-group">
                    <label class="form-label" for="email">メールアドレス</label>
                    <input class="form-input" type="email" id="email" name="email"
                        value="{{ old('email') }}" placeholder="メールアドレスを入力" required>
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
                        placeholder="パスワードを入力" required>
                    <div class="auth__error">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <!-- 次の画面にすすむボタン -->
                <div class="form-button">
                    <button class="admin-button" type="submit">
                        管理者ログインする
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection