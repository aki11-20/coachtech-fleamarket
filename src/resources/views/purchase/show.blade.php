@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('header_search')
<form class="search" action="{{ route('items.index') }}" method="GET">
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
<div class="purchase-wrap">
    <section class="purchase-info">
        <div class="purchase-item">
            <div class="item-image">
                @php $fallback = asset('images/placeholder.png'); @endphp
                @if ($item->image)
                <img
                    src="{{ asset($item->image) }}"
                    alt="{{ $item->product_name }}"
                    data-fallback="{{ $fallback }}"
                    onerror="this.onerror=null;this.src=this.dataset.fallback">
                @else
                <span class="item-image__ph">商品画像</span>
                @endif
            </div>

            <div class="item-date">
                <h1 class="item-title">{{ $item->product_name }}</h1>
                <div class="item-price">¥ {{ number_format($item->price) }}</div>
            </div>
        </div>

        @if ($errors->any())
        <ul class="form-errors">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        @endif

        @php
        $addressSource = (isset($shipping) && is_array($shipping)) ? (object) $shipping : $profile;
        @endphp

        <div class="purchase-block">
            @php
            $selectedPayment = session('purchase.payment_type.' . $item->id, old('payment_type', ''));
            @endphp
            <form action="{{ route('purchase.payment.update', ['item_id' => $item->id]) }}" method="POST">
                @csrf
                <h2 class="block-title">支払い方法</h2>

                <div class="purchase-field">
                    <select class="select" name="payment_type" id="payment_type" required onchange="this.form.submit()">
                        <option value="" disabled {{ $selectedPayment ? '' : 'selected' }}>選択してください</option>
                        <option value="convenience" {{ $selectedPayment === 'convenience' ? 'selected' : '' }}>コンビニ支払い</option>
                        <option value="card" {{ $selectedPayment === 'card' ? 'selected' : '' }}>カード支払い</option>
                    </select>
                </div>
            </form>
        </div>

        <form action="{{ route('purchase.store', ['item_id' => $item->id]) }}" method="POST" id="purchase-form">
            @csrf
            <input type="hidden" name="address_mode" value="saved">
            <input type="hidden" name="payment_type" value="{{ $selectedPayment ?? '' }}">
            
            <div class="purchase-block">
                <h2 class="block-title">配送先</h2>
                <div class="address-head">
                    <a class="link" href="{{ route('purchase.address', ['item_id' => $item->id]) }}">変更する</a>
                </div>
                <div class="address">
                    <div>〒 {{ $addressSource->postal_code ?? 'XXX-YYYY' }}</div>
                    <div>{{ trim(($addressSource->address ?? 'ここには住所と建物が入ります') . ' ' . ($addressSource->building ?? '')) }}</div>
                </div>
            </div>
        </form>
    </section>

    <aside class="purchase-summary">
        <div class="summary">
            <div class="row">
                <div class="label">商品代金</div>
                <div class="value">¥ {{ number_format($item->price) }}</div>
            </div>
            <div class="row">
                <div class="label">支払い方法</div>
                <div class="value" id="summary-payment">
                    @php
                    $pt = $selectedPayment ?? '';
                    $ptLabel = $pt === 'card' ? 'カード支払い' : ($pt === 'convenience' ? 'コンビニ支払い' : '未選択');
                    @endphp
                    {{ $ptLabel }}
                </div>
            </div>
        </div>

        <button class="purchase-btn" type="submit" form="purchase-form">購入する</button>
    </aside>
</div>
@endsection