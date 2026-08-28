@extends('layouts.app')
@section('title', 'ダッシュボード')
@section('content')
<div class="page-header"><div><div class="eyebrow">OVERVIEW</div><h1>{{ auth()->user()->name }}さん、お疲れさまです</h1><p class="lead">必要な状況をここから確認できます。</p></div></div>
<section class="metrics">@foreach($metrics as $metric)<div class="card metric"><span class="metric-label">{{ $metric['label'] }}</span><strong class="metric-value">{{ $metric['value'] }}</strong></div>@endforeach</section>
@if(auth()->user()->canManageCompany())
<section class="workflow" aria-label="給与明細の公開手順"><div class="workflow-step"><small>01 / PREPARE</small><strong>CSVを取り込む</strong></div><div class="workflow-step"><small>02 / APPROVE</small><strong>内容を確認・承認</strong></div><div class="workflow-step"><small>03 / DELIVER</small><strong>予約日時に通知</strong></div></section>
@endif
@endsection
