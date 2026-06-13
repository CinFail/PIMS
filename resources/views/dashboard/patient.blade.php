@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Welcome, {{ auth()->user()->first_name }}</h1>
    <p class="page-subtitle">Here is a quick look at your account.</p>

    <h2>Quick Actions</h2>
    <div class="btn-row">
        <a href="{{ route('patient.appointments.create') }}" class="btn">
            <i class="bi bi-calendar-plus"></i> Book a Doctor Appointment
        </a>
        <a href="{{ route('patient.lab.request.create') }}" class="btn btn-outline">
            <i class="bi bi-droplet"></i> Request a Lab Test
        </a>
        <a href="{{ route('patient.profile.edit') }}" class="btn btn-outline">
            <i class="bi bi-person-gear"></i> Update Information
        </a>
        <a href="{{ route('patient.lab.index') }}" class="btn btn-outline">
            <i class="bi bi-file-earmark-medical"></i> My Lab Results
        </a>
    </div>

    <h2>Recent Appointments</h2>
    @if($appointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>You have no appointments yet.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date &amp; Time</th><th>Doctor</th><th>Status</th></tr>
                @foreach($appointments as $a)
                    <tr>
                        <td>{{ $a->appointment_at?->format('M d, Y g:i A') }}</td>
                        <td>{{ $a->doctor?->user?->fullName() }}</td>
                        <td>{{ $a->status?->status_name }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
@endsection
