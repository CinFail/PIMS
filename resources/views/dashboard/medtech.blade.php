@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>MedTech Dashboard</h1>
    <p class="page-subtitle">Laboratory workflow overview.</p>

    <div class="cards">
        <div class="card">
            <div class="card-inner">
                <div>
                    <div class="num">{{ $pendingRequests }}</div>
                    <div class="lbl">Pending Lab Tests</div>
                </div>
                <div class="card-icon"><i class="bi bi-eyedropper-fill"></i></div>
            </div>
        </div>
        <div class="card">
            <div class="card-inner">
                <div>
                    <div class="num">{{ $softCopyRequests }}</div>
                    <div class="lbl">Soft Copy Requests</div>
                </div>
                <div class="card-icon"><i class="bi bi-cloud-arrow-down-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="btn-row">
        <a href="{{ route('medtech.lab.index') }}" class="btn">
            <i class="bi bi-eyedropper"></i> Scheduled Lab Tests
        </a>
        <a href="{{ route('medtech.softcopy.index') }}" class="btn btn-outline">
            <i class="bi bi-cloud-download"></i> Soft Copy Requests
        </a>
    </div>
@endsection
