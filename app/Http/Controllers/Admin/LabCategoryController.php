<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabTestCategory;
use Illuminate\Http\Request;

class LabCategoryController extends Controller
{
    /** List all categories. */
    public function index()
    {
        $categories = LabTestCategory::withCount('tests')->orderBy('category_name')->get();

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

    /** Delete a category (only if it has no tests). */
    public function destroy(int $id)
    {
        $category = LabTestCategory::withCount('tests')->findOrFail($id);

        if ($category->tests_count > 0) {
            return back()->withErrors(['delete' => 'Cannot delete: this category still has lab tests.']);
        }

        $category->delete();

        AuditLogger::log('DELETE', 'Maintenance', 'lab_test_categories', $id, 'Deleted lab test category');

        return redirect()->route('admin.lab-categories.index')->with('status', 'Category deleted.');
    }
}
