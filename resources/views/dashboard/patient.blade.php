@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Welcome, {{ auth()->user()->first_name }}</h1>
    <p class="page-subtitle">Here is a quick look at your account.</p>

    <h2>Quick Actions</h2>
    <p>
        <a href="{{ route('patient.appointments.create') }}" class="btn">Book an Appointment</a>
        <a href="{{ route('patient.profile.edit') }}" class="btn btn-outline">Update Information</a>
        <a href="{{ route('patient.lab.index') }}" class="btn btn-outline">Request a Lab Result</a>
    </p>

    <h2>Recent Appointments</h2>
    @if($appointments->isEmpty())
        <p class="muted">You have no appointments yet.</p>
    @else
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
    @endif
@endsection
