@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('header_search')
<form class="search" action="{{ route('items.index') }}" method="GET">
    <input type="search" class="search__input" name="keyword" placeholder="なにをお探しですか？">
</form>
@endsection

@section('header_actions')
<nav class="header-nav">
    <ul class="header-nav-list">
        @guest
        <li class="header-nav-item">
            <a href="{{ route('login') }}">ログイン</a>
        </li>
        <li class="header-nav-item">
            <a href="{{ route('mypage') }}">マイページ</a>
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
            <a href="{{ route('mypage') }}">マイページ</a>
        </li>
        <li class="header-nav-item">
            <a class="header-btn" href="{{ route('items.create') }}">出品</a>
        </li>
        @endauth

    </ul>
</nav>
@endsection

@section('content')
<div class="item-show">
    <div class="item-show__container">
        <div class="item-show__image">
            <div class="item-card__thumb">
                @if($item->image)
                <img src="{{ asset($item->image) }}" alt="{{ $item->product_name }}" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
                @else
                <span class="item-card__placeholder">商品画像</span>
                @endif

                @if($item->order && $item->order->isPaid())
                <span class="badge-sold">売却済み</span>
                @elseif($item->order && $item->order->isPending())
                <span class="badge-sold">購入手続き中</span>
                @endif
            </div>
        </div>

        <div class="item-show__body">
            <h1 class="item-title">{{ $item->product_name }}</h1>

            @if($item->brand_name)
            <div class="item-brand">{{ $item->brand_name }}</div>
            @endif

            <div class="item-price">¥{{ number_format($item->price) }}(税込)</div>

            <div class="item-actions">
                <div class="item-actions__top">
                    <form method="POST" action="{{ $isLiked ? route('items.unlike', $item->id) : route('items.like', $item->id) }}">
                        @csrf
                        @if($isLiked)
                        @method('DELETE')
                        @endif
                        <button class="like-btn" type="submit" @guest disabled title="ログインしてください" @endguest>
                            {!! $isLiked ? '★' : '☆' !!}
                            <span class="like-count">
                                {{ $item->likes_count ?? $item->likes->count() }}
                            </span>
                        </button>
                    </form>

                    <button class="comment-btn" type="submit">
                        💬
                        <span class="comment-count">
                            {{ $item->comments_count ?? $item->comments->count() }}
                        </span>
                    </button>
                </div>

                @if(!$item->order)
                <a class="buy-btn" href="{{ route('purchase.show', ['item_id'=>$item->id]) }}">購入手続きへ</a>
                @elseif($item->order->isPending())
                <button class="buy-btn" type="button" disabled>購入手続き中</button>
                @else
                <button class="buy-btn" type="button" disabled>売却済み</button>
                @endif
            </div>

            <div class="item-section">
                <h2 class="section-title">商品説明</h2>
                <p class="item-description">
                    {{ $item->description }}
                </p>
            </div>

            <div class="item-section">
                <h2 class="section-title">商品の情報</h2>
                <dl class="item-info">
                    <div class="item-info__row">
                        <dt>カテゴリ</dt>
                        <dd>
                            @if(isset($item->categories) && $item->categories->count())
                            @foreach($item->categories as $cat)
                            <span class="chip">{{ $cat->name }}</span>
                            @endforeach
                            @else
                            <span class="muted">未設定</span>
                            @endif
                        </dd>
                    </div>
                    <div class="item-info__row">
                        <dt>商品の状態</dt>
                        <dd>
                            {{ $item->condition }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="item-section">
                <h2 class="section-title">コメント({{ isset($item->comments) ? $item->comments->count() : 0 }}件)</h2>

                <div class="comments">
                    @forelse($item->comments as $comment)
                    @php
                    $avatar = optional(optional($comment->user)->profile)->image_url;
                    @endphp

                    <div class="comment {{ $avatar ? 'has-avatar' : 'no-avatar' }}">
                        <div class="comment__head">
                            <div class="comment__avatar">
                                @if($avatar)
                                <img
                                    src="{{ $avatar }}"
                                    alt="プロフィール画像"
                                    onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
                                @endif
                            </div>

                            <div class="comment__meta">
                                <span class="comment__user">{{ optional($comment->user)->name ?? '退会ユーザー' }}</span>
                                <span class="comment__time">{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                            </div>
                        </div>

                        <div class="comment__body">
                            {{ $comment->body }}
                        </div>
                    </div>
                    @empty
                    <p class="muted">まだコメントはありません。</p>
                    @endforelse
                </div>

                @auth
                <form class="comment-form" action="{{ route('items.comments.store', $item->id) }}" method="POST">
                    @csrf
                    <label class="comment-label" for="comment-body">商品へのコメント</label>
                    <textarea id="comment-body" name="body" rows="3" maxlength="255" required>{{ old('body') }}</textarea>
                    @error('body')
                    <p class="error">{{ $message }}</p>
                    @enderror
                    <button class="comment-submit-btn" type="submit">コメントを送信する</button>
                </form>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
