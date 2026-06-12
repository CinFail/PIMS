@extends('layouts.app')
@section('title', $title)
@section('content')
    <h1>{{ $title }}</h1>
    <p class="page-subtitle">Audit logs related to the {{ $role }} role.</p>

    @if($logs->isEmpty())
        <p class="muted">No activity recorded for this area yet.</p>
    @else
        <table>
            <tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th></tr>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->logged_at?->format('M d, Y g:i A') }}</td>
                    <td>{{ $log->user?->fullName() ?? 'System' }}</td>
                    <td><span class="tag">{{ $log->action }}</span></td>
                    <td>{{ $log->module_category }}</td>
                    <td>{{ $log->description }}</td>
                </tr>
            @endforeach
        </table>
        <div class="pagination-wrap">{{ $logs->links() }}</div>
    @endif
@endsection
