@extends('layouts.app')
@section('title', '会社管理者')
@section('content')
<div class="page-header"><div><div class="eyebrow">ADMINISTRATORS</div><h1>会社管理者</h1><p class="lead">各社の人事・給与担当者を複数登録できます。</p></div><a class="button button-primary" href="{{ route('system.administrators.create') }}">管理者を登録</a></div>
<div class="card"><form class="search" method="get"><input name="q" value="{{ request('q') }}" placeholder="氏名またはメール"><button class="button button-secondary">検索</button></form>
<div class="table-wrap" style="margin-top:18px"><table><thead><tr><th>氏名</th><th>会社</th><th>メール</th><th>ログイン失敗</th><th>最終ログイン</th><th>状態</th><th></th></tr></thead><tbody>
@forelse($administrators as $administrator)<tr><td><strong>{{ $administrator->name }}</strong></td><td>{{ $administrator->company?->name }}</td><td>{{ $administrator->email }}</td><td>{{ $administrator->login_failure_count }}回</td><td>{{ $administrator->last_login_at?->format('Y/m/d H:i') ?? '—' }}</td><td><span class="badge {{ $administrator->is_active ? 'badge-success' : 'badge-muted' }}">{{ $administrator->is_active ? '利用中' : '停止中' }}</span></td><td><div class="actions"><a class="button button-secondary button-small" href="{{ route('system.administrators.edit', $administrator) }}">編集</a><form method="post" action="{{ route('system.administrators.destroy', $administrator) }}" onsubmit="return confirm('管理者を停止しますか？')">@csrf @method('delete')<button class="button button-danger button-small">停止</button></form></div></td></tr>
@empty<tr><td colspan="7" class="empty">会社管理者がまだ登録されていません。</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$administrators" /></div>
@endsection
