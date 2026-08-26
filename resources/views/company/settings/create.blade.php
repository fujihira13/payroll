@extends('layouts.app')
@section('title', 'ひな型を選ぶ')
@section('content')
<div class="page-header"><div><div class="eyebrow">SELECT TEMPLATE</div><h1>ひな型を選ぶ</h1><p class="lead">選んだ項目は自社用にコピーされ、表示名や項目を変更できます。</p></div></div>
<form class="card" method="post" action="{{ route('company.settings.store') }}">@csrf<div class="form-grid"><div class="field"><label>システム帳票テンプレート</label><select name="payslip_template_id" required><option value="">選択してください</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected(old('payslip_template_id')==$template->id)>{{ $template->name }}（{{ $template->items->count() }}項目）</option>@endforeach</select></div><div class="field"><label>自社での設定名</label><input name="name" value="{{ old('name', '標準給与明細') }}" required></div></div><div class="form-actions"><a class="button button-secondary" href="{{ route('company.settings.index') }}">戻る</a><button class="button button-primary">コピーして設定へ</button></div></form>
@endsection
