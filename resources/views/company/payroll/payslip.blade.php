@extends('layouts.app')
@section('title', $payslip->employee->name.'さんの明細')
@section('content')
<div class="page-header"><div><div class="eyebrow">EMPLOYEE PAYSLIP</div><h1>{{ $payslip->employee->name }}さんの明細</h1><p class="lead">{{ $batch->target_month->format('Y年n月') }} ／ 社員番号 {{ $payslip->employee->employee_number }}</p></div><a class="button button-secondary" href="{{ route('company.payroll.show', $batch) }}">一覧へ戻る</a></div>
@php($groups = collect($payslip->details)->groupBy('category'))
<article class="payslip-sheet">@foreach(['earning'=>'支給','deduction'=>'控除','information'=>'勤怠・その他'] as $key=>$label)@if($groups->has($key))<section style="margin-bottom:24px"><h3>{{ $label }}</h3><table><tbody>@foreach($groups[$key] as $item)<tr><td>{{ $item['label'] }}</td><td class="numeric">@if($item['data_type']==='amount')¥{{ number_format((float)$item['value']) }}@else{{ $item['value'] }}@endif</td></tr>@endforeach</tbody></table></section>@endif @endforeach
<div class="summary-grid"><div>総支給額<strong>¥{{ number_format($payslip->gross_amount) }}</strong></div><div>控除合計<strong>¥{{ number_format($payslip->deduction_amount) }}</strong></div><div>差引支給額<strong>¥{{ number_format($payslip->net_amount) }}</strong></div></div></article>
@endsection
