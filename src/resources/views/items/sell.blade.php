@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
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
<div class="sell-wrap">
    <div class="sell-card">
        <h1 class="sell-title">商品の出品</h1>

        <form class="sell-form" action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="sell-block">
                <h2 class="block-title">商品画像</h2>
                <label class="image-upload">
                    <input type="file" name="image" accept="image/jpeg,image/png" hidden>
                    <span class="upload-btn">画像を選択する</span>
                </label>
                @error('image')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="sell-block">
                <h2 class="block-title">商品の詳細</h2>
                <div class="sub-title">カテゴリー</div>
                <div class="categories">
                    @foreach($categories as $cat)
                    <input class="cat-input" id="cat-{{ $cat->id }}" type="checkbox" name="categories[]" value="{{ $cat->id }}" {{ in_array($cat->id, old('categories', [])) ? 'checked' : '' }}>
                    <label class="chip" for="cat-{{ $cat->id }}">{{ $cat->name }}</label>
                    @endforeach
                </div>
                @error('categories')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror

                <div class="sub-title">商品の状態</div>
                <select class="select" name="condition">
                    <option value="">選択してください</option>
                    @foreach(['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い'] as $c)
                    <option value="{{ $c }}" {{ old('condition') === $c ? 'selected' : '' }}>{{ $c}}</option>
                    @endforeach
                </select>
                @error('condition')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="sell-block">
                <h2 class="block-title">商品名と説明</h2>

                <label class="form-label" for="product_name">商品名</label>
                <input class="form-input" type="text" name="product_name" value="{{ old('product_name') }}">
                @error('product_name')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror

                <label class="form-label" for="brand_name">ブランド名</label>
                <input class="form-input" type="text" name="brand_name" value="{{ old('brand_name') }}">

                <label class="form-label" for="description">商品の説明</label>
                <textarea class="form-textarea" name="description" rows="5">{{ old('description') }}</textarea>
                @error('description')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="sell-block">
                <label class="form-label" for="price">販売価格</label>
                <div class="price-row">
                    <span class="price-prefix">¥</span>
                    <input class="price-input" type="text" name="price" value="{{ old('price') }}">
                </div>
                @error('price')
                <p class="error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <button class="sell-btn" type="submit">出品する</button>
        </form>
    </div>
</div>
@endsection