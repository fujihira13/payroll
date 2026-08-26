@extends('layouts.app')
@section('title', $company->exists ? '会社を編集' : '会社を登録')
@section('content')
<div class="page-header"><div><div class="eyebrow">COMPANY PROFILE</div><h1>{{ $company->exists ? '会社を編集' : '会社を登録' }}</h1></div></div>
<form class="card" method="post" action="{{ $company->exists ? route('system.companies.update', $company) : route('system.companies.store') }}">@csrf @if($company->exists) @method('put') @endif
<div class="form-grid">
<div class="field"><label for="code">会社コード</label><input id="code" name="code" value="{{ old('code', $company->code) }}" required></div>
<div class="field"><label for="name">会社名</label><input id="name" name="name" value="{{ old('name', $company->name) }}" required></div>
<div class="field"><label for="email">代表メール</label><input id="email" name="email" type="email" value="{{ old('email', $company->email) }}"></div>
<div class="field"><label for="phone">電話番号</label><input id="phone" name="phone" value="{{ old('phone', $company->phone) }}"></div>
<div class="field field-full"><label for="address">住所</label><textarea id="address" name="address">{{ old('address', $company->address) }}</textarea></div>
<div class="field field-full"><input type="hidden" name="is_active" value="0"><label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $company->exists ? $company->is_active : true))>この会社を利用可能にする</label></div>
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('system.companies.index') }}">戻る</a><button class="button button-primary">保存</button></div></form>
@endsection
