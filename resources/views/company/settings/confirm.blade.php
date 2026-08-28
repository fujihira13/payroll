@extends('layouts.app')
@section('title', '帳票設定確認')
@section('content')
<div class="page-header"><div><div class="eyebrow">STEP 3 / 3</div><h1>帳票設定を確認する</h1><p class="lead">{{ $data['name'] }} ／ {{ $template->name }}</p></div></div>
<form method="post" action="{{ route('company.settings.store') }}">@csrf<input type="hidden" name="payslip_template_id" value="{{ $data['payslip_template_id'] }}"><input type="hidden" name="name" value="{{ $data['name'] }}"><input type="hidden" name="layout_type" value="{{ $data['layout_type'] }}">
<section class="card"><div class="table-wrap"><table><thead><tr><th>項目</th><th>分類</th><th>スロット</th><th>使用</th></tr></thead><tbody>@foreach($data['items'] as $index=>$item)<tr><td>{{ $item['label'] }}@foreach($item as $key=>$value)<input type="hidden" name="items[{{ $index }}][{{ $key }}]" value="{{ $value }}">@endforeach</td><td>{{ \App\Enums\PayslipItemCategory::from($item['category'])->label() }}</td><td>{{ $slots[$item['slot_code'] ?? ''] ?? '未割当' }}</td><td>{{ ($item['is_active'] ?? false) ? '使用' : '未使用' }}</td></tr>@endforeach</tbody></table></div></section><div class="form-actions"><a class="button button-secondary" href="{{ route('company.settings.create') }}">最初からやり直す</a><button class="button button-primary">この内容で作成</button></div></form>
@endsection
