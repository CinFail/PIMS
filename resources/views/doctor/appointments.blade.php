@extends('layouts.app')
@section('title', 'Scheduled Check-ups')
@section('content')
    <h1>Scheduled Check-ups</h1>
    <p class="page-subtitle">Your queue of appointments. Click a patient to open their chart.</p>

    @if($appointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>No scheduled check-ups.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date &amp; Time</th><th>Patient</th><th>Reason</th><th>Status</th><th></th></tr>
                @foreach($appointments as $a)
                    <tr>
                        <td>{{ $a->appointment_at?->format('M d, Y g:i A') }}</td>
                        <td>{{ $a->patient?->user?->fullName() }}</td>
                        <td>{{ $a->reason_for_visit ?? '—' }}</td>
                        <td>{{ $a->status?->status_name }}</td>
                        <td class="row-actions">
                            @if($a->patient)
                                <a href="{{ route('doctor.patients.show', $a->patient->patient_id) }}" class="btn btn-small">Open Chart</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
@endsection
