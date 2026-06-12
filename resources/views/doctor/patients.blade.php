@extends('layouts.app')
@section('title', 'Patient Records')
@section('content')
    <h1>Patient Records</h1>
    <p class="page-subtitle">The patient database. Search by name or email.</p>

    <form action="{{ route('doctor.patients.index') }}" method="GET" class="form-group" style="display:flex;gap:8px;max-width:520px;">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search patients...">
        <button type="submit" class="btn">Search</button>
    </form>

    @if($patients->isEmpty())
        <p class="muted">No patients found.</p>
    @else
        <table>
            <tr><th>Name</th><th>Email</th><th>Sex</th><th>Blood Type</th><th></th></tr>
            @foreach($patients as $p)
                <tr>
                    <td>{{ $p->user?->fullName() }}</td>
                    <td>{{ $p->user?->email }}</td>
                    <td>{{ $p->sex ?? '—' }}</td>
                    <td>{{ $p->blood_type ?? '—' }}</td>
                    <td class="row-actions">
                        <a href="{{ route('doctor.patients.show', $p->patient_id) }}" class="btn btn-small">Open Chart</a>
                    </td>
                </tr>
            @endforeach
        </table>
        <div class="pagination-wrap">{{ $patients->links() }}</div>
    @endif
@endsection
