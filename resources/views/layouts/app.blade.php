<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ダッシュボード')｜{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark">給</span>
            <span class="brand-text">給与明細ポータル</span>
        </a>
        <nav aria-label="メインメニュー">
            <div class="nav-label">OVERVIEW</div>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">ダッシュボード</a>
            @if(auth()->user()->canManageCompany())
                <div class="nav-label">PAYROLL</div>
                <a class="nav-link {{ request()->routeIs('company.payroll.*') ? 'active' : '' }}" href="{{ route('company.payroll.index') }}">給与明細</a>
                <a class="nav-link {{ request()->routeIs('company.settings.*') ? 'active' : '' }}" href="{{ route('company.settings.index') }}">明細項目設定</a>
                <a class="nav-link {{ request()->routeIs('company.email-templates.*') ? 'active' : '' }}" href="{{ route('company.email-templates.index') }}">メール管理</a>
                <div class="nav-label">PEOPLE</div>
                <a class="nav-link {{ request()->routeIs('company.employees.*') ? 'active' : '' }}" href="{{ route('company.employees.index') }}">社員管理</a>
                <a class="nav-link {{ request()->routeIs('company.departments.*') ? 'active' : '' }}" href="{{ route('company.departments.index') }}">部署管理</a>
            @endif
            <div class="nav-label">MY ACCOUNT</div>
            <a class="nav-link {{ request()->routeIs('employee.payslips.*') ? 'active' : '' }}" href="{{ route('employee.payslips.index') }}">明細を見る</a>
            <a class="nav-link {{ request()->routeIs('account.password.*') ? 'active' : '' }}" href="{{ route('account.password.edit') }}">パスワード変更</a>
        </nav>
        <div class="sidebar-user">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ auth()->user()->permission?->label() ?? '一般社員' }}（{{ auth()->user()->permission?->value ?? 1 }}）{{ auth()->user()->company ? ' / '.auth()->user()->company->name : '' }}</span>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">ログアウト</button></form>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <span class="topbar-context">@yield('context', auth()->user()->permission?->label() ?? '一般社員')</span>
            <span class="topbar-context">{{ now()->format('Y年n月j日') }}</span>
        </header>
        <div class="content">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('warning'))<div class="alert alert-error">{{ session('warning') }}</div>@endif
            @if(session('temporary_password'))<div class="alert alert-success"><strong>仮パスワード：{{ session('temporary_password') }}</strong><br>この画面を離れると再表示できません。</div>@endif
            @if($errors->any())
                <div class="alert alert-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
