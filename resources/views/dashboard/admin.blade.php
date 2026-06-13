@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Super Admin Dashboard</h1>
    <p class="page-subtitle">System-wide oversight.</p>

    <div class="cards">
        <div class="card">
            <div class="card-inner">
                <div>
                    <div class="num">{{ $userCount }}</div>
                    <div class="lbl">Total Users</div>
                </div>
                <div class="card-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>

    <h2>Recent Activity (Audit Trail)</h2>
    @if($recentLogs->isEmpty())
        <div class="empty-state">
            <i class="bi bi-clipboard2-x"></i>
            <p>No activity recorded yet.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th></tr>
                @foreach($recentLogs as $log)
                    <tr>
                        <td>{{ $log->logged_at?->format('M d, g:i A') }}</td>
                        <td>{{ $log->user?->fullName() ?? 'System' }}</td>
                        <td><span class="tag tag-{{ strtolower($log->action) }}">{{ $log->action }}</span></td>
                        <td>{{ $log->module_category }}</td>
                        <td>{{ $log->description }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="btn-row">
        <a href="{{ route('admin.audit.index') }}" class="btn btn-outline">
            <i class="bi bi-list-ul"></i> View Full Audit Trail
        </a>
    </div>
@endsection
