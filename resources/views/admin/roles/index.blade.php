@extends('layouts.app')
@section('title', 'Role Permissions')
@section('content')
    <h1>Role Permissions</h1>
    <p class="page-subtitle">Choose a role to change what its members are allowed to do.</p>

    <table>
        <tr><th>Role</th><th>Permissions Assigned</th><th></th></tr>
        @foreach($roles as $role)
            <tr>
                <td>{{ $role->display_name }} <span class="muted">({{ $role->name }})</span></td>
                <td>{{ $role->permissions_count }}</td>
                <td class="row-actions">
                    <a href="{{ route('admin.roles.edit', $role->role_id) }}" class="btn btn-small">Edit Permissions</a>
                </td>
            </tr>
        @endforeach
    </table>
@endsection
