@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<div class="verify-form">
    <p class="verify-message">登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。</p>

        <a class="verify-btn" href="http://localhost:8025" target="_blank" rel="noopener">認証はこちらから</a>

    <form action="{{ route('verification.send') }}" method="POST">
        @csrf
        <button class="verify-link" type="submit">認証メールを再送する</button>
    </form>
</div>
@endsection
