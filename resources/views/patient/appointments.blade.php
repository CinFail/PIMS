@extends('layouts.app')
@section('title', 'My Appointments')
@section('content')
    <h1>My Appointments</h1>
    <p class="page-subtitle">Your doctor and laboratory appointments.</p>

    <div class="btn-row">
        <a href="{{ route('patient.appointments.create') }}" class="btn">
            <i class="bi bi-calendar-plus"></i> Book a Doctor Appointment
        </a>
        <a href="{{ route('patient.lab.request.create') }}" class="btn btn-outline">
            <i class="bi bi-droplet"></i> Request a Lab Test
        </a>
    </div>

    {{-- Doctor Appointments --}}
    <h2>Doctor Appointments</h2>
    @if($appointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>You have no doctor appointments yet.</p>
        </div>
    @else
        <div class="table-card">
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
        </div>
    @endif

    {{-- Laboratory Appointments --}}
    <h2>Laboratory Appointments</h2>
    @if($labAppointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-eyedropper"></i>
            <p>You have no laboratory appointments yet.</p>
        </div>
    @else
        <div class="table-card">
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
        </div>
    @endif
@endsection
