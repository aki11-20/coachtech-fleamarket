@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<div class="register-wrap">
    <div class="register-card">
        <h1 class="register-title">会員登録</h1>
        <form class="register-form" action="{{ route('register') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="name">ユーザー名</label>
                <input class="form-input" type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="name">
                @error('name')
                <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email">
                @error('email')
                <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">パスワード</label>
                <input class="form-input" type="password" id="password" name="password" value="{{ old('password') }}" autocomplete="new-password">
                @error('password')
                <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">確認用パスワード</label>
                <input class="form-input" type="password" id="password_confirmation" name="password_confirmation" value="{{ old('password_confirmation') }}" autocomplete="new-password">
                @error('password_confirmation')
                <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <button class="register-btn" type="submit">登録する</button>
        </form>

        <a class="login" href="{{ route('login') }}">ログインはこちら</a>
    </div>
</div>
@endsection
