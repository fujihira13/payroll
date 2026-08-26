@extends('layouts.app')
@section('title', '明細項目設定')
@section('content')
<div class="page-header"><div><div class="eyebrow">PAYSLIP SETTINGS</div><h1>明細項目設定</h1><p class="lead">システム管理者のひな型をコピーして、自社用に調整します。</p></div><a class="button button-primary" href="{{ route('company.settings.create') }}">ひな型を選ぶ</a></div>
@forelse($settings as $setting)<article class="card"><div class="page-header"><div><h2>{{ $setting->name }}</h2><p class="help">元のひな型：{{ $setting->template->name }} ／ {{ $setting->items->where('is_active', true)->count() }}項目</p></div><div class="toolbar"><span class="badge {{ $setting->is_active ? 'badge-success' : 'badge-muted' }}">{{ $setting->is_active ? '現在使用中' : '停止中' }}</span><a class="button button-secondary button-small" href="{{ route('company.settings.edit', $setting) }}">項目を編集</a></div></div></article>@empty<div class="card empty">自社用の明細設定がありません。「ひな型を選ぶ」から作成してください。</div>@endforelse
@endsection
