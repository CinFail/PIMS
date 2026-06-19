@extends('layouts.app')
@section('title', 'Scheduled Lab Tests')
@section('content')
    <h1>Scheduled Laboratory Tests</h1>
    <p class="page-subtitle">Pending and in-progress lab requests. Encode a result then release it to make it visible to the patient.</p>

    @if($requests->isEmpty())
        <div class="empty-state">
            <i class="bi bi-eyedropper"></i>
            <p>No pending lab requests.</p>
        </div>
    @else
        @foreach($requests as $req)
            <div class="box">
                <p>
                    <strong>Request #{{ $req->lab_request_id }}</strong> —
                    {{ $req->patient?->user?->fullName() }} —
                    <span class="tag">{{ $req->status }}</span>
                    <span class="muted">({{ $req->request_at?->format('M d, Y g:i A') }})</span>
                </p>
                <p class="muted" style="margin-bottom:10px;">
                    @if($req->doctor)
                        Referred by Dr. {{ $req->doctor?->user?->fullName() }}
                    @else
                        Patient self-requested (no doctor consultation)
                    @endif
                    @if($req->labAppointment)
                        &mdash; Lab appointment: {{ $req->labAppointment->scheduled_at?->format('M d, Y g:i A') }}
                    @endif
                </p>
                <table>
                    <tr><th>Test</th><th>Status</th><th>Result</th><th>Workflow</th><th></th></tr>
                    @foreach($req->items as $item)
                        <tr>
                            <td>{{ $item->test?->test_name }}</td>
                            <td>{{ $item->status }}</td>
                            <td>
                                @if($item->result)
                                    {{ $item->result->result_value ?? '—' }} {{ $item->result->unit }}
                                    @if($item->result->abnormal_flag !== 'Normal')
                                        <span class="tag" style="background:#e74c3c;color:#fff;">{{ $item->result->abnormal_flag }}</span>
                                    @endif
                                @else
                                    <span class="muted">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($item->result)
                                    <span class="tag">{{ $item->result->workflow_status }}</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="row-actions">
                                <a href="{{ route('medtech.lab.result.create', $item->request_item_id) }}" class="btn btn-small btn-outline">
                                    {{ $item->result ? 'Update' : 'Encode' }}
                                </a>

                                @if($item->result && $item->result->workflow_status === 'Encoded')
                                    <form action="{{ route('medtech.lab.result.release', $item->request_item_id) }}" method="POST" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn-small"
                                                onclick="return confirm('Release this result? The patient will be able to view it.')">
                                            <i class="bi bi-send"></i> Release
                                        </button>
                                    </form>
                                @endif

                                @if($item->result && !$item->result->is_voided)
                                    <button type="button" class="btn btn-small btn-outline"
                                            onclick="toggleVoidForm('void-result-{{ $item->result->result_id }}')">
                                        Request Void
                                    </button>
                                    <div id="void-result-{{ $item->result->result_id }}" style="display:none;margin-top:6px;padding:8px;border:1px solid #d0d0d0;border-radius:6px;background:#fafafa;">
                                        <form action="{{ route('void.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="table_name" value="lab_results">
                                            <input type="hidden" name="record_id" value="{{ $item->result->result_id }}">
                                            <div class="form-group" style="margin-bottom:6px;">
                                                <textarea name="reason" placeholder="Reason for void request (min 10 characters)" style="width:100%;min-height:60px;" required minlength="10"></textarea>
                                            </div>
                                            <div style="display:flex;gap:6px;">
                                                <button type="submit" class="btn btn-small">Submit Request</button>
                                                <button type="button" class="btn btn-small btn-outline"
                                                        onclick="toggleVoidForm('void-result-{{ $item->result->result_id }}')">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif
@endsection
@push('scripts')
<script>
function toggleVoidForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
