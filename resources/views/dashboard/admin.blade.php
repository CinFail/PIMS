@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Super Admin Dashboard</h1>
    <p class="page-subtitle">System-wide oversight.</p>

    <div class="cards">
        <div class="card">
            <div class="num">{{ $userCount }}</div>
            <div class="lbl">Total Users</div>
        </div>
    </div>

    <h2>Recent Activity (Audit Trail)</h2>
    @if($recentLogs->isEmpty())
        <p class="muted">No activity recorded yet.</p>
    @else
        <table>
            <tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th></tr>
            @foreach($recentLogs as $log)
                <tr>
                    <td>{{ $log->logged_at?->format('M d, g:i A') }}</td>
                    <td>{{ $log->user?->fullName() ?? 'System' }}</td>
                    <td><span class="tag">{{ $log->action }}</span></td>
                    <td>{{ $log->module_category }}</td>
                    <td>{{ $log->description }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p><a href="{{ route('admin.audit.index') }}" class="btn btn-outline">View Full Audit Trail</a></p>
@endsection
