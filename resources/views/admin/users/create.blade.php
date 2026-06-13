@extends('layouts.app')
@section('title', 'Add User')
@section('content')
    <h1>Add New User</h1>
    <p class="page-subtitle">Create a staff or admin account. Doctors require a license number.</p>

    <div class="btn-row">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required>
        </div>
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required>
        </div>
        <div class="form-group">
            <label for="mobile_number">Mobile Number</label>
            <input type="text" name="mobile_number" id="mobile_number" value="{{ old('mobile_number') }}"
                   inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                   title="Numbers only — exactly 11 digits"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            <p class="help">Numbers only, exactly 11 digits (e.g. 09171234567).</p>
        </div>
        <div class="form-group">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}" required>
        </div>
        <div class="form-group">
            <label for="role">Role</label>
            <select name="role" id="role" required>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role')==$role->name)>{{ $role->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="license_number">License Number (doctors &amp; medtechs)</label>
            <input type="text" name="license_number" id="license_number" value="{{ old('license_number') }}">
        </div>
        <div class="form-group">
            <label for="specialization">Specialization (doctors)</label>
            <input type="text" name="specialization" id="specialization" value="{{ old('specialization') }}">
        </div>
        <div class="form-group">
            <label for="short_bio">Short Bio (doctors)</label>
            <textarea name="short_bio" id="short_bio">{{ old('short_bio') }}</textarea>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit" class="btn"><i class="bi bi-person-check"></i> Create User</button>
    </form>
@endsection
