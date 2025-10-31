<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH FleaMarket</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__logo">
            <img class="logo" alt="COACHTECH" src="{{ asset('images/logo.svg') }}">
        </div>
        @if (request()->is('login') || request()->is('register') || request()->is('email/verify'))
        @else

        <div class="header__search">
            @yield('header_search')
        </div>
        <div class="header__actions">
            @hasSection('header_actions')
            @yield('header_actions')
            @else
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
                            <button class="header-link" type="submit">ログアウト</button>
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
            @endif
        </div>
        @endif
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>