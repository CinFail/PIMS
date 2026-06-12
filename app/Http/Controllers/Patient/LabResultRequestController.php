<?php

namespace App\Http\Controllers\Patient;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabResult;
use App\Models\LabResultRequest;
use App\Models\PatientProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabResultRequestController extends Controller
{
    /** Show the patient's released results and their soft-copy requests. */
    public function index()
    {
        $patient = $this->patient();

        // Results that belong to this patient (joined through the request chain).
        $results = LabResult::whereHas('requestItem.request', function ($q) use ($patient) {
            $q->where('patient_id', $patient->patient_id);
        })->with('requestItem.test')->orderByDesc('created_at')->get();

        $myRequests = LabResultRequest::with('result.requestItem.test')
            ->where('patient_id', $patient->patient_id)
            ->orderByDesc('requested_at')->get();

        return view('patient.lab_requests', compact('results', 'myRequests'));
    }

    /** Submit a soft-copy request for one of the patient's own results. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'result_id' => ['required', 'integer', 'exists:lab_results,result_id'],
        ]);

        $patient = $this->patient();

        // Make sure the result really belongs to this patient.
        $owns = LabResult::where('result_id', $data['result_id'])
            ->whereHas('requestItem.request', fn ($q) => $q->where('patient_id', $patient->patient_id))
            ->exists();

        if (! $owns) {
            return back()->withErrors(['result_id' => 'That result does not belong to you.']);
        }

        $alreadyPending = LabResultRequest::where('result_id', $data['result_id'])
            ->where('patient_id', $patient->patient_id)
            ->where('status', 'Pending')
            ->exists();

        if ($alreadyPending) {
            return back()->withErrors(['result_id' => 'You already have a pending request for this result.']);
        }

        $req = LabResultRequest::create([
            'result_id'  => $data['result_id'],
            'patient_id' => $patient->patient_id,
            'status'     => 'Pending',
        ]);

        AuditLogger::log(
            'REQUEST', 'Laboratory', 'lab_result_requests', $req->result_request_id,
            'Patient requested a soft copy of a lab result'
        );

        return back()->with('status', 'Your lab result request has been sent to the MedTech.');
    }

    private function patient(): PatientProfile
    {
        return PatientProfile::firstOrCreate(['user_id' => Auth::id()]);
    }
}
