<?php

namespace App\Http\Controllers\MedTech;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LabController extends Controller
{
    public function index()
    {
        $requests = LabRequest::with(['patient.user', 'items.test', 'items.result', 'doctor.user', 'labAppointment'])
            ->where('is_voided', 0)
            ->whereIn('status', ['Pending', 'Processing'])
            ->orderBy('request_at')
            ->get();

        return view('medtech.lab_requests', compact('requests'));
    }

    public function createResult(int $itemId)
    {
        $item = LabRequestItem::with(['test', 'request.patient.user', 'result'])->findOrFail($itemId);

        return view('medtech.upload_result', compact('item'));
    }

    public function storeResult(Request $request, int $itemId)
    {
        $item    = LabRequestItem::with(['request', 'result'])->findOrFail($itemId);
        $medtech = Auth::user()->medTechProfile;

        $hasExistingFile = $item->result?->result_file_path !== null;

        $data = $request->validate([
            'result_value'    => ['nullable', 'numeric'],
            'unit'            => ['nullable', 'string', 'max:20'],
            'reference_range' => ['nullable', 'string', 'max:50'],
            'abnormal_flag'   => ['required', 'in:High,Low,Normal,Critical'],
            'remarks'         => ['nullable', 'string'],
            'result_file'     => array_merge(
                $hasExistingFile ? ['nullable'] : ['required'],
                ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']
            ),
        ]);

        $filePath = null;
        if ($request->hasFile('result_file')) {
            $filePath = $request->file('result_file')->store('lab_results', 'public');
        }

        DB::transaction(function () use ($data, $item, $medtech, $filePath) {
            $resultData = [
                'result_value'    => $data['result_value'] ?? null,
                'unit'            => $data['unit'] ?? null,
                'reference_range' => $data['reference_range'] ?? null,
                'abnormal_flag'   => $data['abnormal_flag'],
                'remarks'         => $data['remarks'] ?? null,
                'workflow_status' => 'Encoded',
                'performed_by'    => $medtech?->medtech_id,
                'result_at'       => now(),
                'released_by'     => null,
                'released_at'     => null,
            ];

            // keep existing file if no new one was uploaded
            if ($filePath !== null) {
                $resultData['result_file_path'] = $filePath;
            }

            LabResult::updateOrCreate(
                ['request_item_id' => $item->request_item_id],
                $resultData
            );

            $item->update(['status' => 'Processing']);
            $item->request->update(['status' => 'Processing']);
        });

        AuditLogger::log('UPLOAD', 'Laboratory', 'lab_results', $item->request_item_id,
            'MedTech encoded a laboratory test result — awaiting release');

        return redirect()->route('medtech.lab.index')->with('status', 'Result encoded. Use the Release button to make it visible to the patient.');
    }

    public function releaseResult(int $itemId)
    {
        $item = LabRequestItem::with(['request.labAppointment', 'result'])->findOrFail($itemId);

        if (! $item->result || $item->result->workflow_status !== 'Encoded' || $item->result->is_voided) {
            return back()->withErrors(['release' => 'This result cannot be released in its current state.']);
        }

        DB::transaction(function () use ($item) {
            $item->result->update([
                'workflow_status' => 'Released',
                'released_by'     => Auth::id(),
                'released_at'     => now(),
            ]);

            $item->update(['status' => 'Completed']);

            $remaining = LabRequestItem::where('lab_request_id', $item->lab_request_id)
                ->where('status', '!=', 'Completed')
                ->count();

            if ($remaining === 0) {
                $item->request->update(['status' => 'Completed']);
                $item->request->labAppointment?->update(['status' => 'Completed']);
            }
        });

        AuditLogger::log('UPDATE', 'Laboratory', 'lab_results', $item->result->result_id,
            'MedTech released a laboratory result — now visible to patient');

        return redirect()->route('medtech.lab.index')->with('status', 'Result released. Patient can now view it.');
    }
}
