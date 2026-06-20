<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    public function index()
    {
        $tests = LabTest::with('category')
            ->orderBy('test_name')
            ->paginate(15);

        return view('admin.lab_tests.index', compact('tests'));
    }

    public function create()
    {
        $categories = LabTestCategory::where('is_active', 1)->orderBy('category_name')->get();

        return view('admin.lab_tests.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $test = LabTest::create($data);

        AuditLogger::log('CREATE', 'Maintenance', 'lab_tests', $test->lab_test_id, 'Created lab test');

        return redirect()->route('admin.lab-tests.index')->with('status', 'Lab test created.');
    }

    public function edit(int $id)
    {
        $test       = LabTest::findOrFail($id);
        $categories = LabTestCategory::where('is_active', 1)->orderBy('category_name')->get();

        return view('admin.lab_tests.edit', compact('test', 'categories'));
    }

    public function update(Request $request, int $id)
    {
        $test = LabTest::findOrFail($id);
        $data = $this->validateData($request);

        $test->update($data);

        AuditLogger::log('UPDATE', 'Maintenance', 'lab_tests', $test->lab_test_id, 'Updated lab test');

        return redirect()->route('admin.lab-tests.index')->with('status', 'Lab test updated.');
    }

    public function toggleActive(int $id)
    {
        $test      = LabTest::findOrFail($id);
        $newActive = ! $test->is_active;
        $test->update(['is_active' => $newActive]);

        $label = $newActive ? 'Active' : 'Inactive';

        AuditLogger::log('UPDATE', 'Maintenance', 'lab_tests', $test->lab_test_id,
            "Admin set lab test status to {$label}");

        return back()->with('status', "Lab test is now {$label}.");
    }

    public function destroy(int $id)
    {
        $test = LabTest::findOrFail($id);
        $test->update(['is_active' => 0]);

        AuditLogger::log('DELETE', 'Maintenance', 'lab_tests', $id, 'Deactivated lab test');

        return redirect()->route('admin.lab-tests.index')->with('status', 'Lab test deactivated.');
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'lab_category_id'         => ['required', 'integer', 'exists:lab_test_categories,lab_category_id'],
            'test_name'               => ['required', 'string', 'max:100'],
            'default_unit'            => ['nullable', 'string', 'max:20'],
            'default_reference_range' => ['nullable', 'string', 'max:50'],
            'is_active'               => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
