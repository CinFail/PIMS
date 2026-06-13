@extends('layouts.app')
@section('title', 'Users')
@section('content')
    <h1>Users</h1>
    <p class="page-subtitle">All system users and their roles.</p>

    <div class="btn-row">
        <a href="{{ route('admin.users.create') }}" class="btn">
            <i class="bi bi-person-plus"></i> Add New User
        </a>
    </div>

    <form action="{{ route('admin.users.index') }}" method="GET" class="search-form">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search users...">
        <button type="submit" class="btn"><i class="bi bi-search"></i> Search</button>
    </form>

    <div class="table-card">
        <table>
            <tr><th>Name</th><th>Email</th><th>Role(s)</th><th>Status</th><th></th></tr>
            @foreach($users as $u)
                <tr>
                    <td>{{ $u->fullName() }}</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @foreach($u->roles as $r)
                            <span class="tag">{{ $r->name }}</span>
                        @endforeach
                    </td>
                    <td>{{ $u->account_status }}</td>
                    <td class="row-actions">
                        @if($u->roles->contains(fn ($r) => $r->name === 'super_admin'))
                            <span class="muted" title="Super Admin accounts are protected and cannot be deactivated.">Protected</span>
                        @else
                            <form action="{{ route('admin.users.toggle', $u->user_id) }}" method="POST" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-small btn-outline">
                                    {{ $u->account_status === 'Active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="pagination-wrap">{{ $users->links() }}</div>
@endsection
