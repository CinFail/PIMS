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
    /** Scheduled laboratory tests: pending / processing lab requests. */
    public function index()
    {
        $requests = LabRequest::with(['patient.user', 'items.test', 'items.result'])
            ->where('is_voided', 0)
            ->whereIn('status', ['Pending', 'Processing'])
            ->orderBy('request_at')
            ->get();

        return view('medtech.lab_requests', compact('requests'));
    }

    /** Form to encode the result for one test item. */
    public function createResult(int $itemId)
    {
        $item = LabRequestItem::with(['test', 'request.patient.user', 'result'])->findOrFail($itemId);

        return view('medtech.upload_result', compact('item'));
    }

    /** Save the result and optional uploaded soft-copy file. */
    public function storeResult(Request $request, int $itemId)
    {
        $item = LabRequestItem::with('request')->findOrFail($itemId);
        $medtech = Auth::user()->medTechProfile;

        $data = $request->validate([
            'result_value'    => ['nullable', 'string', 'max:255'],
            'unit'            => ['nullable', 'string', 'max:20'],
            'reference_range' => ['nullable', 'string', 'max:50'],
            'abnormal_flag'   => ['required', 'in:High,Low,Normal,Critical'],
            'remarks'         => ['nullable', 'string'],
            'result_file'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $filePath = null;
        if ($request->hasFile('result_file')) {
            // Stored under storage/app/public/lab_results (run: php artisan storage:link)
            $filePath = $request->file('result_file')->store('lab_results', 'public');
        }

        DB::transaction(function () use ($data, $item, $medtech, $filePath) {
            LabResult::updateOrCreate(
                ['request_item_id' => $item->request_item_id],
                [
                    'result_value'     => $data['result_value'] ?? null,
                    'unit'             => $data['unit'] ?? null,
                    'reference_range'  => $data['reference_range'] ?? null,
                    'abnormal_flag'    => $data['abnormal_flag'],
                    'remarks'          => $data['remarks'] ?? null,
                    'workflow_status'  => 'Released',
                    'result_file_path' => $filePath,
                    'performed_by'     => $medtech?->medtech_id,
                    'released_by'      => Auth::id(),
                    'result_at'        => now(),
                    'released_at'      => now(),
                ]
            );

            $item->update(['status' => 'Completed']);

            // If all items are completed, mark the whole request completed.
            $remaining = LabRequestItem::where('lab_request_id', $item->lab_request_id)
                ->where('status', '!=', 'Completed')->count();
            if ($remaining === 0) {
                $item->request->update(['status' => 'Completed']);
            } else {
                $item->request->update(['status' => 'Processing']);
            }
        });

        AuditLogger::log('UPLOAD', 'Laboratory', 'lab_results', $item->request_item_id, 'MedTech uploaded a laboratory test result');

        return redirect()->route('medtech.lab.index')->with('status', 'Result saved successfully.');
    }
}
