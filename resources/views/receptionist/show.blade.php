@extends('layouts.app')
@section('title', 'Patient Information')
@section('content')
    <h1>{{ $patient->user?->fullName() }}</h1>
    <p class="page-subtitle">Basic patient information</p>

    <div class="btn-row">
        <a href="{{ route('receptionist.patients.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="box">
        <table>
            <tr><th>Email</th><td>{{ $patient->user?->email }}</td></tr>
            <tr><th>Mobile</th><td>{{ $patient->user?->mobile_number ?? '—' }}</td></tr>
            <tr><th>Date of Birth</th><td>{{ $patient->user?->date_of_birth?->format('M d, Y') }}</td></tr>
            <tr><th>Age</th><td>{{ $patient->user?->age() !== null ? $patient->user->age().' yrs' : '—' }}</td></tr>
            <tr><th>Sex</th><td>{{ $patient->sex ?? '—' }}</td></tr>
            <tr><th>Address</th><td>{{ $patient->address ?? '—' }}</td></tr>
            <tr><th>Account Status</th><td>{{ $patient->user?->account_status }}</td></tr>
        </table>
    </div>
@endsection
