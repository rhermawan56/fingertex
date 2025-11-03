@extends('layout.app')

@section('title', 'Dashboard')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Dashboard</h3>
    </div>
    <div class="card-body">
        <p>Selamat datang, {{ Auth::user()->name }}!</p>
    </div>
</div>
@endsection
