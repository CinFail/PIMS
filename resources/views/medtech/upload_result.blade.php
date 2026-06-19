@extends('layouts.app')
@section('title', 'Encode Result')
@section('content')
    <h1>Encode Laboratory Result</h1>
    <p class="page-subtitle">
        <strong>{{ $item->test?->test_name }}</strong> &bull;
        Patient: {{ $item->request?->patient?->user?->fullName() }}
    </p>

    <div class="btn-row">
        <a href="{{ route('medtech.lab.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('medtech.lab.result.store', $item->request_item_id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-card">
            <div class="form-section-title">Result</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="result_value">Result Value</label>
                    <input type="text" name="result_value" id="result_value"
                           value="{{ old('result_value', $item->result?->result_value) }}">
                </div>
                <div class="form-group">
                    <label for="unit">Unit</label>
                    <input type="text" name="unit" id="unit"
                           value="{{ old('unit', $item->result?->unit ?? $item->test?->default_unit) }}">
                </div>
                <div class="form-group">
                    <label for="reference_range">Reference Range</label>
                    <input type="text" name="reference_range" id="reference_range"
                           value="{{ old('reference_range', $item->result?->reference_range ?? $item->test?->default_reference_range) }}">
                </div>
                <div class="form-group">
                    <label for="abnormal_flag">Flag</label>
                    <select name="abnormal_flag" id="abnormal_flag">
                        @foreach(['Normal', 'High', 'Low', 'Critical'] as $flag)
                            <option value="{{ $flag }}"
                                @selected(old('abnormal_flag', $item->result?->abnormal_flag) == $flag)>
                                {{ $flag }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group span-2">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks">{{ old('remarks', $item->result?->remarks) }}</textarea>
                </div>
                <div class="form-group span-2">
                    <label for="result_file">Soft Copy (PDF or image)</label>
                    <p class="muted" style="font-size:0.85em;margin-bottom:6px;">
                        Either a result value <strong>or</strong> a file is required. Both are accepted.
                    </p>
                    <input type="file" name="result_file" id="result_file" accept=".pdf,.jpg,.jpeg,.png">
                    @if($item->result?->result_file_path)
                        <span style="color:var(--text-muted);font-size:0.85em;display:block;margin-top:4px;">
                            Current file: <a href="{{ asset('storage/'.$item->result->result_file_path) }}" target="_blank">view file</a>
                            (leave blank to keep existing)
                        </span>
                    @endif
                    @error('result_file') <span class="field-error">{{ $message }}</span> @enderror
                    @error('result_value') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn"><i class="bi bi-save"></i> Save Result</button>
    </form>
@endsection
