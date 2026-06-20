@extends('layouts.app')
@section('title', 'Add Category')
@section('content')
    <h1>Add Lab Category</h1>

    <div class="btn-row">
        <a href="{{ route('admin.lab-categories.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.lab-categories.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="category_name">Category Name</label>
            <input type="text" name="category_name" id="category_name" value="{{ old('category_name') }}" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description">{{ old('description') }}</textarea>
        </div>
        <button type="submit" class="btn"><i class="bi bi-save"></i> Save</button>
    </form>
@endsection
