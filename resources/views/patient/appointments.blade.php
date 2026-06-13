@extends('layouts.app')
@section('title', 'My Appointments')
@section('content')
    <h1>My Appointments</h1>
    <p class="page-subtitle">Your doctor and laboratory appointments.</p>

    <p>
        <a href="{{ route('patient.appointments.create') }}" class="btn">Book a Doctor Appointment</a>
        <a href="{{ route('patient.lab.request.create') }}" class="btn btn-outline">Request a Lab Test</a>
    </p>

    {{-- ---------------- Doctor Appointments ---------------- --}}
    <h2>Doctor Appointments</h2>
    @if($appointments->isEmpty())
        <p class="muted">You have no doctor appointments yet.</p>
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

    {{-- -------------- Laboratory Appointments -------------- --}}
    <h2>Laboratory Appointments</h2>
    @if($labAppointments->isEmpty())
        <p class="muted">You have no laboratory appointments yet.</p>
    @else
        <table>
            <tr><th>Date &amp; Time</th><th>Tests</th><th>Status</th></tr>
            @foreach($labAppointments as $la)
                <tr>
                    <td>{{ $la->scheduled_at?->format('M d, Y g:i A') }}</td>
                    <td>
                        @if($la->labRequest && $la->labRequest->items->isNotEmpty())
                            {{ $la->labRequest->items->map(fn ($i) => $i->test?->test_name)->filter()->join(', ') }}
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td>{{ $la->status }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
