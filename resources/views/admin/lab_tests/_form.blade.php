<div class="form-group">
    <label for="lab_category_id">Category</label>
    <select name="lab_category_id" id="lab_category_id" required>
        <option value="">-- Select --</option>
        @foreach($categories as $c)
            <option value="{{ $c->lab_category_id }}" @selected(old('lab_category_id', $test?->lab_category_id)==$c->lab_category_id)>{{ $c->category_name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label for="test_name">Test Name</label>
    <input type="text" name="test_name" id="test_name" value="{{ old('test_name', $test?->test_name) }}" required>
</div>
<div class="form-group">
    <label for="default_unit">Default Unit</label>
    <input type="text" name="default_unit" id="default_unit" value="{{ old('default_unit', $test?->default_unit) }}">
</div>
<div class="form-group">
    <label for="default_reference_range">Default Reference Range</label>
    <input type="text" name="default_reference_range" id="default_reference_range" value="{{ old('default_reference_range', $test?->default_reference_range) }}">
</div>
<div class="checkbox-row">
    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $test?->is_active ?? true))>
    <label for="is_active">Active</label>
</div>
