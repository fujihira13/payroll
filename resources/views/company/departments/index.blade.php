@extends('layouts.app')
@section('title', '部署管理')
@section('content')
<div class="page-header"><div><div class="eyebrow">DEPARTMENTS</div><h1>部署管理</h1><p class="lead">社員の所属先を管理します。</p></div><a class="button button-primary" href="{{ route('company.departments.create') }}">部署を登録</a></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>部署コード</th><th>部署名</th><th>部署名カナ</th><th>所属人数</th><th></th></tr></thead><tbody>@forelse($departments as $department)<tr><td>{{ $department->code }}</td><td><strong>{{ $department->name }}</strong></td><td>{{ $department->name_kana ?: '—' }}</td><td>{{ $department->users_count }}</td><td><div class="actions"><a class="button button-secondary button-small" href="{{ route('company.departments.edit', $department) }}">編集</a><form method="post" action="{{ route('company.departments.destroy', $department) }}" onsubmit="return confirm('削除しますか？')">@csrf @method('delete')<button class="button button-danger button-small">削除</button></form></div></td></tr>@empty<tr><td colspan="5" class="empty">部署がまだありません。</td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$departments" /></div>
@endsection
