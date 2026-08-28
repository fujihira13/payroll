@extends('layouts.app')
@section('title', 'ひな型を選ぶ')
@section('content')
<div class="page-header"><div><div class="eyebrow">STEP 1 / 3</div><h1>ベース帳票を選ぶ</h1><p class="lead">会社で使用する帳票の元レイアウトを選択します。</p></div></div>
<form class="card" method="post" action="{{ route('company.settings.prepare') }}">@csrf<div class="form-grid"><div class="field"><label>ベース帳票</label><select name="payslip_template_id" required><option value="">選択してください</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected(old('payslip_template_id')==$template->id)>{{ $template->name }}（{{ $template->items->count() }}項目）</option>@endforeach</select></div><div class="field"><label>自社での帳票名</label><input name="name" value="{{ old('name', '標準給与明細') }}" required></div></div><div class="form-actions"><a class="button button-secondary" href="{{ route('company.settings.index') }}">戻る</a><button class="button button-primary">次へ（項目割当）</button></div></form>
@endsection
