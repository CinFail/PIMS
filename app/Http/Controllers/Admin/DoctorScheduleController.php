<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\DoctorDutySession;
use App\Models\DoctorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorScheduleController extends Controller
{
    /** List all non-voided duty sessions, newest date first. */
    public function index()
    {
        $sessions = DoctorDutySession::with('doctor.user')
            ->where('is_voided', 0)
            ->orderByDesc('duty_date')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('admin.doctor-schedules.index', compact('sessions'));
    }

    /** Form to add a new duty session. */
    public function create()
    {
        $doctors = $this->activeDoctors();

        return view('admin.doctor-schedules.create', compact('doctors'));
    }

    /** Save a new duty session. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id'  => ['required', 'integer', 'exists:doctor_profiles,doctor_id'],
            'duty_date'  => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'status'     => ['required', 'in:Scheduled,Ongoing,Completed,Cancelled'],
        ]);

        // Extra guard: doctor must still be active (the exists rule only checks the row exists).
        abort_unless(
            DoctorProfile::where('doctor_id', $data['doctor_id'])->where('is_active', 1)->exists(),
            422,
            'The selected doctor is not active.'
        );

        $session = DoctorDutySession::create([
            'doctor_id'   => $data['doctor_id'],
            'duty_date'   => $data['duty_date'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'status'      => $data['status'],
            'assigned_by' => Auth::id(),
        ]);

        AuditLogger::log(
            'CREATE', 'Doctor', 'doctor_duty_sessions', $session->duty_session_id,
            "Admin created a duty session for doctor_id {$session->doctor_id} on {$session->duty_date}"
        );

        return redirect()->route('admin.doctor-schedules.index')
            ->with('status', 'Duty session created successfully.');
    }

    /** Form to edit an existing duty session. */
    public function edit(int $id)
    {
        $session = DoctorDutySession::where('is_voided', 0)->findOrFail($id);
        $doctors = $this->activeDoctors();

        return view('admin.doctor-schedules.edit', compact('session', 'doctors'));
    }

    /** Save changes to an existing duty session. */
    public function update(Request $request, int $id)
    {
        $session = DoctorDutySession::where('is_voided', 0)->findOrFail($id);

        $data = $request->validate([
            'doctor_id'  => ['required', 'integer', 'exists:doctor_profiles,doctor_id'],
            'duty_date'  => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'status'     => ['required', 'in:Scheduled,Ongoing,Completed,Cancelled'],
        ]);

        $old = $session->only(['doctor_id', 'duty_date', 'start_time', 'end_time', 'status']);

        $session->update([
            'doctor_id'   => $data['doctor_id'],
            'duty_date'   => $data['duty_date'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'status'      => $data['status'],
            'assigned_by' => Auth::id(),
        ]);

        AuditLogger::log(
            'UPDATE', 'Doctor', 'doctor_duty_sessions', $session->duty_session_id,
            "Admin updated duty session for doctor_id {$session->doctor_id} on {$session->duty_date}",
            $old,
            $data
        );

        return redirect()->route('admin.doctor-schedules.index')
            ->with('status', 'Duty session updated successfully.');
    }

    /** Delete a duty session, voiding any active appointments linked to it. */
    public function destroy(int $id)
    {
        $session = DoctorDutySession::where('is_voided', 0)->findOrFail($id);

        $voided = $session->appointments()->where('is_voided', 0)->update([
            'is_voided'   => 1,
            'void_at'     => now(),
            'void_reason' => 'Doctor duty session cancelled by admin',
        ]);

        $session->update([
            'is_voided'   => 1,
            'void_at'     => now(),
            'void_reason' => 'Deleted by admin',
        ]);

        AuditLogger::log(
            'DELETE', 'Doctor', 'doctor_duty_sessions', $id,
            "Admin deleted duty session (doctor_id {$session->doctor_id}, date {$session->duty_date})"
            .($voided ? "; {$voided} linked appointment(s) voided" : '')
        );

        return redirect()->route('admin.doctor-schedules.index')
            ->with('status', 'Duty session deleted.'
                .($voided ? " {$voided} linked appointment(s) were also cancelled." : ''));
    }

    /** Active doctors ordered by last name, used in create/edit forms. */
    private function activeDoctors()
    {
        return DoctorProfile::with('user')
            ->where('is_active', 1)
            ->get()
            ->sortBy(fn ($d) => $d->user->last_name);
    }
}
