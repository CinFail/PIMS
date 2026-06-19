<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\VoidRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoidRequestController extends Controller
{
    private const VOIDABLE = [
        'diagnoses'         => 'diagnosis_id',
        'consultations'     => 'consultation_id',
        'lab_results'       => 'result_id',
        'lab_requests'      => 'lab_request_id',
        'lab_request_items' => 'request_item_id',
        'appointments'      => 'appointment_id',
        'prescriptions'     => 'prescription_id',
    ];

    private const TABLE_LABELS = [
        'diagnoses'         => 'Diagnosis',
        'consultations'     => 'Consultation',
        'lab_results'       => 'Lab Result',
        'lab_requests'      => 'Lab Request',
        'lab_request_items' => 'Lab Request Item',
        'appointments'      => 'Appointment',
        'prescriptions'     => 'Prescription',
    ];

    /** Admin queue: pending first, then resolved. */
    public function index()
    {
        $requests = VoidRequest::with('requester', 'reviewer')
            ->orderByRaw("FIELD(status,'Pending','Approved','Rejected')")
            ->orderByDesc('created_at')
            ->paginate(20);

        $voidableTables = array_keys(self::VOIDABLE);
        $tableLabels    = self::TABLE_LABELS;

        return view('admin.void_requests', compact('requests', 'voidableTables', 'tableLabels'));
    }

    /** Doctor, MedTech, or Patient (for own appointments) submits a void request. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'table_name' => ['required', 'string', 'in:' . implode(',', array_keys(self::VOIDABLE))],
            'record_id'  => ['required', 'integer', 'min:1'],
            'reason'     => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('patient')) {
            // Patients may only request voids for their own appointments.
            if ($data['table_name'] !== 'appointments') {
                abort(403, 'Patients may only submit void requests for their own appointments.');
            }
            $patient = $user->patientProfile;
            $appt    = DB::table('appointments')->where('appointment_id', $data['record_id'])->first();
            if (! $appt || ! $patient || $appt->patient_id !== $patient->patient_id) {
                abort(403, 'That appointment does not belong to you.');
            }
        } elseif (! $user->doctorProfile && ! $user->medTechProfile && ! $user->hasRole('super_admin')) {
            abort(403, 'You do not have permission to submit void requests.');
        }

        $pk     = self::VOIDABLE[$data['table_name']];
        $record = DB::table($data['table_name'])->where($pk, $data['record_id'])->first();

        if (! $record) {
            return back()->withErrors(['reason' => 'Record not found.']);
        }

        if ($record->is_voided) {
            return back()->withErrors(['reason' => 'This record is already voided.']);
        }

        $alreadyPending = VoidRequest::where('table_name', $data['table_name'])
            ->where('record_id', $data['record_id'])
            ->where('status', 'Pending')
            ->exists();

        if ($alreadyPending) {
            return back()->withErrors(['reason' => 'A void request for this record is already pending admin review.']);
        }

        VoidRequest::create([
            'table_name'   => $data['table_name'],
            'record_id'    => $data['record_id'],
            'requested_by' => Auth::id(),
            'reason'       => $data['reason'],
            'status'       => 'Pending',
        ]);

        AuditLogger::log('VOID', 'Clinical', $data['table_name'], $data['record_id'],
            'Void request submitted: ' . $data['reason']);

        return back()->with('status', 'Void request submitted. Awaiting admin approval.');
    }

    /** Admin approves — sets is_voided = 1 on the target record. */
    public function approve(int $id)
    {
        $vr = VoidRequest::where('status', 'Pending')->findOrFail($id);
        $pk = self::VOIDABLE[$vr->table_name] ?? 'id';

        DB::transaction(function () use ($vr, $pk) {
            DB::table($vr->table_name)->where($pk, $vr->record_id)->update([
                'is_voided'        => 1,
                'void_at'          => now(),
                'void_reason'      => $vr->reason,
                'void_approved_by' => Auth::id(),
            ]);

            $vr->update([
                'status'      => 'Approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        AuditLogger::log('APPROVE', 'Clinical', $vr->table_name, $vr->record_id,
            'Admin approved void request #' . $vr->id);

        return back()->with('status', 'Void approved. Record has been voided.');
    }

    /** Admin rejects the request — record is untouched. */
    public function reject(int $id)
    {
        $vr = VoidRequest::where('status', 'Pending')->findOrFail($id);

        $vr->update([
            'status'      => 'Rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        AuditLogger::log('UPDATE', 'Clinical', $vr->table_name, $vr->record_id,
            'Admin rejected void request #' . $vr->id);

        return back()->with('status', 'Void request rejected.');
    }

    /** Admin directly voids a record without going through the request queue. */
    public function adminVoid(Request $request)
    {
        $data = $request->validate([
            'table_name'   => ['required', 'string', 'in:' . implode(',', array_keys(self::VOIDABLE))],
            'record_id'    => ['required', 'integer', 'min:1'],
            'admin_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $pk     = self::VOIDABLE[$data['table_name']];
        $record = DB::table($data['table_name'])->where($pk, $data['record_id'])->first();

        if (! $record) {
            return back()->withErrors(['admin_reason' => 'Record not found.']);
        }

        if ($record->is_voided) {
            return back()->withErrors(['admin_reason' => 'This record is already voided.']);
        }

        DB::table($data['table_name'])->where($pk, $data['record_id'])->update([
            'is_voided'        => 1,
            'void_at'          => now(),
            'void_reason'      => $data['admin_reason'],
            'void_approved_by' => Auth::id(),
        ]);

        AuditLogger::log('VOID', 'Clinical', $data['table_name'], $data['record_id'],
            'Admin directly voided record: ' . $data['admin_reason']);

        return back()->with('status', 'Record voided directly.');
    }

    /** Admin restores a voided record, reversing an approved void. */
    public function restore(int $id)
    {
        $vr = VoidRequest::where('status', 'Approved')->findOrFail($id);
        $pk = self::VOIDABLE[$vr->table_name] ?? 'id';

        $record = DB::table($vr->table_name)->where($pk, $vr->record_id)->first();

        if (! $record) {
            return back()->withErrors(['restore' => 'Record not found.']);
        }

        if (! $record->is_voided) {
            return back()->withErrors(['restore' => 'This record is not currently voided.']);
        }

        DB::table($vr->table_name)->where($pk, $vr->record_id)->update([
            'is_voided'        => 0,
            'void_at'          => null,
            'void_reason'      => null,
            'void_approved_by' => null,
        ]);

        AuditLogger::log('UPDATE', 'Clinical', $vr->table_name, $vr->record_id,
            'Admin restored voided record (reversed void request #' . $vr->id . ')');

        return back()->with('status', 'Record restored successfully.');
    }
}
