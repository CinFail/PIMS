@extends('layouts.app')
@section('title', 'Doctor Schedules')
@section('content')
    <h1>Doctor Duty Schedules</h1>
    <p class="page-subtitle">View and manage all doctor duty sessions.</p>

    <p><a href="{{ route('admin.doctor-schedules.create') }}" class="btn">Add Schedule</a></p>

    <table>
        <tr>
            <th>Doctor</th>
            <th>Date</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
            <th></th>
        </tr>
        @forelse($sessions as $s)
            <tr>
                <td>Dr. {{ $s->doctor->user->last_name }}
                    @if($s->doctor->specialization)
                        <span class="muted">— {{ $s->doctor->specialization }}</span>
                    @endif
                </td>
                <td>{{ $s->duty_date->format('M d, Y') }}</td>
                <td>{{ substr($s->start_time, 0, 5) }}</td>
                <td>{{ substr($s->end_time, 0, 5) }}</td>
                <td><span class="tag">{{ $s->status }}</span></td>
                <td class="row-actions">
                    <a href="{{ route('admin.doctor-schedules.edit', $s->duty_session_id) }}"
                       class="btn btn-small">Edit</a>
                    <form action="{{ route('admin.doctor-schedules.destroy', $s->duty_session_id) }}"
                          method="POST" class="inline-form"
                          onsubmit="return confirm('Delete this duty session?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-small btn-outline">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No duty sessions found.</td></tr>
        @endforelse
    </table>

    <div class="pagination-wrap">{{ $sessions->links() }}</div>
@endsection
