@extends('layouts.app')
@section('title', 'メール管理')
@section('content')
<div class="page-header"><div><div class="eyebrow">EMAIL TEMPLATES</div><h1>メール管理</h1><p class="lead">用途ごとに複数の通知文面と送信者を管理します。</p></div><a class="button button-primary" href="{{ route('company.email-templates.create') }}">テンプレートを登録</a></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>テンプレート名</th><th>用途</th><th>送信者</th><th>件名</th><th>状態</th><th></th></tr></thead><tbody>
@forelse($templates as $template)<tr><td><strong>{{ $template->name }}</strong></td><td>{{ $template->type === 'payslip_published' ? '明細公開' : '社員連絡' }}</td><td>{{ $template->sender_name ?: 'システム既定' }}<br><span class="help">{{ $template->sender_address }}</span></td><td>{{ $template->subject }}</td><td>{{ $template->is_active ? '使用中' : '停止中' }}</td><td><div class="actions"><a class="button button-secondary button-small" href="{{ route('company.email-templates.edit', $template) }}">編集</a><form method="post" action="{{ route('company.email-templates.destroy', $template) }}" onsubmit="return confirm('削除しますか？')">@csrf @method('delete')<button class="button button-danger button-small">削除</button></form></div></td></tr>
@empty<tr><td colspan="6" class="empty">メールテンプレートがありません。</td></tr>@endforelse
</tbody></table></div></div>
@endsection
