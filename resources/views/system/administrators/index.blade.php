@extends('layouts.manage')
@section('title', 'システム管理者一覧')
@section('content')
<div class="page-header"><div><div class="eyebrow">SYSTEM ADMINS</div><h1>システム管理者一覧</h1><p class="lead">全システム管理者は同じ権限を持ちます。</p></div><a class="button button-primary" href="{{ route('manage.admins.create') }}">管理者を登録</a></div>
<div class="card"><form class="search" method="get"><input name="q" value="{{ request('q') }}" placeholder="氏名またはログインID"><button class="button button-secondary">検索</button></form>
<div class="table-wrap" style="margin-top:18px"><table><thead><tr><th>ログインID</th><th>氏名</th><th>失敗回数</th><th>ロック</th><th>初回変更</th><th>最終ログイン</th><th></th></tr></thead><tbody>
@forelse($administrators as $administrator)<tr><td>{{ $administrator->login_id }}</td><td><strong>{{ $administrator->name }}</strong></td><td>{{ $administrator->try_count }}回</td><td>{{ $administrator->lock_status ? 'ロック中' : '—' }}</td><td>{{ $administrator->force_password_change ? '必要' : '完了' }}</td><td>{{ $administrator->last_login_at?->format('Y/m/d H:i') ?? '—' }}</td><td><div class="actions"><a class="button button-secondary button-small" href="{{ route('manage.admins.edit', $administrator) }}">編集</a><form method="post" action="{{ route('manage.admins.reset-password', $administrator) }}">@csrf<button class="button button-secondary button-small">パスワードリセット</button></form>@if($administrator->lock_status)<form method="post" action="{{ route('manage.admins.unlock', $administrator) }}">@csrf<button class="button button-secondary button-small">ロック解除</button></form>@endif<form method="post" action="{{ route('manage.admins.destroy', $administrator) }}" onsubmit="return confirm('管理者を停止しますか？')">@csrf @method('delete')<button class="button button-danger button-small">削除</button></form></div></td></tr>
@empty<tr><td colspan="7" class="empty">システム管理者がいません。</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$administrators" /></div>
@endsection
