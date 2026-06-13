@extends('layouts.app')
@section('title', 'Request a Lab Test')
@section('content')
    <h1>Request a Lab Test</h1>
    <p class="page-subtitle">No doctor consultation required. Select tests and a preferred visit time.</p>

    <form action="{{ route('patient.lab.request.store') }}" method="POST" id="lab-request-form">
        @csrf

        <h2>Laboratory Tests <span class="req">*</span></h2>
        @error('lab_tests')
            <span class="field-error" style="display:block;margin-bottom:8px;">{{ $message }}</span>
        @enderror
        <div class="form-group" style="max-width:none;">
            @forelse($labTests as $t)
                <div class="checkbox-row">
                    <input type="checkbox" name="lab_tests[]" id="t{{ $t->lab_test_id }}" value="{{ $t->lab_test_id }}"
                           @checked(collect(old('lab_tests'))->contains($t->lab_test_id))>
                    <label for="t{{ $t->lab_test_id }}">
                        {{ $t->test_name }}
                        <span class="muted">({{ $t->category?->category_name }})</span>
                    </label>
                </div>
            @empty
                <div class="empty-state" style="padding:32px 24px;">
                    <i class="bi bi-eyedropper"></i>
                    <p>No lab tests are configured yet. Please check back later.</p>
                </div>
            @endforelse
        </div>

        @if($labTests->isNotEmpty())
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="scheduled_date">Preferred Date <span class="req">*</span></label>
                    <input type="date" name="scheduled_date" id="scheduled_date"
                           value="{{ old('scheduled_date') }}"
                           min="{{ date('Y-m-d') }}">
                    @error('scheduled_date')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    <span class="field-error" id="date-error" style="display:none;"></span>
                </div>

                <div class="form-group">
                    <label for="time-hour">Preferred Time <span class="req">*</span>
                        <span style="font-size:11px;color:var(--text-muted);font-weight:400;">Mon–Thu 7:30–4:30 PM &bull; Sun &amp; Fri 7:30–4:00 PM</span>
                    </label>
                    <div style="display:flex;gap:6px;">
                        <input type="text" id="time-hour"
                               maxlength="5" style="flex:1;min-width:0;" autocomplete="off">
                        <select id="time-ampm" style="width:78px;">
                            <option value="AM">AM</option>
                            <option value="PM" selected>PM</option>
                        </select>
                    </div>
                    <input type="hidden" name="scheduled_time" id="scheduled_time"
                           value="{{ old('scheduled_time') }}">
                    @error('scheduled_time')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    <span class="field-error" id="time-error" style="display:none;"></span>
                </div>

                <div class="form-group span-2">
                    <label for="clinical_notes">Notes</label>
                    <textarea name="clinical_notes" id="clinical_notes">{{ old('clinical_notes') }}</textarea>
                </div>
            </div>

            <button type="submit" class="btn"><i class="bi bi-send"></i> Submit Lab Request</button>
        @endif
    </form>
@endsection

@push('scripts')
<script>
(function () {
    var dateInput  = document.getElementById('scheduled_date');
    var timeHour   = document.getElementById('time-hour');
    var timeAmpm   = document.getElementById('time-ampm');
    var timeHidden = document.getElementById('scheduled_time');
    var dateErr    = document.getElementById('date-error');
    var timeErr    = document.getElementById('time-error');
    var theForm    = document.getElementById('lab-request-form');

    if (!dateInput) return;

    // Restore display value after server-side validation failure
    var oldTime = timeHidden ? timeHidden.value : '';
    if (oldTime) {
        var parts = oldTime.split(':');
        var h24 = parseInt(parts[0]);
        var m = parts[1];
        var h12 = h24 % 12 || 12;
        timeHour.value = h12 + ':' + m;
        timeAmpm.value = h24 < 12 ? 'AM' : 'PM';
    }

    function show(el, msg) {
        el.textContent = msg;
        el.style.display = msg ? '' : 'none';
    }

    function validateDate() {
        if (!dateInput.value) {
            show(dateErr, 'Please select a date.');
            return false;
        }
        // append T00:00:00 so Date() treats it as local time (not UTC)
        var d = new Date(dateInput.value + 'T00:00:00');
        if (d.getDay() === 6) {
            show(dateErr, 'Saturdays are not available. Please choose Sunday through Friday.');
            return false;
        }
        show(dateErr, '');
        return true;
    }

    function dayLimits() {
        // Returns { min: minutes, max: minutes, maxLabel: string } based on selected date's day
        var defaultMax = 16 * 60; // 4:00 PM
        if (!dateInput.value) return { min: 7 * 60 + 30, max: defaultMax, maxLabel: '4:00 PM' };
        var day = new Date(dateInput.value + 'T00:00:00').getDay();
        // Mon(1)–Thu(4): close at 4:30 PM; Sun(0) & Fri(5): close at 4:00 PM
        var isLong = day >= 1 && day <= 4;
        return {
            min: 7 * 60 + 30,
            max: isLong ? 16 * 60 + 30 : 16 * 60,
            maxLabel: isLong ? '4:30 PM' : '4:00 PM'
        };
    }

    function validateTime() {
        var raw = timeHour.value.trim();
        if (!raw) {
            show(timeErr, 'Please enter a preferred time.');
            timeHidden.value = '';
            return false;
        }
        var match = raw.match(/^(\d{1,2})(?::(\d{2}))?$/);
        if (!match) {
            show(timeErr, 'Enter time as "9:00" or "2:30".');
            timeHidden.value = '';
            return false;
        }
        var h = parseInt(match[1]);
        var m = parseInt(match[2] || '0');
        if (h < 1 || h > 12 || m < 0 || m > 59) {
            show(timeErr, 'Enter a valid time (hour 1–12, minutes 00–59).');
            timeHidden.value = '';
            return false;
        }
        var ampm = timeAmpm.value;
        var h24 = h;
        if (ampm === 'AM' && h === 12) h24 = 0;
        if (ampm === 'PM' && h !== 12) h24 = h + 12;

        var limits = dayLimits();
        var total = h24 * 60 + m;
        if (total < limits.min || total > limits.max) {
            show(timeErr, 'Please choose a time between 7:30 AM and ' + limits.maxLabel + '.');
            timeHidden.value = '';
            return false;
        }
        show(timeErr, '');
        timeHidden.value = String(h24).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        return true;
    }

    dateInput.addEventListener('change', validateDate);
    timeHour.addEventListener('blur', validateTime);
    timeHour.addEventListener('input', function () { show(timeErr, ''); });
    timeAmpm.addEventListener('change', function () { if (timeHour.value.trim()) validateTime(); });

    theForm.addEventListener('submit', function (e) {
        var dOk = validateDate();
        var tOk = validateTime();
        if (!dOk || !tOk) e.preventDefault();
    });
})();
</script>
@endpush
