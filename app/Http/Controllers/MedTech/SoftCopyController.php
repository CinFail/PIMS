<?php

namespace App\Http\Controllers\MedTech;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabResultRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SoftCopyController extends Controller
{
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

    public function fulfill(Request $request, int $requestId)
    {
        $softRequest = LabResultRequest::with('result')->findOrFail($requestId);
        $medtech     = Auth::user()->medTechProfile;

        $hasExistingFile = ! empty($softRequest->result?->result_file_path);

        if ($hasExistingFile) {
            // file already stored during result encoding, just mark fulfilled
            DB::transaction(function () use ($softRequest, $medtech) {
                $softRequest->update([
                    'status'       => 'Fulfilled',
                    'fulfilled_by' => $medtech?->medtech_id,
                    'fulfilled_at' => now(),
                ]);
            });
        } else {
            // no file yet, must upload now
            $request->validate([
                'result_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ]);

            $filePath = $request->file('result_file')->store('lab_results', 'public');

            DB::transaction(function () use ($softRequest, $medtech, $filePath) {
                if ($softRequest->result) {
                    $softRequest->result->update(['result_file_path' => $filePath]);
                }

                $softRequest->update([
                    'status'       => 'Fulfilled',
                    'fulfilled_by' => $medtech?->medtech_id,
                    'fulfilled_at' => now(),
                ]);
            });
        }

        $who = $softRequest->patient_id ? 'patient' : 'doctor';

        AuditLogger::log(
            'UPLOAD', 'Laboratory', 'lab_result_requests', $softRequest->result_request_id,
            "MedTech fulfilled a {$who}-requested soft copy"
        );

        return back()->with('status', 'Soft copy request marked as fulfilled.');
    }
}
