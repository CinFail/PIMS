@extends('layouts.app')
@section('title', 'Audit Trail')
@section('content')
    <h1>Full Audit Trail</h1>

    <form action="{{ route('admin.audit.index') }}" method="GET" class="filter-bar">
        <select name="module">
            <option value="">All Modules</option>
            @foreach($modules as $m)
                <option value="{{ $m }}" @selected($module == $m)>{{ $m }}</option>
            @endforeach
        </select>
        <select name="action">
            <option value="">All Actions</option>
            @foreach(['CREATE','UPDATE','DELETE','VIEW','LOGIN','LOGOUT','VOID','APPROVE','UPLOAD','REQUEST'] as $a)
                <option value="{{ $a }}" @selected($action == $a)>{{ $a }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn"><i class="bi bi-funnel"></i> Filter</button>
    </form>

    <p class="result-count">Showing {{ $logs->count() }} of {{ $logs->total() }} entries</p>

    <div class="table-card">
        <table>
            <tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>IP</th></tr>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->logged_at?->format('M d, Y g:i A') }}</td>
                    <td>{{ $log->user?->fullName() ?? 'System' }}</td>
                    <td><span class="tag tag-{{ strtolower($log->action) }}">{{ $log->action }}</span></td>
                    <td>{{ $log->module_category }}</td>
                    <td>{{ $log->description }}</td>
                    <td class="muted">{{ $log->ip_address }}</td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="pagination-wrap">{{ $logs->links() }}</div>
@endsection
