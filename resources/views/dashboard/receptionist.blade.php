@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Receptionist Dashboard</h1>
    <p class="page-subtitle">Front desk overview.</p>

    <div class="cards">
        <div class="card">
            <div class="card-inner">
                <div>
                    <div class="num">{{ $patientCount }}</div>
                    <div class="lbl">Registered Patients</div>
                </div>
                <div class="card-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="card quick-links-card">
            <div class="quick-links-title">Quick Links</div>
            <a href="{{ route('receptionist.patients.create') }}" class="quick-link">
                <i class="bi bi-person-plus"></i> Add New Patient
            </a>
            <a href="{{ route('receptionist.patients.index') }}" class="quick-link">
                <i class="bi bi-card-list"></i> View All Information
            </a>
        </div>
    </div>

    <div class="btn-row">
        <a href="{{ route('receptionist.patients.create') }}" class="btn">
            <i class="bi bi-person-plus"></i> Add New Patient
        </a>
        <a href="{{ route('receptionist.patients.index') }}" class="btn btn-outline">
            <i class="bi bi-person-lines-fill"></i> Patient Information
        </a>
    </div>
@endsection
