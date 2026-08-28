<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン｜{{ config('app.name') }}</title><link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="login-page">
    <section class="login-panel">
        <form class="login-form" method="post" action="{{ route('login.store') }}">
            @csrf
            <div class="brand" style="color:var(--ink); margin-bottom:42px"><span class="brand-mark" style="color:#fff">給</span><span class="brand-text">給与明細ポータル</span></div>
            <div class="eyebrow">SECURE SIGN IN</div><h1>ログイン</h1>
            <p class="lead" style="margin-bottom:28px">会社コードと会社から発行されたログインIDでログインしてください。</p>
            @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
            <div class="field" style="margin-bottom:18px"><label for="company_code">会社コード</label><input id="company_code" name="company_code" value="{{ old('company_code') }}" required autofocus></div>
            <div class="field" style="margin-bottom:18px"><label for="login_id">ログインID</label><input id="login_id" name="login_id" value="{{ old('login_id') }}" autocomplete="username" required></div>
            <div class="field" style="margin-bottom:16px"><label for="password">パスワード</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
            <label class="checkbox" style="margin-bottom:24px"><input type="checkbox" name="remember" value="1">ログイン状態を保持する</label>
            <button class="button button-primary" style="width:100%" type="submit">ログイン</button>
        </form>
    </section>
    <section class="login-visual">
        <div><div class="eyebrow" style="color:#68c5b8">PAYROLL LEDGER</div><h1>給与明細を、<br>正しく届ける。</h1><p>会社ごとの明細設定、公開予約、通知、閲覧状況までを一つの流れで管理します。</p></div>
        <div class="login-ledger"><div><small>01</small>明細を作成</div><div><small>02</small>確認して公開</div><div><small>03</small>社員が閲覧</div></div>
    </section>
</main>
</body></html>
