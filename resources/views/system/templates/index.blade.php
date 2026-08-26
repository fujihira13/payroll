@extends('layouts.app')
@section('title', '帳票テンプレート')
@section('content')
<div class="page-header"><div><div class="eyebrow">BASE TEMPLATES</div><h1>帳票テンプレート</h1><p class="lead">各会社が選択する給与明細の共通項目を定義します。</p></div><a class="button button-primary" href="{{ route('system.templates.create') }}">テンプレートを作成</a></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>テンプレート名</th><th>項目数</th><th>説明</th><th>状態</th><th></th></tr></thead><tbody>
@forelse($templates as $template)<tr><td><strong>{{ $template->name }}</strong></td><td>{{ $template->items_count }}</td><td>{{ Str::limit($template->description, 50) }}</td><td><span class="badge {{ $template->is_active ? 'badge-success' : 'badge-muted' }}">{{ $template->is_active ? '提供中' : '停止中' }}</span></td><td><div class="actions"><a class="button button-secondary button-small" href="{{ route('system.templates.edit', $template) }}">編集</a><form method="post" action="{{ route('system.templates.destroy', $template) }}" onsubmit="return confirm('削除しますか？')">@csrf @method('delete')<button class="button button-danger button-small">削除</button></form></div></td></tr>
@empty<tr><td colspan="5" class="empty">テンプレートがありません。最初の帳票を作成してください。</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$templates" /></div>
@endsection
