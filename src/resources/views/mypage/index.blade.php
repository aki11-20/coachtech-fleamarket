@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
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
<div class="mypage">
    <div class="mypage__header">
        <div class="mypage__icon">
            @if(isset($profile) && $profile?->image)
            <img class="icon-img" src="{{ asset($profile->image) }}" alt="プロフィール画像"
                onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
            @else
            <span class="icon-ph"></span>
            @endif
        </div>
        <div class="mypage__name">{{ $profile->nickname ?? $user->name }}</div>
        <a class="btn-outline" href="{{ route('profile.edit') }}">プロフィールを編集</a>
    </div>

    <div class="tabs">
        <div class="tabs__inner">
            <a class="tabs__link {{ $tab === 'sell' ? 'is-active' : '' }}" href="{{ route('mypage', ['tab' => 'sell']) }}">出品した商品</a>
            <a class="tabs__link {{ $tab === 'buy'  ? 'is-active' : '' }}" href="{{ route('mypage', ['tab' => 'buy'])  }}">購入した商品</a>
        </div>
    </div>

    <section class="item-grid">
        @if ($tab === 'sell')
        @forelse ($sellingItems as $item)
        <a class="item-card" href="{{ route('items.show', ['item_id' => $item->id]) }}">
            <div class="item-card__thumb">
                @if($item->image)
                <img src="{{ asset($item->image) }}" alt="{{ $item->product_name }}"
                    onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
                @else
                <span class="item-card__placeholder">商品画像</span>
                @endif
                @if($item->order)
                <span class="item-card__badge">Sold</span>
                @endif
            </div>
            <div class="item-card__title">{{ $item->product_name }}</div>
        </a>
        @empty
        <p class="muted">出品した商品はまだありません。</p>
        @endforelse

        @elseif ($tab === 'buy')
        @forelse ($purchasedItems as $item)
        <a class="item-card" href="{{ route('items.show', ['item_id' => $item->id]) }}">
            <div class="item-card__thumb">
                @if($item->image)
                <img src="{{ asset($item->image) }}" alt="{{ $item->product_name }}"
                    onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
                @else
                <span class="item-card__placeholder">商品画像</span>
                @endif
                <span class="item-card__badge">Sold</span>
            </div>
            <div class="item-card__title">{{ $item->product_name }}</div>
        </a>
        @empty
        <p class="muted">購入した商品はまだありません。</p>
        @endforelse
        @endif
    </section>
</div>
@endsection
