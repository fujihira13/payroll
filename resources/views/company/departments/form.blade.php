@extends('layouts.app')
@section('title', $department->exists ? '部署を編集' : '部署を登録')
@section('content')
<div class="page-header"><div><div class="eyebrow">DEPARTMENT</div><h1>{{ $department->exists ? '部署を編集' : '部署を登録' }}</h1></div></div>
<form class="card" method="post" action="{{ $department->exists ? route('company.departments.update', $department) : route('company.departments.store') }}">@csrf @if($department->exists) @method('put') @endif<div class="form-grid"><div class="field"><label>部署コード</label><input name="code" value="{{ old('code', $department->code) }}" required></div><div class="field"><label>部署名</label><input name="name" value="{{ old('name', $department->name) }}" required></div><div class="field field-full"><label>部署名カナ</label><input name="name_kana" value="{{ old('name_kana', $department->name_kana) }}"></div></div><div class="form-actions"><a class="button button-secondary" href="{{ route('company.departments.index') }}">戻る</a><button class="button button-primary">保存</button></div></form>
@endsection
