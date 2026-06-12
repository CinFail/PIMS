@extends('layouts.app')
@section('title', 'Patient Information')
@section('content')
    <h1>Patient Information</h1>
    <p class="page-subtitle">Look up patients during check-in.</p>

    <p><a href="{{ route('receptionist.patients.create') }}" class="btn">Add New Patient</a></p>

    <form action="{{ route('receptionist.patients.index') }}" method="GET" class="form-group" style="display:flex;gap:8px;max-width:520px;">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search patients...">
        <button type="submit" class="btn">Search</button>
    </form>

    @if($patients->isEmpty())
        <p class="muted">No patients found.</p>
    @else
        <table>
            <tr><th>Name</th><th>Email</th><th>Mobile</th><th></th></tr>
            @foreach($patients as $p)
                <tr>
                    <td>{{ $p->user?->fullName() }}</td>
                    <td>{{ $p->user?->email }}</td>
                    <td>{{ $p->user?->mobile_number ?? $p->contact_number ?? '—' }}</td>
                    <td class="row-actions">
                        <a href="{{ route('receptionist.patients.show', $p->patient_id) }}" class="btn btn-small">View</a>
                    </td>
                </tr>
            @endforeach
        </table>
        <div class="pagination-wrap">{{ $patients->links() }}</div>
    @endif
@endsection
