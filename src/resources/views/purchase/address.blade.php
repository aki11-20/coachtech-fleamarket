@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/address.css') }}">
@endsection

@section('header_search')
<form class="search" action="{{ route('items.index') }}" method="GET">
    @csrf
    <input type="search" class="search__input" name="keyword" placeholder="なにをお探しですか？">
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
<div class="address-wrap">
    <h1 class="address-title">住所変更</h1>

    <form class="address-form" action="{{ route('purchase.address.update', ['item_id' => $item->id]) }}" method="POST">
        @csrf

        <div class="form-group">
            <label class="form-label" for="postal_code">郵便番号</label>
            <input class="form-input" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $profile->postal_code ?? '') }}">
            @error('postal_code')
            <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="address">住所</label>
            <input class="form-input" type="text" name="address" id="address" value="{{ old('address', $profile->address ?? '') }}">
            @error('address')
            <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="building">建物名</label>
            <input class="form-input" type="text" name="building" id="building" value="{{ old('building', $profile->building ?? '') }}">
            @error('building')
            <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <button class="address-btn" type="submit">更新する</button>

        <div class="address-back">
            <a href="{{ route('purchase.show',['item_id' => $item->id]) }}">購入画面に戻る</a>
        </div>
    </form>
</div>
@endsection