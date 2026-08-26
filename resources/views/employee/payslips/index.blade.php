@extends('layouts.app')
@section('title', '自分の給与明細')
@section('content')
<div class="page-header"><div><div class="eyebrow">MY PAYSLIPS</div><h1>自分の給与明細</h1><p class="lead">公開済みの月を選んで内容を確認できます。</p></div></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>対象月</th><th>明細名</th><th>差引支給額</th><th>閲覧状態</th><th>公開日</th><th></th></tr></thead><tbody>@forelse($payslips as $payslip)<tr><td><strong>{{ $payslip->batch->target_month->format('Y年n月') }}</strong></td><td>{{ $payslip->batch->name }}</td><td class="numeric"><strong>¥{{ number_format($payslip->net_amount) }}</strong></td><td><span class="badge {{ $payslip->first_viewed_at ? 'badge-success' : 'badge-warning' }}">{{ $payslip->first_viewed_at ? '閲覧済み' : '未閲覧' }}</span></td><td>{{ $payslip->batch->published_at?->format('Y/m/d') }}</td><td><a class="button button-primary button-small" href="{{ route('employee.payslips.show', $payslip) }}">明細を見る</a></td></tr>@empty<tr><td colspan="6" class="empty">公開された給与明細はまだありません。</td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$payslips" /></div>
@endsection
