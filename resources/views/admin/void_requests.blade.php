@extends('layouts.app')
@section('title', 'Void Requests')
@section('content')
    <h1>Void Requests</h1>
    <p class="page-subtitle">Pending void requests submitted by doctors and medical technologists. Approve to permanently void the record, or reject to leave it unchanged.</p>

    {{-- Direct Void --}}
    <details class="form-card" style="margin-bottom:20px;">
        <summary style="font-weight:600;cursor:pointer;list-style:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-x-octagon"></i> Direct Void (bypass queue)
        </summary>
        <div style="margin-top:14px;">
            <p class="muted" style="margin-bottom:10px;">Immediately void a record without waiting for a request. Enter the exact table name and numeric record ID.</p>
            <form action="{{ route('admin.void.admin') }}" method="POST">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Table</label>
                        <select name="table_name" required>
                            <option value="">— Select Table —</option>
                            @foreach($tableLabels as $table => $label)
                                <option value="{{ $table }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Record ID</label>
                        <input type="number" name="record_id" min="1" required placeholder="e.g. 42">
                    </div>
                    <div class="form-group span-2">
                        <label>Reason</label>
                        <textarea name="admin_reason" required minlength="10" placeholder="Reason for directly voiding this record (min 10 characters)"></textarea>
                        @error('admin_reason') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-small"
                        onclick="return confirm('Void this record immediately? This cannot be undone without using the Restore action.')">
                    <i class="bi bi-x-octagon"></i> Void Directly
                </button>
            </form>
        </div>
    </details>

    @if($requests->isEmpty())
        <div class="empty-state">
            <i class="bi bi-x-octagon"></i>
            <p>No void requests found.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr>
                    <th>Record</th>
                    <th>Requested By</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
                @foreach($requests as $vr)
                    <tr>
                        <td>
                            <strong>{{ $tableLabels[$vr->table_name] ?? $vr->table_name }}</strong>
                            <span class="muted">#{{ $vr->record_id }}</span>
                        </td>
                        <td>{{ $vr->requester?->fullName() ?? 'Unknown' }}</td>
                        <td>{{ $vr->reason }}</td>
                        <td>
                            @if($vr->status === 'Pending')
                                <span class="tag" style="background:#e67e22;color:#fff;">Pending</span>
                            @elseif($vr->status === 'Approved')
                                <span class="tag" style="background:#27ae60;color:#fff;">Approved</span>
                                <div class="muted" style="font-size:0.8em;">by {{ $vr->reviewer?->fullName() }} &bull; {{ $vr->reviewed_at?->format('M d, Y') }}</div>
                            @else
                                <span class="tag" style="background:#7f8c8d;color:#fff;">Rejected</span>
                                <div class="muted" style="font-size:0.8em;">by {{ $vr->reviewer?->fullName() }} &bull; {{ $vr->reviewed_at?->format('M d, Y') }}</div>
                            @endif
                        </td>
                        <td class="muted">{{ $vr->created_at?->format('M d, Y g:i A') }}</td>
                        <td class="row-actions">
                            @if($vr->status === 'Pending')
                                <form action="{{ route('admin.void.approve', $vr->id) }}" method="POST" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-small"
                                            onclick="return confirm('Approve void? This will permanently mark the record as voided.')">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                <form action="{{ route('admin.void.reject', $vr->id) }}" method="POST" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-small btn-outline">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </form>
                            @elseif($vr->status === 'Approved')
                                <form action="{{ route('admin.void.restore', $vr->id) }}" method="POST" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-small btn-outline"
                                            onclick="return confirm('Restore this record? This will clear the void and make the record active again.')">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $requests->links() }}
        </div>
    @endif
@endsection
