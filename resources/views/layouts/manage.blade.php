<!doctype html>
<html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title', 'システム管理')｜{{ config('app.name') }}</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body><div class="app-shell"><aside class="sidebar"><a class="brand" href="{{ route('manage.companies.index') }}"><span class="brand-mark">管</span><span class="brand-text">システム管理</span></a>
<nav aria-label="システム管理メニュー"><div class="nav-label">SYSTEM</div>
<a class="nav-link {{ request()->routeIs('manage.companies.*') ? 'active' : '' }}" href="{{ route('manage.companies.index') }}">企業一覧</a>
<a class="nav-link {{ request()->routeIs('manage.admins.*') ? 'active' : '' }}" href="{{ route('manage.admins.index') }}">管理者一覧</a>
<a class="nav-link {{ request()->routeIs('manage.templates.*') ? 'active' : '' }}" href="{{ route('manage.templates.index') }}">ベース帳票管理</a>
<a class="nav-link {{ request()->routeIs('manage.password.*') ? 'active' : '' }}" href="{{ route('manage.password.edit') }}">パスワード変更</a></nav>
<div class="sidebar-user"><strong>{{ auth('admin')->user()->name }}</strong><span>システム管理者</span><form method="post" action="{{ route('manage.logout') }}">@csrf<button class="logout-button">ログアウト</button></form></div></aside>
<main class="main"><header class="topbar"><span class="topbar-context">SYSTEM ADMINISTRATION</span><span class="topbar-context">{{ now()->format('Y年n月j日') }}</span></header><div class="content">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert alert-error">{{ session('warning') }}</div>@endif
@if(session('temporary_password'))<div class="alert alert-success"><strong>仮パスワード：{{ session('temporary_password') }}</strong><br>この画面を離れると再表示できません。安全な方法で本人へ伝えてください。</div>@endif
@if($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</div></main></div></body></html>
