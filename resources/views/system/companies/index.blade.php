@extends('layouts.app')
@section('title', '会社管理')
@section('content')
<div class="page-header"><div><div class="eyebrow">COMPANIES</div><h1>会社管理</h1><p class="lead">利用会社の登録と稼働状態を管理します。</p></div><a class="button button-primary" href="{{ route('system.companies.create') }}">会社を登録</a></div>
<div class="card">
<form class="search" method="get"><input name="q" value="{{ request('q') }}" placeholder="会社名または会社コード"><button class="button button-secondary">検索</button></form>
<div class="table-wrap" style="margin-top:18px"><table><thead><tr><th>会社コード</th><th>会社名</th><th>利用者数</th><th>状態</th><th></th></tr></thead><tbody>
@forelse($companies as $company)<tr><td>{{ $company->code }}</td><td><strong>{{ $company->name }}</strong></td><td>{{ $company->users_count }}</td><td><span class="badge {{ $company->is_active ? 'badge-success' : 'badge-muted' }}">{{ $company->is_active ? '利用中' : '停止中' }}</span></td><td><div class="actions"><a class="button button-secondary button-small" href="{{ route('system.companies.edit', $company) }}">編集</a><form method="post" action="{{ route('system.companies.destroy', $company) }}" onsubmit="return confirm('会社を停止しますか？')">@csrf @method('delete')<button class="button button-danger button-small">停止</button></form></div></td></tr>
@empty<tr><td colspan="5" class="empty">会社がまだ登録されていません。</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$companies" /></div>
@endsection
