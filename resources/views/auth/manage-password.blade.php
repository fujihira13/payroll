@extends('layouts.manage')
@section('title', 'パスワード変更')
@section('content')
<div class="page-header"><div><div class="eyebrow">ACCOUNT SECURITY</div><h1>パスワード変更</h1><p class="lead">システム管理者のパスワードを変更します。</p></div></div>
<form class="card" method="post" action="{{ route('manage.password.update') }}" style="max-width:680px">@csrf @method('put')<div class="form-grid"><div class="field field-full"><label>現在のパスワード</label><input type="password" name="current_password" required></div><div class="field"><label>新しいパスワード</label><input type="password" name="password" required></div><div class="field"><label>確認</label><input type="password" name="password_confirmation" required></div></div><div class="form-actions"><button class="button button-primary">変更</button></div></form>
@endsection
