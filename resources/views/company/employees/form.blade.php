@extends('layouts.app')
@section('title', $employee->exists ? '社員を編集' : '社員を登録')
@section('content')
<div class="page-header"><div><div class="eyebrow">EMPLOYEE PROFILE</div><h1>{{ $employee->exists ? '社員を編集' : '社員を登録' }}</h1></div></div>
<form class="card" method="post" action="{{ $employee->exists ? route('company.employees.update', $employee) : route('company.employees.store') }}">@csrf @if($employee->exists) @method('put') @endif<div class="form-grid">
<div class="field"><label>社員番号</label><input name="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" required></div><div class="field"><label>部署</label><select name="department_id"><option value="">未設定</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id)==$department->id)>{{ $department->code }}｜{{ $department->name }}</option>@endforeach</select></div>
<div class="field"><label>氏名</label><input name="name" value="{{ old('name', $employee->name) }}" required></div><div class="field"><label>メールアドレス</label><input type="email" name="email" value="{{ old('email', $employee->email) }}" required></div>
<div class="field"><label>パスワード</label><input type="password" name="password" @required(!$employee->exists)><span class="help">{{ $employee->exists ? '変更しない場合は空欄' : '8文字以上' }}</span></div><div class="field"><input type="hidden" name="is_active" value="0"><label class="checkbox" style="margin-top:32px"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->exists ? $employee->is_active : true))>ログインを許可する</label></div>
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('company.employees.index') }}">戻る</a><button class="button button-primary">保存</button></div></form>
@endsection
