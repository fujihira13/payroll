@extends('layouts.app')
@section('title', $administrator->exists ? '会社管理者を編集' : '会社管理者を登録')
@section('content')
<div class="page-header"><div><div class="eyebrow">ADMIN ACCOUNT</div><h1>{{ $administrator->exists ? '会社管理者を編集' : '会社管理者を登録' }}</h1></div></div>
<form class="card" method="post" action="{{ $administrator->exists ? route('system.administrators.update', $administrator) : route('system.administrators.store') }}">@csrf @if($administrator->exists) @method('put') @endif
<div class="form-grid">
<div class="field"><label>所属会社</label><select name="company_id" required><option value="">選択してください</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(old('company_id', $administrator->company_id) == $company->id)>{{ $company->name }}</option>@endforeach</select></div>
<div class="field"><label>氏名</label><input name="name" value="{{ old('name', $administrator->name) }}" required></div>
<div class="field"><label>メールアドレス</label><input type="email" name="email" value="{{ old('email', $administrator->email) }}" required></div>
<div class="field"><label>パスワード</label><input type="password" name="password" @required(!$administrator->exists)><span class="help">{{ $administrator->exists ? '変更しない場合は空欄' : '8文字以上' }}</span></div>
<div class="field field-full"><input type="hidden" name="is_active" value="0"><label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $administrator->exists ? $administrator->is_active : true))>ログインを許可する</label></div>
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('system.administrators.index') }}">戻る</a><button class="button button-primary">保存</button></div></form>
@endsection
