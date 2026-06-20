@extends('layouts.app')
@section('title', 'Add Lab Test')
@section('content')
    <h1>Add Lab Test</h1>

    <div class="btn-row">
        <a href="{{ route('admin.lab-tests.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.lab-tests.store') }}" method="POST">
        @csrf
        @include('admin.lab_tests._form', ['test' => null])
        <button type="submit" class="btn"><i class="bi bi-save"></i> Save</button>
    </form>
@endsection
