@extends('layouts.app')
@section('title', 'Add New Patient')
@section('content')
    <h1>Add New Patient</h1>
    <p class="page-subtitle">Register a walk-in patient. OTP is bypassed, but an email OR a mobile number is required.</p>

    <form action="{{ route('receptionist.patients.store') }}" method="POST">
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
            <label for="email">Email (email OR mobile required)</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}">
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
            <label for="sex">Sex</label>
            <select name="sex" id="sex">
                <option value="">-- Select --</option>
                <option value="Male" @selected(old('sex')=='Male')>Male</option>
                <option value="Female" @selected(old('sex')=='Female')>Female</option>
            </select>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea name="address" id="address">{{ old('address') }}</textarea>
        </div>
        <div class="form-group">
            <label for="password">Temporary Password</label>
            <input type="password" name="password" id="password" required>
        </div>

        <h2>Medical History</h2>
        <div class="form-group">
            <label for="allergies">Allergies</label>
            <textarea name="allergies" id="allergies">{{ old('allergies') }}</textarea>
            <p class="help">List any known drug, food, or environmental allergies.</p>
        </div>
        <div class="form-group">
            <label for="chronic_conditions">Chronic Conditions</label>
            <textarea name="chronic_conditions" id="chronic_conditions">{{ old('chronic_conditions') }}</textarea>
            <p class="help">e.g. Diabetes, Hypertension, Asthma</p>
        </div>
        <div class="form-group">
            <label for="past_surgeries">Past Surgeries / Hospitalizations</label>
            <textarea name="past_surgeries" id="past_surgeries">{{ old('past_surgeries') }}</textarea>
        </div>
        <div class="form-group">
            <label for="current_medications">Current Medications</label>
            <textarea name="current_medications" id="current_medications">{{ old('current_medications') }}</textarea>
            <p class="help">Include dosage and frequency if known.</p>
        </div>
        <div class="form-group">
            <label for="family_history">Family Medical History</label>
            <textarea name="family_history" id="family_history">{{ old('family_history') }}</textarea>
            <p class="help">e.g. Heart disease, Cancer, Diabetes in immediate family.</p>
        </div>

        <button type="submit" class="btn">Create Patient</button>
    </form>
@endsection
