@extends('layouts.app')
@section('title', '給与明細')
@section('content')
<div class="page-header"><div><div class="eyebrow">PAYROLL BATCHES</div><h1>給与明細</h1><p class="lead">作成から公開、閲覧状況までを月単位で管理します。</p></div><a class="button button-primary" href="{{ route('company.payroll.create') }}">明細を作成</a></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>対象月</th><th>名称</th><th>状態</th><th>明細数</th><th>閲覧済み</th><th>公開日時</th><th></th></tr></thead><tbody>
@forelse($batches as $batch)<tr><td><strong>{{ $batch->target_month->format('Y年n月') }}</strong></td><td>{{ $batch->name }}</td><td><span class="badge {{ $batch->status === \App\Enums\PayrollBatchStatus::Published ? 'badge-success' : ($batch->status === \App\Enums\PayrollBatchStatus::Scheduled ? 'badge-warning' : 'badge-muted') }}">{{ $batch->status->label() }}</span></td><td>{{ $batch->payslips_count }}</td><td>{{ $batch->viewed_count }} / {{ $batch->payslips_count }}</td><td>{{ $batch->published_at?->format('Y/m/d H:i') ?? $batch->scheduled_for?->format('Y/m/d H:i') ?? '—' }}</td><td><a class="button button-secondary button-small" href="{{ route('company.payroll.show', $batch) }}">詳細</a></td></tr>
@empty<tr><td colspan="7" class="empty">給与明細がありません。最初の下書きを作成してください。</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$batches" /></div>
@endsection
