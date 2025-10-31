@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('header_search')
<form class="search" action="{{ route('items.index') }}" method="GET">
    <input type="search" class="search__input" name="keyword" value="{{ request('keyword') }}" placeholder="なにをお探しですか？">
</form>
@endsection

@section('header_actions')
<nav class="header-nav">
    <ul class="header-nav-list">
        <li class="header-nav-item">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="header-link" type="submit">ログアウト</button>
            </form>
        </li>
        <li class="header-nav-item"><a href="{{ route('mypage') }}">マイページ</a></li>
        <li class="header-nav-item"><a class="header-btn" href="{{ route('items.create') }}">出品</a></li>
    </ul>
</nav>
@endsection

@section('content')
<div class="profile-wrap">
    <div class="profile-card">
        <h1 class="profile-title">プロフィール設定</h1>

        @if (session('status'))
        <p class="flash-success">{{ session('status') }}</p>
        @endif

        <form class="profile-form" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <div class="icon-row">
                    <div class="icon">
                        @if($profile && $profile->image)
                        <img class="icon__img" src="{{ asset($profile->image) }}" alt="プロフィール画像">
                        @else
                        <span class="icon__ph"></span>
                        @endif
                    </div>

                    <div class="icon-upload">
                        <input type="file" id="profile_image" name="image" accept="image/jpeg,image/png" hidden>
                        <label class="btn-outline" for="profile_image">
                            画像を選択する
                        </label>
                        @error('image')
                        <p class="error">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="nickname">ユーザー名</label>
                <input class="form-input" type="text" name="nickname" value="{{ old('nickname', $profile->nickname ?? auth()->user()->name) }}">
                @error('nickname')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="postal_code">郵便番号</label>
                <input class="form-input" type="text" name="postal_code" value="{{ old('postal_code', $profile->postal_code ?? '') }}">
                @error('postal_code')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="address">住所</label>
                <input class="form-input" type="text" name="address" value="{{ old('address', $profile->address ?? '') }}">
                @error('address')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="building">建物名</label>
                <input class="form-input" type="text" name="building" value="{{ old('building', $profile->building ?? '') }}">
                @error('building')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <button class="update-btn" type="submit">更新する</button>
        </form>
    </div>
</div>
@endsection