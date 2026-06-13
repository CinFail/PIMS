@extends('layouts.app')
@section('title', 'Add New Patient')
@section('content')
    <h1>Add New Patient</h1>
    <p class="page-subtitle">Walk-in registration. Requires email or mobile number.</p>

    <div class="btn-row">
        <a href="{{ route('receptionist.patients.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('receptionist.patients.store') }}" method="POST">
        @csrf

        <div class="form-card">
            <div class="form-section-title">Personal Information</div>
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
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email') }}"
                           class="{{ $errors->has('email') ? 'is-error' : '' }}">
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
                    <label for="sex">Sex</label>
                    <select name="sex" id="sex" class="{{ $errors->has('sex') ? 'is-error' : '' }}">
                        <option value="">— Select —</option>
                        <option value="Male"   @selected(old('sex') == 'Male')>Male</option>
                        <option value="Female" @selected(old('sex') == 'Female')>Female</option>
                    </select>
                    @error('sex') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group span-2">
                    <label for="address">Address</label>
                    <textarea name="address" id="address">{{ old('address') }}</textarea>
                </div>
                <div class="form-group">
                    <label for="password">Temporary Password <span class="req">*</span></label>
                    <input type="password" name="password" id="password"
                           class="{{ $errors->has('password') ? 'is-error' : '' }}" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-section-title">Medical History</div>
            <div class="form-group">
                <label for="allergies">Allergies</label>
                <textarea name="allergies" id="allergies">{{ old('allergies') }}</textarea>
            </div>
            <div class="form-group">
                <label for="chronic_conditions">Chronic Conditions</label>
                <textarea name="chronic_conditions" id="chronic_conditions">{{ old('chronic_conditions') }}</textarea>
            </div>
            <div class="form-group">
                <label for="past_surgeries">Past Surgeries / Hospitalizations</label>
                <textarea name="past_surgeries" id="past_surgeries">{{ old('past_surgeries') }}</textarea>
            </div>
            <div class="form-group">
                <label for="current_medications">Current Medications</label>
                <textarea name="current_medications" id="current_medications">{{ old('current_medications') }}</textarea>
            </div>
            <div class="form-group">
                <label for="family_history">Family Medical History</label>
                <textarea name="family_history" id="family_history">{{ old('family_history') }}</textarea>
            </div>
        </div>

        <button type="submit" class="btn"><i class="bi bi-person-check"></i> Create Patient</button>
    </form>
@endsection
