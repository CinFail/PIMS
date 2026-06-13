@extends('layouts.app')
@section('title', 'Update Information')
@section('content')
    <h1>Update Information</h1>
    <p class="page-subtitle">Keep your personal details up to date.</p>

    <form action="{{ route('patient.profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <h2>Personal</h2>
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required>
        </div>
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required>
        </div>
        <div class="form-group">
            <label for="mobile_number">Mobile Number</label>
            <input type="text" name="mobile_number" id="mobile_number" value="{{ old('mobile_number', $user->mobile_number) }}"
                   inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                   title="Numbers only — exactly 11 digits"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            <p class="help">Numbers only, exactly 11 digits (e.g. 09171234567).</p>
        </div>

        <h2>Clinical &amp; Contact</h2>
        <div class="form-group">
            <label for="sex">Sex</label>
            <select name="sex" id="sex">
                <option value="">-- Select --</option>
                <option value="Male"   @selected(old('sex', $profile->sex)=='Male')>Male</option>
                <option value="Female" @selected(old('sex', $profile->sex)=='Female')>Female</option>
            </select>
        </div>
        <div class="form-group">
            <label for="blood_type">Blood Type</label>
            <input type="text" name="blood_type" id="blood_type" value="{{ old('blood_type', $profile->blood_type) }}" maxlength="5">
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea name="address" id="address">{{ old('address', $profile->address) }}</textarea>
        </div>
        <div class="form-group">
            <label for="emergency_contact_name">Emergency Contact Name</label>
            <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name', $profile->emergency_contact_name) }}">
        </div>
        <div class="form-group">
            <label for="emergency_contact_number">Emergency Contact Number</label>
            <input type="text" name="emergency_contact_number" id="emergency_contact_number" value="{{ old('emergency_contact_number', $profile->emergency_contact_number) }}"
                   inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                   title="Numbers only — exactly 11 digits"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            <p class="help">Numbers only, exactly 11 digits.</p>
        </div>

        <h2>Medical History</h2>
        <div class="form-group">
            <label for="allergies">Allergies</label>
            <textarea name="allergies" id="allergies">{{ old('allergies', $medicalHistory->allergies) }}</textarea>
            <p class="help">List any known drug, food, or environmental allergies.</p>
        </div>
        <div class="form-group">
            <label for="chronic_conditions">Chronic Conditions</label>
            <textarea name="chronic_conditions" id="chronic_conditions">{{ old('chronic_conditions', $medicalHistory->chronic_conditions) }}</textarea>
            <p class="help">e.g. Diabetes, Hypertension, Asthma</p>
        </div>
        <div class="form-group">
            <label for="past_surgeries">Past Surgeries / Hospitalizations</label>
            <textarea name="past_surgeries" id="past_surgeries">{{ old('past_surgeries', $medicalHistory->past_surgeries) }}</textarea>
        </div>
        <div class="form-group">
            <label for="current_medications">Current Medications</label>
            <textarea name="current_medications" id="current_medications">{{ old('current_medications', $medicalHistory->current_medications) }}</textarea>
            <p class="help">Include dosage and frequency if known.</p>
        </div>
        <div class="form-group">
            <label for="family_history">Family Medical History</label>
            <textarea name="family_history" id="family_history">{{ old('family_history', $medicalHistory->family_history) }}</textarea>
            <p class="help">e.g. Heart disease, Cancer, Diabetes in immediate family.</p>
        </div>

        <button type="submit" class="btn">Save Changes</button>
    </form>
@endsection
