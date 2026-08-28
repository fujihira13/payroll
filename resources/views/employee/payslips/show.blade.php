@extends('layouts.app')
@section('title', $payslip->batch->target_month->format('Y年n月').' 給与明細')
@section('content')
<div class="page-header"><div><div class="eyebrow">PAYSLIP / {{ $payslip->batch->target_month->format('Y-m') }}</div><h1>{{ $payslip->batch->target_month->format('Y年n月') }} 給与明細</h1></div><div class="toolbar"><a class="button button-secondary" href="{{ route('employee.payslips.index') }}">一覧へ戻る</a><a class="button button-primary" href="{{ route('employee.payslips.pdf', $payslip) }}">PDFをダウンロード</a></div></div>
<article class="payslip-sheet"><header class="payslip-head"><div><div class="eyebrow">{{ $payslip->batch->company->code }}</div><h2>{{ $payslip->batch->company->name }}</h2></div><div style="text-align:right"><strong>{{ $payslip->employee->name }} 様</strong><div class="help">社員番号 {{ $payslip->employee->employee_number }} ／ {{ $payslip->employee->department?->name ?? '所属未設定' }}</div></div></header>
@php($items = collect($payslip->details))
@php($hasSlots = $items->contains(fn($item) => filled($item['slot_code'] ?? null)))
@if($hasSlots)
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
@foreach(['left'=>'左列','right'=>'右列'] as $prefix=>$columnLabel)<section><h3>{{ $columnLabel }}</h3><table><tbody>@foreach($items->filter(fn($item) => str_starts_with($item['slot_code'] ?? '', $prefix.'_'))->sortBy('slot_code') as $item)<tr><td>{{ $item['label'] }}</td><td class="numeric">@if($item['data_type']==='amount')¥{{ number_format((float)$item['value']) }}@else{{ $item['value'] }}@endif</td></tr>@endforeach</tbody></table></section>@endforeach
</div>
@else
@php($groups = $items->groupBy('category'))
@foreach(['earning'=>'支給','deduction'=>'控除','information'=>'勤怠・その他'] as $key => $label)
@if($groups->has($key))<section style="margin-bottom:24px"><h3>{{ $label }}</h3><table><tbody>@foreach($groups[$key] as $item)<tr><td>{{ $item['label'] }}</td><td class="numeric">@if($item['data_type']==='amount')¥{{ number_format((float)$item['value']) }}@else{{ $item['value'] }}@endif</td></tr>@endforeach</tbody></table></section>@endif
@endforeach
@endif
<div class="payslip-summary"><div><span class="help">総支給額</span><strong>¥{{ number_format($payslip->gross_amount) }}</strong></div><div><span class="help">控除合計</span><strong>¥{{ number_format($payslip->deduction_amount) }}</strong></div><div><span class="help">差引支給額</span><strong>¥{{ number_format($payslip->net_amount) }}</strong></div></div>
<p class="help">公開日時：{{ $payslip->batch->published_at->format('Y年n月j日 H:i') }}</p></article>
@endsection
