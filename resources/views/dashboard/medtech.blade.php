@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>MedTech Dashboard</h1>
    <p class="page-subtitle">Laboratory workflow overview.</p>

    <div class="cards">
        <div class="card">
            <div class="num">{{ $pendingRequests }}</div>
            <div class="lbl">Pending Lab Tests</div>
        </div>
        <div class="card">
            <div class="num">{{ $softCopyRequests }}</div>
            <div class="lbl">Soft Copy Requests</div>
        </div>
    </div>

    <p>
        <a href="{{ route('medtech.lab.index') }}" class="btn">Scheduled Lab Tests</a>
        <a href="{{ route('medtech.softcopy.index') }}" class="btn btn-outline">Soft Copy Requests</a>
    </p>
@endsection
