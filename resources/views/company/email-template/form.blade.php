@extends('layouts.app')
@section('title', $template->exists ? 'メールテンプレート編集' : 'メールテンプレート登録')
@section('content')
<div class="page-header"><div><div class="eyebrow">EMAIL TEMPLATE</div><h1>{{ $template->exists ? 'メールテンプレート編集' : 'メールテンプレート登録' }}</h1></div></div>
<form class="card" method="post" action="{{ $template->exists ? route('company.email-templates.update', $template) : route('company.email-templates.store') }}">@csrf @if($template->exists) @method('put') @endif<div class="form-grid">
<div class="field"><label>テンプレート名</label><input name="name" value="{{ old('name', $template->name) }}" required></div><div class="field"><label>用途</label><select name="type"><option value="payslip_published" @selected(old('type', $template->type)==='payslip_published')>明細公開通知</option><option value="employee_notice" @selected(old('type', $template->type)==='employee_notice')>社員への個別連絡</option></select></div>
<div class="field"><label>送信者名</label><input name="sender_name" value="{{ old('sender_name', $template->sender_name) }}"></div><div class="field"><label>送信者アドレス</label><input type="email" name="sender_address" value="{{ old('sender_address', $template->sender_address) }}"></div>
<div class="field field-full"><label>件名</label><input name="subject" value="{{ old('subject', $template->subject) }}" required></div><div class="field field-full"><label>本文</label><textarea name="body" rows="12" required>{{ old('body', $template->body) }}</textarea><span class="help">差込：{company_name} {department_name} {employee_name} {payslip_title} {payment_date} {login_url} {login_id}</span></div>
<div class="field field-full"><input type="hidden" name="is_active" value="0"><label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->exists ? $template->is_active : true))>このテンプレートを使用する</label></div>
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('company.email-templates.index') }}">戻る</a><button class="button button-primary">保存</button></div></form>
@endsection
