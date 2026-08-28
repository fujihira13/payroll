<!doctype html>
<html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>システム管理者ログイン｜{{ config('app.name') }}</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body class="login-page"><main class="login-shell"><section class="login-card"><form class="login-form" method="post" action="{{ route('manage.login.store') }}">@csrf
<div class="eyebrow">SYSTEM ADMINISTRATION</div><h1>システム管理者ログイン</h1><p class="lead" style="margin-bottom:28px">企業管理用の専用ログイン画面です。</p>
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
<div class="field" style="margin-bottom:18px"><label>ログインID</label><input name="login_id" value="{{ old('login_id') }}" autocomplete="username" required autofocus></div>
<div class="field" style="margin-bottom:16px"><label>パスワード</label><input name="password" type="password" autocomplete="current-password" required></div>
<label class="checkbox" style="margin-bottom:24px"><input type="checkbox" name="remember" value="1">ログイン状態を保持する</label>
<button class="button button-primary" style="width:100%">ログイン</button></form></section></main></body></html>
