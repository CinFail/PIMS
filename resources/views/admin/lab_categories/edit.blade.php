@extends('layouts.app')
@section('title', 'Edit Category')
@section('content')
    <h1>Edit Lab Category</h1>

    <div class="btn-row">
        <a href="{{ route('lab-categories.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('lab-categories.update', $category->lab_category_id) }}" method="POST">
        @csrf @method('PUT')
        <div class="form-group">
            <label for="category_name">Category Name</label>
            <input type="text" name="category_name" id="category_name" value="{{ old('category_name', $category->category_name) }}" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description">{{ old('description', $category->description) }}</textarea>
        </div>
        <button type="submit" class="btn"><i class="bi bi-save"></i> Update</button>
    </form>
@endsection
