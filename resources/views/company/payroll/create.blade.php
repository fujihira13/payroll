@extends('layouts.app')
@section('title', '給与明細を作成')
@section('content')
<div class="page-header"><div><div class="eyebrow">NEW PAYROLL</div><h1>給与明細を作成</h1><p class="lead">まず対象月と明細設定を選び、下書きを作成します。</p></div></div>
@if($settings->isEmpty())<div class="alert alert-error">利用中の明細項目設定がありません。<a href="{{ route('company.settings.create') }}">ひな型を選択</a>してください。</div>@else
<form class="card" method="post" action="{{ route('company.payroll.store') }}">@csrf<div class="form-grid"><div class="field"><label>対象月</label><input type="month" name="target_month" value="{{ old('target_month', now()->format('Y-m')) }}" required></div><div class="field"><label>名称</label><input name="name" value="{{ old('name', now()->format('Y年n月').' 給与明細') }}" required></div><div class="field field-full"><label>明細項目設定</label><select name="company_payslip_setting_id" required>@foreach($settings as $setting)<option value="{{ $setting->id }}">{{ $setting->name }}（{{ $setting->items->where('is_active', true)->count() }}項目）</option>@endforeach</select></div></div><div class="form-actions"><a class="button button-secondary" href="{{ route('company.payroll.index') }}">戻る</a><button class="button button-primary">下書きを作成</button></div></form>
@endif
@endsection
