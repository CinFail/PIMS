<?php

namespace App\Http\Controllers\MedTech;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabResult;
use App\Models\LabResultRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SoftCopyController extends Controller
{
    /** Show pending soft-copy requests from patients and doctors. */
    public function index()
    {
        $requests = LabResultRequest::with([
            'result.requestItem.test',
            'patient.user',
            'doctor.user',
        ])
            ->where('status', 'Pending')
            ->orderBy('requested_at')
            ->get();

        return view('medtech.soft_copy', compact('requests'));
    }

    /** Fulfill a request by uploading the soft-copy file. */
    public function fulfill(Request $request, int $requestId)
    {
        $softRequest = LabResultRequest::with('result')->findOrFail($requestId);
        $medtech = Auth::user()->medTechProfile;

        $data = $request->validate([
            'result_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $filePath = $request->file('result_file')->store('lab_results', 'public');

        DB::transaction(function () use ($softRequest, $medtech, $filePath) {
            // Attach the uploaded file to the result.
            if ($softRequest->result) {
                $softRequest->result->update(['result_file_path' => $filePath]);
            }

            $softRequest->update([
                'status'       => 'Fulfilled',
                'fulfilled_by' => $medtech?->medtech_id,
                'fulfilled_at' => now(),
            ]);
        });

        $who = $softRequest->patient_id ? 'patient' : 'doctor';

        AuditLogger::log(
            'UPLOAD', 'Laboratory', 'lab_result_requests', $softRequest->result_request_id,
            "MedTech uploaded a {$who}-requested laboratory result soft copy"
        );

        return back()->with('status', 'Soft copy uploaded and request marked as fulfilled.');
    }
}
