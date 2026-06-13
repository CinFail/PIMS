@extends('layouts.app')
@section('title', 'Update Information')
@section('content')
    <h1>Update Information</h1>

    <form action="{{ route('patient.profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-card">
            <div class="form-section-title">Personal Information</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="first_name">First Name <span class="req">*</span></label>
                    <input type="text" name="first_name" id="first_name"
                           value="{{ old('first_name', $user->first_name) }}"
                           class="{{ $errors->has('first_name') ? 'is-error' : '' }}" required>
                    @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span class="req">*</span></label>
                    <input type="text" name="last_name" id="last_name"
                           value="{{ old('last_name', $user->last_name) }}"
                           class="{{ $errors->has('last_name') ? 'is-error' : '' }}" required>
                    @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="text" name="mobile_number" id="mobile_number"
                           value="{{ old('mobile_number', $user->mobile_number) }}"
                           class="{{ $errors->has('mobile_number') ? 'is-error' : '' }}"
                           data-mobile placeholder="09XX-XXX-XXXX" maxlength="13">
                    @error('mobile_number') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="sex">Sex</label>
                    <select name="sex" id="sex" class="{{ $errors->has('sex') ? 'is-error' : '' }}">
                        <option value="">— Select —</option>
                        <option value="Male"   @selected(old('sex', $profile->sex) == 'Male')>Male</option>
                        <option value="Female" @selected(old('sex', $profile->sex) == 'Female')>Female</option>
                    </select>
                    @error('sex') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="blood_type">Blood Type</label>
                    <input type="text" name="blood_type" id="blood_type"
                           value="{{ old('blood_type', $profile->blood_type) }}"
                           class="{{ $errors->has('blood_type') ? 'is-error' : '' }}"
                           placeholder="e.g. A+" maxlength="5">
                    @error('blood_type') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-section-title">Contact &amp; Emergency</div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea name="address" id="address"
                          class="{{ $errors->has('address') ? 'is-error' : '' }}">{{ old('address', $profile->address) }}</textarea>
                @error('address') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                           value="{{ old('emergency_contact_name', $profile->emergency_contact_name) }}"
                           class="{{ $errors->has('emergency_contact_name') ? 'is-error' : '' }}">
                    @error('emergency_contact_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="emergency_contact_number">Emergency Contact Number</label>
                    <input type="text" name="emergency_contact_number" id="emergency_contact_number"
                           value="{{ old('emergency_contact_number', $profile->emergency_contact_number) }}"
                           class="{{ $errors->has('emergency_contact_number') ? 'is-error' : '' }}"
                           data-mobile placeholder="09XX-XXX-XXXX" maxlength="13">
                    @error('emergency_contact_number') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-section-title">Medical History</div>
            <div class="form-group">
                <label for="allergies">Allergies</label>
                <textarea name="allergies" id="allergies">{{ old('allergies', $medicalHistory->allergies) }}</textarea>
            </div>
            <div class="form-group">
                <label for="chronic_conditions">Chronic Conditions</label>
                <textarea name="chronic_conditions" id="chronic_conditions">{{ old('chronic_conditions', $medicalHistory->chronic_conditions) }}</textarea>
            </div>
            <div class="form-group">
                <label for="past_surgeries">Past Surgeries / Hospitalizations</label>
                <textarea name="past_surgeries" id="past_surgeries">{{ old('past_surgeries', $medicalHistory->past_surgeries) }}</textarea>
            </div>
            <div class="form-group">
                <label for="current_medications">Current Medications</label>
                <textarea name="current_medications" id="current_medications">{{ old('current_medications', $medicalHistory->current_medications) }}</textarea>
            </div>
            <div class="form-group">
                <label for="family_history">Family Medical History</label>
                <textarea name="family_history" id="family_history">{{ old('family_history', $medicalHistory->family_history) }}</textarea>
            </div>
        </div>

        <button type="submit" class="btn">Save Changes</button>
    </form>
@endsection
