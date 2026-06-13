@extends('layouts.app')
@section('title', 'Add User')
@section('content')
    <h1>Add New User</h1>

    <div class="btn-row">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <div class="form-card">
            <div class="form-section-title">Account Details</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="first_name">First Name <span class="req">*</span></label>
                    <input type="text" name="first_name" id="first_name"
                           value="{{ old('first_name') }}"
                           class="{{ $errors->has('first_name') ? 'is-error' : '' }}" required>
                    @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span class="req">*</span></label>
                    <input type="text" name="last_name" id="last_name"
                           value="{{ old('last_name') }}"
                           class="{{ $errors->has('last_name') ? 'is-error' : '' }}" required>
                    @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email') }}"
                           class="{{ $errors->has('email') ? 'is-error' : '' }}" required>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="text" name="mobile_number" id="mobile_number"
                           value="{{ old('mobile_number') }}"
                           class="{{ $errors->has('mobile_number') ? 'is-error' : '' }}"
                           data-mobile placeholder="09XX-XXX-XXXX" maxlength="13">
                    @error('mobile_number') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth <span class="req">*</span></label>
                    <input type="date" name="date_of_birth" id="date_of_birth"
                           value="{{ old('date_of_birth') }}"
                           class="{{ $errors->has('date_of_birth') ? 'is-error' : '' }}" required>
                    @error('date_of_birth') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="role">Role <span class="req">*</span></label>
                    <select name="role" id="role"
                            class="{{ $errors->has('role') ? 'is-error' : '' }}" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') == $role->name)>{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                    @error('role') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="req">*</span></label>
                    <input type="password" name="password" id="password"
                           class="{{ $errors->has('password') ? 'is-error' : '' }}" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div id="doctor-fields" style="display:none;">
            <div class="form-card">
                <div class="form-section-title">Professional Details</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="license_number">License Number</label>
                        <input type="text" name="license_number" id="license_number"
                               value="{{ old('license_number') }}"
                               class="{{ $errors->has('license_number') ? 'is-error' : '' }}">
                        @error('license_number') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" name="specialization" id="specialization"
                               value="{{ old('specialization') }}"
                               class="{{ $errors->has('specialization') ? 'is-error' : '' }}">
                        @error('specialization') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group span-2">
                        <label for="short_bio">Short Bio</label>
                        <textarea name="short_bio" id="short_bio">{{ old('short_bio') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn"><i class="bi bi-person-check"></i> Create User</button>
    </form>
@endsection
