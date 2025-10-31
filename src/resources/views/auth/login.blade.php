@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <h1 class="login-title">ログイン</h1>
        <form class="login-form" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" />
                @error('email')
                <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="password">パスワード</label>
                <input class="form-input" type="password" id="password" name="password" value="{{ old('password') }}" autocomplete="current-password" />
                @error('password')
                <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <button class="login-btn" type="submit">
                ログインする
            </button>
        </form>
        <a class="register" href="{{route('register') }}">会員登録はこちら</a>
    </div>
</div>
@endsection