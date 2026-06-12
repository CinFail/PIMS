{{-- Shared form fields for create and edit. --}}
{{-- Required variables: $doctors collection                   --}}
{{-- Optional variable:  $session (present on edit, absent on create) --}}

<div class="form-group">
    <label for="doctor_id">Doctor</label>
    <select name="doctor_id" id="doctor_id" required>
        <option value="">-- Select Doctor --</option>
        @foreach($doctors as $d)
            <option value="{{ $d->doctor_id }}"
                @selected(old('doctor_id', $session->doctor_id ?? '') == $d->doctor_id)>
                Dr. {{ $d->user->last_name }}{{ $d->specialization ? ' — ' . $d->specialization : '' }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="duty_date">Date</label>
    <input type="date" name="duty_date" id="duty_date"
           value="{{ old('duty_date', isset($session) ? $session->duty_date->toDateString() : '') }}"
           required>
</div>

<div class="form-group">
    <label for="start_time">Start Time</label>
    <input type="time" name="start_time" id="start_time"
           value="{{ old('start_time', isset($session) ? substr($session->start_time, 0, 5) : '') }}"
           required>
</div>

<div class="form-group">
    <label for="end_time">End Time</label>
    <input type="time" name="end_time" id="end_time"
           value="{{ old('end_time', isset($session) ? substr($session->end_time, 0, 5) : '') }}"
           required>
    <p class="help">Must be after the start time.</p>
</div>

<div class="form-group">
    <label for="status">Status</label>
    <select name="status" id="status" required>
        @foreach(['Scheduled', 'Ongoing', 'Completed', 'Cancelled'] as $opt)
            <option value="{{ $opt }}"
                @selected(old('status', $session->status ?? 'Scheduled') === $opt)>
                {{ $opt }}
            </option>
        @endforeach
    </select>
</div>
