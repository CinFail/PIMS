<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabTestCategory;
use Illuminate\Http\Request;

class LabCategoryController extends Controller
{
    /** List active categories with their active test count. */
    public function index()
    {
        $categories = LabTestCategory::withCount(['tests' => fn ($q) => $q->where('is_active', 1)])
            ->where('is_active', 1)
            ->orderBy('category_name')
            ->get();

        return view('admin.lab_categories.index', compact('categories'));
    }

    /** Show the create form. */
    public function create()
    {
        return view('admin.lab_categories.create');
    }

    /** Save a new category. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_name' => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
        ]);

        $category = LabTestCategory::create($data);

        AuditLogger::log('CREATE', 'Maintenance', 'lab_test_categories', $category->lab_category_id, 'Created lab test category');

        return redirect()->route('admin.lab-categories.index')->with('status', 'Category created.');
    }

    /** Show the edit form. */
    public function edit(int $id)
    {
        $category = LabTestCategory::findOrFail($id);

        return view('admin.lab_categories.edit', compact('category'));
    }

    /** Save edits. */
    public function update(Request $request, int $id)
    {
        $category = LabTestCategory::findOrFail($id);

        $data = $request->validate([
            'category_name' => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
        ]);

        $category->update($data);

        AuditLogger::log('UPDATE', 'Maintenance', 'lab_test_categories', $category->lab_category_id, 'Updated lab test category');

        return redirect()->route('admin.lab-categories.index')->with('status', 'Category updated.');
    }

    /** Soft-delete: set is_active = 0. Blocked if active tests still exist. */
    public function destroy(int $id)
    {
        $category = LabTestCategory::withCount(['tests' => fn ($q) => $q->where('is_active', 1)])->findOrFail($id);

        if ($category->tests_count > 0) {
            return back()->withErrors(['delete' => 'Cannot deactivate: this category still has active lab tests. Deactivate the tests first.']);
        }

        $category->update(['is_active' => 0]);

        AuditLogger::log('DELETE', 'Maintenance', 'lab_test_categories', $id, 'Deactivated lab test category');

        return redirect()->route('admin.lab-categories.index')->with('status', 'Category deactivated.');
    }
}
