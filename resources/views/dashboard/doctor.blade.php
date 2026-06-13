@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Doctor Dashboard</h1>
    <p class="page-subtitle">Welcome, Dr. {{ auth()->user()->last_name }}.</p>

    <div class="cards">
        <div class="card">
            <div class="card-inner">
                <div>
                    <div class="num">{{ $todayCount }}</div>
                    <div class="lbl">Today's Check-ups</div>
                </div>
                <div class="card-icon"><i class="bi bi-calendar2-check"></i></div>
            </div>
        </div>
    </div>

    <h2>Upcoming Check-ups</h2>
    @if($upcoming->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>No upcoming appointments.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date &amp; Time</th><th>Patient</th><th>Status</th><th></th></tr>
                @foreach($upcoming as $a)
                    <tr>
                        <td>{{ $a->appointment_at?->format('M d, Y g:i A') }}</td>
                        <td>{{ $a->patient?->user?->fullName() }}</td>
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
