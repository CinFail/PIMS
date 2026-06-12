@extends('layouts.app')
@section('title', 'My Appointments')
@section('content')
    <h1>My Appointments</h1>
    <p class="page-subtitle">All your booked appointments.</p>

    <p><a href="{{ route('patient.appointments.create') }}" class="btn">Book an Appointment</a></p>

    @if($appointments->isEmpty())
        <p class="muted">You have no appointments yet.</p>
    @else
        <table>
            <tr><th>Date &amp; Time</th><th>Doctor</th><th>Type</th><th>Status</th></tr>
            @foreach($appointments as $a)
                <tr>
                    <td>{{ $a->appointment_at?->format('M d, Y g:i A') }}</td>
                    <td>{{ $a->doctor?->user?->fullName() }}</td>
                    <td>{{ $a->appointment_type }}</td>
                    <td>{{ $a->status?->status_name }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
