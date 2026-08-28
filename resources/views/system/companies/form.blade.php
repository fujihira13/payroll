@extends('layouts.manage')
@section('title', $company->exists ? '企業を編集' : '企業を登録')
@section('content')
<div class="page-header"><div><div class="eyebrow">COMPANY PROFILE</div><h1>{{ $company->exists ? '企業を編集' : '企業を登録' }}</h1></div></div>
<form class="card" method="post" action="{{ $company->exists ? route('manage.companies.update', $company) : route('manage.companies.store') }}">@csrf @if($company->exists) @method('put') @endif<div class="form-grid">
<div class="field"><label>企業コード</label><input name="code" value="{{ old('code', $company->code) }}" required></div>
<div class="field"><label>ログインURL識別子</label><input name="login_slug" value="{{ old('login_slug', $company->login_slug ?? $company->code) }}" required><span class="help">/login/識別子 のURLになります。</span></div>
<div class="field"><label>企業名</label><input name="name" value="{{ old('name', $company->name) }}" required></div><div class="field"><label>代表メール</label><input name="email" type="email" value="{{ old('email', $company->email) }}"></div>
<div class="field"><label>電話番号</label><input name="phone" value="{{ old('phone', $company->phone) }}"></div><div class="field field-full"><label>住所</label><textarea name="address">{{ old('address', $company->address) }}</textarea></div>
<div class="field field-full"><input type="hidden" name="is_active" value="0"><label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $company->exists ? $company->is_active : true))>この企業を利用可能にする</label></div>
@unless($company->exists)<div class="field field-full"><h2>初期社員管理者</h2><p class="help">最初の社員管理者（permission 9）を同時に作成し、12文字の仮パスワードを1回だけ表示します。</p></div><div class="field"><label>管理者ログインID</label><input name="initial_admin_login_id" value="{{ old('initial_admin_login_id') }}" required></div><div class="field"><label>管理者氏名</label><input name="initial_admin_name" value="{{ old('initial_admin_name') }}" required></div><div class="field field-full"><label>管理者メール</label><input type="email" name="initial_admin_email" value="{{ old('initial_admin_email') }}" required></div>@endunless
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('manage.companies.index') }}">戻る</a><button class="button button-primary">保存</button></div></form>
@endsection
