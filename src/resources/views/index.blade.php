@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('header_search')
<form class="search" action="{{ route('items.index') }}" method="GET">
    <input type="search" class="search__input" name="keyword" value="{{ $keyword ?? ' ' }}" placeholder="なにをお探しですか？">
    @if(request('tab') === 'mylist')
    <input type="hidden" name="tab" value="mylist">
    @endif
</form>
@endsection

@section('header_actions')
<nav class="header-nav">
    <ul class="header-nav-list">
        @guest
        <li class="header-nav-item">
            <a class="header-link" href="{{ route('login') }}">ログイン</a>
        </li>
        <li class="header-nav-item">
            <a class="header-link" href="{{ route('mypage') }}">マイページ</a>
        </li>
        <li class="header-nav-item">
            <a class="header-btn" href="{{ route('items.create') }}">出品</a>
        </li>
        @endguest

        @auth
        <li class="header-nav-item">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="header-link" type="submit">
                    ログアウト
                </button>
            </form>
        </li>
        <li class="header-nav-item">
            <a class="header-link" href="{{ route('mypage') }}">マイページ</a>
        </li>
        <li class="header-nav-item">
            <a class="header-btn" href="{{ route('items.create') }}">出品</a>
        </li>
        @endauth
    </ul>
</nav>
@endsection

@section('content')
<div class="tabs">
    <div class="tabs__inner">
        <a class="tabs__link {{ $tab === 'recommend' ? 'is-active' : '' }}" href="{{ route('items.index', ['tab' => 'recommend', 'keyword' => request('keyword')]) }}">おすすめ</a>
        <a class="tabs__link {{ $tab === 'mylist' ? 'is-active' : '' }}" href="{{ route('items.index', ['tab' => 'mylist', 'keyword' => request('keyword')]) }}">マイリスト</a>
    </div>
</div>

<section class="item-grid">
    @forelse($items as $item)
    <a class="item-card" href="{{ route('items.show', $item->id) }}">
        <div class="item-card__thumb">
            @if($item->image)
            <img src="{{ asset($item->image) }}" alt="{{ $item->product_name }}">
            @else
            <span class="item-card__placeholder">商品画像</span>
            @endif

            @if($item->order && $item->order->isPaid())
            <span class="item-card__badge">Sold</span>
            @elseif($item->order && $item->order->isPending())
            <span class="item-card__badge">取引中</span>
            @endif
        </div>

        <div class="item-card__title">
            {{ $item->product_name }}
        </div>
    </a>
    @empty
    <p style="max-width:1200px;margin:24px auto;padding:0 8px;">
        @if($tab === 'mylist')
        まだマイリストは空です。
        @else
        商品がありません。
        @endif
    </p>
    @endforelse
</section>
@endsection