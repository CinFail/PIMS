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
        'lab_appointments'  => 'lab_appointment_id',
        'appointments'      => 'appointment_id',
        'prescriptions'     => 'prescription_id',
    ];

    private const TABLE_LABELS = [
        'diagnoses'         => 'Diagnosis',
        'consultations'     => 'Consultation',
        'lab_results'       => 'Lab Result',
        'lab_requests'      => 'Lab Request',
        'lab_request_items' => 'Lab Request Item',
        'lab_appointments'  => 'Lab Appointment',
        'appointments'      => 'Appointment',
        'prescriptions'     => 'Prescription',
    ];

    public function index()
    {
        $requests = VoidRequest::with('requester', 'reviewer')
            ->orderByRaw("FIELD(status,'Pending','Approved','Rejected')")
            ->orderByDesc('created_at')
            ->paginate(20);

        // check live record state to know which approved voids were later restored
        // (avoids adding 'Restored' to the ENUM just for display)
        $restoredVrIds = [];
        foreach ($requests as $vr) {
            if ($vr->status !== 'Approved') continue;
            $pk = self::VOIDABLE[$vr->table_name] ?? null;
            if (! $pk) continue;
            $record = DB::table($vr->table_name)->where($pk, $vr->record_id)->first();
            if (! $record) continue;
            $isRestored = ($vr->table_name === 'lab_appointments')
                ? $record->status !== 'Cancelled'
                : ! $record->is_voided;
            if ($isRestored) {
                $restoredVrIds[] = $vr->id;
            }
        }

        $voidableTables = array_keys(self::VOIDABLE);
        $tableLabels    = self::TABLE_LABELS;

        return view('admin.void_requests', compact('requests', 'voidableTables', 'tableLabels', 'restoredVrIds'));
    }

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
            $patient = $user->patientProfile;
            if ($data['table_name'] === 'appointments') {
                $appt = DB::table('appointments')->where('appointment_id', $data['record_id'])->first();
                if (! $appt || ! $patient || $appt->patient_id !== $patient->patient_id) {
                    abort(403, 'That appointment does not belong to you.');
                }
            } elseif ($data['table_name'] === 'lab_appointments') {
                $labAppt = DB::table('lab_appointments')->where('lab_appointment_id', $data['record_id'])->first();
                if (! $labAppt || ! $patient || $labAppt->patient_id !== $patient->patient_id) {
                    abort(403, 'That lab appointment does not belong to you.');
                }
            } else {
                abort(403, 'Patients may only submit void requests for their own appointments.');
            }
        } elseif (! $user->doctorProfile && ! $user->medTechProfile && ! $user->hasRole('super_admin')) {
            abort(403, 'You do not have permission to submit void requests.');
        }

        $pk     = self::VOIDABLE[$data['table_name']];
        $record = DB::table($data['table_name'])->where($pk, $data['record_id'])->first();

        if (! $record) {
            return back()->withErrors(['reason' => 'Record not found.']);
        }

        $alreadyVoided = ($data['table_name'] === 'lab_appointments')
            ? $record->status === 'Cancelled'
            : (bool) $record->is_voided;

        if ($alreadyVoided) {
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

    public function approve(int $id)
    {
        $vr = VoidRequest::where('status', 'Pending')->findOrFail($id);
        $pk = self::VOIDABLE[$vr->table_name] ?? 'id';

        DB::transaction(function () use ($vr, $pk) {
            if ($vr->table_name === 'lab_appointments') {
                DB::table('lab_appointments')->where($pk, $vr->record_id)->update(['status' => 'Cancelled']);
            } else {
                DB::table($vr->table_name)->where($pk, $vr->record_id)->update([
                    'is_voided'        => 1,
                    'void_at'          => now(),
                    'void_reason'      => $vr->reason,
                    'void_approved_by' => Auth::id(),
                ]);
            }

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

    public function reject(int $id)
    {
        $vr = VoidRequest::where('status', 'Pending')->findOrFail($id);

        $vr->update([
            'status'      => 'Rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        AuditLogger::log('REJECT', 'Clinical', $vr->table_name, $vr->record_id,
            'Admin rejected void request #' . $vr->id);

        return back()->with('status', 'Void request rejected.');
    }

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

        $alreadyVoided = ($data['table_name'] === 'lab_appointments')
            ? $record->status === 'Cancelled'
            : (bool) $record->is_voided;

        if ($alreadyVoided) {
            return back()->withErrors(['admin_reason' => 'This record is already voided.']);
        }

        if ($data['table_name'] === 'lab_appointments') {
            DB::table('lab_appointments')->where($pk, $data['record_id'])->update(['status' => 'Cancelled']);
        } else {
            DB::table($data['table_name'])->where($pk, $data['record_id'])->update([
                'is_voided'        => 1,
                'void_at'          => now(),
                'void_reason'      => $data['admin_reason'],
                'void_approved_by' => Auth::id(),
            ]);
        }

        AuditLogger::log('VOID', 'Clinical', $data['table_name'], $data['record_id'],
            'Admin directly voided record: ' . $data['admin_reason']);

        return back()->with('status', 'Record voided directly.');
    }

    public function restore(int $id)
    {
        $vr = VoidRequest::where('status', 'Approved')->findOrFail($id);
        $pk = self::VOIDABLE[$vr->table_name] ?? 'id';

        $record = DB::table($vr->table_name)->where($pk, $vr->record_id)->first();

        if (! $record) {
            return back()->withErrors(['restore' => 'Record not found.']);
        }

        if ($vr->table_name === 'lab_appointments') {
            if ($record->status !== 'Cancelled') {
                return back()->withErrors(['restore' => 'This lab appointment is not currently cancelled.']);
            }
        } else {
            if (! $record->is_voided) {
                return back()->withErrors(['restore' => 'This record is not currently voided.']);
            }

            // for appointments, make sure the slot isn't already claimed by another booking
            if ($vr->table_name === 'appointments' && ! empty($record->duty_session_id)) {
                $slotTaken = DB::table('appointments')
                    ->where('duty_session_id', $record->duty_session_id)
                    ->where('is_voided', 0)
                    ->whereNotIn('status_id', [4, 5, 6, 7])
                    ->where('appointment_id', '!=', $vr->record_id)
                    ->exists();
                if ($slotTaken) {
                    return back()->withErrors(['restore' => 'Doctor unavailable: the duty session is already booked by another patient\'s appointment.']);
                }
            }
        }

        if ($vr->table_name === 'lab_appointments') {
            DB::table('lab_appointments')->where($pk, $vr->record_id)->update(['status' => 'Scheduled']);
        } else {
            $restoreData = [
                'is_voided'        => 0,
                'void_at'          => null,
                'void_reason'      => null,
                'void_approved_by' => null,
            ];

            // reset status so isTaken() picks the slot back up
            if ($vr->table_name === 'appointments') {
                $restoreData['status_id'] = 1;
            }

            DB::table($vr->table_name)->where($pk, $vr->record_id)->update($restoreData);
        }

        AuditLogger::log('RESTORE', 'Clinical', $vr->table_name, $vr->record_id,
            'Admin restored voided record (reversed void request #' . $vr->id . ')');

        return back()->with('status', 'Record restored successfully.');
    }
}
