@extends('layouts.app')
@section('title', 'Lab Tests')
@section('content')
    <h1>Lab Tests</h1>
    <p class="page-subtitle">Maintenance: reusable laboratory test definitions.</p>

    <p><a href="{{ route('admin.lab-tests.create') }}" class="btn">Add Lab Test</a></p>

    <table>
        <tr><th>Test Name</th><th>Category</th><th>Unit</th><th>Reference Range</th><th>Active</th><th></th></tr>
        @foreach($tests as $t)
            <tr>
                <td>{{ $t->test_name }}</td>
                <td>{{ $t->category?->category_name }}</td>
                <td>{{ $t->default_unit ?? '—' }}</td>
                <td>{{ $t->default_reference_range ?? '—' }}</td>
                <td>{{ $t->is_active ? 'Yes' : 'No' }}</td>
                <td class="row-actions">
                    <a href="{{ route('admin.lab-tests.edit', $t->lab_test_id) }}" class="btn btn-small">Edit</a>
                    <form action="{{ route('admin.lab-tests.destroy', $t->lab_test_id) }}" method="POST" class="inline-form" onsubmit="return confirm('Delete this test?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-small btn-outline">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
    <div class="pagination-wrap">{{ $tests->links() }}</div>
@endsection
