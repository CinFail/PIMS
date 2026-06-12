@extends('layouts.app')
@section('title', 'Edit Role Permissions')
@section('content')
    <h1>Permissions: {{ $role->display_name }}</h1>
    <p class="page-subtitle">Tick the actions this role can perform, then save.</p>

    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline">Back</a>

    <form action="{{ route('admin.roles.update', $role->role_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group" style="max-width:none;">
            @forelse($permissions as $perm)
                <div class="checkbox-row">
                    <input type="checkbox" name="permissions[]" id="p{{ $perm->permission_id }}" value="{{ $perm->permission_id }}"
                        @checked(in_array($perm->permission_id, $assigned))>
                    <label for="p{{ $perm->permission_id }}">{{ $perm->name }}</label>
                </div>
            @empty
                <p class="muted">No permissions are defined yet.</p>
            @endforelse
        </div>

        <button type="submit" class="btn">Save Permissions</button>
    </form>
@endsection
