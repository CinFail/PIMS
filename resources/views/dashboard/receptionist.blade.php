@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Receptionist Dashboard</h1>
    <p class="page-subtitle">Front desk overview.</p>

    <div class="cards">
        <div class="card">
            <div class="num">{{ $patientCount }}</div>
            <div class="lbl">Registered Patients</div>
        </div>
    </div>

    <p>
        <a href="{{ route('receptionist.patients.create') }}" class="btn">Add New Patient</a>
        <a href="{{ route('receptionist.patients.index') }}" class="btn btn-outline">Patient Information</a>
    </p>
@endsection
