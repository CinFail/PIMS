<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    /** List all lab tests. */
    public function index()
    {
        $tests = LabTest::with('category')->orderBy('test_name')->paginate(15);

        return view('admin.lab_tests.index', compact('tests'));
    }

    /** Show the create form. */
    public function create()
    {
        $categories = LabTestCategory::orderBy('category_name')->get();

        return view('admin.lab_tests.create', compact('categories'));
    }

    /** Save a new lab test. */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $test = LabTest::create($data);

        AuditLogger::log('CREATE', 'Maintenance', 'lab_tests', $test->lab_test_id, 'Created lab test');

        return redirect()->route('admin.lab-tests.index')->with('status', 'Lab test created.');
    }

    /** Show the edit form. */
    public function edit(int $id)
    {
        $test = LabTest::findOrFail($id);
        $categories = LabTestCategory::orderBy('category_name')->get();

        return view('admin.lab_tests.edit', compact('test', 'categories'));
    }

    /** Save edits. */
    public function update(Request $request, int $id)
    {
        $test = LabTest::findOrFail($id);
        $data = $this->validateData($request);

        $test->update($data);

        AuditLogger::log('UPDATE', 'Maintenance', 'lab_tests', $test->lab_test_id, 'Updated lab test');

        return redirect()->route('admin.lab-tests.index')->with('status', 'Lab test updated.');
    }

    /** Delete a lab test. */
    public function destroy(int $id)
    {
        $test = LabTest::findOrFail($id);
        $test->delete();

        AuditLogger::log('DELETE', 'Maintenance', 'lab_tests', $id, 'Deleted lab test');

        return redirect()->route('admin.lab-tests.index')->with('status', 'Lab test deleted.');
    }

    /** Shared validation rules for create + update. */
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
