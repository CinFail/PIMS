@extends('layouts.app')
@section('title', 'Lab Categories')
@section('content')
    <h1>Lab Test Categories</h1>
    <p class="page-subtitle">Maintenance: groupings for laboratory tests.</p>

    <div class="btn-row">
        <a href="{{ route('admin.lab-categories.create') }}" class="btn">
            <i class="bi bi-plus-lg"></i> Add Category
        </a>
    </div>

    <div class="table-card">
        <table>
            <tr><th>Name</th><th>Description</th><th>Tests</th><th></th></tr>
            @foreach($categories as $c)
                <tr>
                    <td>{{ $c->category_name }}</td>
                    <td>{{ $c->description ?? '—' }}</td>
                    <td>{{ $c->tests_count }}</td>
                    <td class="row-actions">
                        <a href="{{ route('admin.lab-categories.edit', $c->lab_category_id) }}" class="btn btn-small">Edit</a>
                        <form action="{{ route('admin.lab-categories.destroy', $c->lab_category_id) }}" method="POST" class="inline-form" onsubmit="return confirm('Delete this category?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-small btn-outline">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection
