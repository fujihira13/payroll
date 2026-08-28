@extends('layouts.manage')
@section('title', $administrator->exists ? 'システム管理者を編集' : 'システム管理者を登録')
@section('content')
<div class="page-header"><div><div class="eyebrow">ADMIN ACCOUNT</div><h1>{{ $administrator->exists ? 'システム管理者を編集' : 'システム管理者を登録' }}</h1><p class="lead">新規登録時は12文字の仮パスワードを自動生成します。</p></div></div>
<form class="card" method="post" action="{{ $administrator->exists ? route('manage.admins.update', $administrator) : route('manage.admins.store') }}">@csrf @if($administrator->exists) @method('put') @endif<div class="form-grid">
<div class="field"><label>ログインID</label><input name="login_id" value="{{ old('login_id', $administrator->login_id) }}" required><span class="help">半角英数字・記号、4〜20文字</span></div><div class="field"><label>氏名</label><input name="name" value="{{ old('name', $administrator->name) }}" required></div>
@if($administrator->exists)<div class="field field-full"><input type="hidden" name="is_active" value="0"><label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $administrator->is_active))>ログインを許可する</label></div>@endif
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('manage.admins.index') }}">戻る</a><button class="button button-primary">保存</button></div></form>
@endsection
