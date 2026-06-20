@extends('layouts.app')
@section('title', 'Doctor Schedules')
@section('content')
    <h1>Doctor Duty Schedules</h1>
    <p class="page-subtitle">View and manage all doctor duty sessions.</p>

    <div class="btn-row">
        <a href="{{ route('admin.doctor-schedules.create') }}" class="btn">
            <i class="bi bi-plus-lg"></i> Add Schedule
        </a>
    </div>

    <div class="table-card">
        <table>
            <tr>
                <th>Doctor</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Schedule Status</th>
                <th>Active</th>
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
                    <td>
                        <span class="tag {{ $s->is_voided ? 'tag-red' : 'tag-green' }}">
                            {{ $s->is_voided ? 'Inactive' : 'Active' }}
                        </span>
                    </td>
                    <td class="row-actions">
                        @if(! $s->is_voided)
                            <a href="{{ route('admin.doctor-schedules.edit', $s->duty_session_id) }}" class="btn btn-small">Edit</a>
                        @endif
                        <form action="{{ route('admin.doctor-schedules.toggle', $s->duty_session_id) }}"
                              method="POST" class="inline-form">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-small btn-outline">
                                {{ $s->is_voided ? 'Reactivate' : 'Deactivate' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" style="text-align:center;padding:24px;">No duty sessions found.</td></tr>
            @endforelse
        </table>
    </div>
    <div class="pagination-wrap">{{ $sessions->links() }}</div>
@endsection
