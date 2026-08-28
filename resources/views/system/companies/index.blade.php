@extends('layouts.manage')
@section('title', '企業一覧')
@section('content')
<div class="page-header"><div><div class="eyebrow">COMPANIES</div><h1>企業一覧</h1><p class="lead">利用企業を管理します。企業管理者と一般社員は共通ログイン画面を使用します。</p></div><a class="button button-primary" href="{{ route('manage.companies.create') }}">企業を登録</a></div>
<div class="card"><form class="search" method="get"><input name="q" value="{{ request('q') }}" placeholder="企業名または企業コード"><button class="button button-secondary">検索</button></form>
<div class="table-wrap" style="margin-top:18px"><table><thead><tr><th>企業コード</th><th>企業名</th><th>共通ログインURL</th><th>利用者数</th><th>状態</th><th></th></tr></thead><tbody>
@forelse($companies as $company)<tr><td>{{ $company->code }}</td><td><strong>{{ $company->name }}</strong></td><td><a href="{{ route('login') }}" target="_blank">{{ route('login') }}</a></td><td>{{ $company->users_count }}</td><td><span class="badge {{ $company->is_active ? 'badge-success' : 'badge-muted' }}">{{ $company->is_active ? '利用中' : '停止中' }}</span></td><td><div class="actions"><a class="button button-secondary button-small" href="{{ route('manage.companies.edit', $company) }}">編集</a><form method="post" action="{{ route('manage.companies.destroy', $company) }}" onsubmit="return confirm('企業を停止しますか？')">@csrf @method('delete')<button class="button button-danger button-small">停止</button></form></div></td></tr>
@empty<tr><td colspan="6" class="empty">企業がまだ登録されていません。</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$companies" /></div>
@endsection
