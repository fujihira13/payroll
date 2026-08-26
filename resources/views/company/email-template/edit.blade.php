@extends('layouts.app')
@section('title', '通知メール')
@section('content')
<div class="page-header"><div><div class="eyebrow">EMAIL NOTICE</div><h1>通知メール</h1><p class="lead">給与明細の公開時に社員へ送る案内です。PDFは添付しません。</p></div></div>
<form class="card" method="post" action="{{ route('company.email-template.update') }}">@csrf @method('put')<div class="form-grid"><div class="field field-full"><label>件名</label><input name="subject" value="{{ old('subject', $template->subject) }}" required></div><div class="field field-full"><label>本文</label><textarea name="body" rows="12" required>{{ old('body', $template->body) }}</textarea><span class="help">利用可能：{employee_name} {target_month} {login_url} {company_name}</span></div></div><div class="form-actions"><button class="button button-primary">保存</button></div></form>
@endsection
