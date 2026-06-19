{{-- Shared form fields for create and edit. --}}
{{-- Required variable: $doctors collection                    --}}
{{-- Optional variable: $session (present on edit)             --}}

<div class="form-group">
    <label for="doctor_id">Doctor <span class="req">*</span></label>
    <select name="doctor_id" id="doctor_id" required>
        <option value="" disabled selected hidden></option>
        @foreach($doctors as $d)
            <option value="{{ $d->doctor_id }}"
                @selected(old('doctor_id', $session->doctor_id ?? '') == $d->doctor_id)>
                Dr. {{ $d->user->last_name }}{{ $d->specialization ? ' — ' . $d->specialization : '' }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label for="duty_date">Date <span class="req">*</span></label>
        <input type="date" name="duty_date" id="duty_date"
               value="{{ old('duty_date', isset($session) ? $session->duty_date->toDateString() : '') }}"
               required>
    </div>
    <div class="form-group">
        <label for="status">Status <span class="req">*</span></label>
        <select name="status" id="status" required>
            @foreach(['Scheduled', 'Ongoing', 'Completed', 'Cancelled'] as $opt)
                <option value="{{ $opt }}"
                    @selected(old('status', $session->status ?? 'Scheduled') === $opt)>
                    {{ $opt }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="start_time">Start Time <span class="req">*</span></label>
        <input type="time" name="start_time" id="start_time"
               value="{{ old('start_time', isset($session) ? substr($session->start_time, 0, 5) : '') }}"
               required>
    </div>
    <div class="form-group">
        <label for="end_time">End Time <span class="req">*</span></label>
        <input type="time" name="end_time" id="end_time"
               value="{{ old('end_time', isset($session) ? substr($session->end_time, 0, 5) : '') }}"
               required>
    </div>
</div>
