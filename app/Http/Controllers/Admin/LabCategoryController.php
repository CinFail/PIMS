<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabTestCategory;
use Illuminate\Http\Request;

class LabCategoryController extends Controller
{
    public function index()
    {
        $categories = LabTestCategory::withCount(['tests' => fn ($q) => $q->where('is_active', 1)])
            ->orderBy('category_name')
            ->get();

        return view('admin.lab_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.lab_categories.create');
    }

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

    public function edit(int $id)
    {
        $category = LabTestCategory::findOrFail($id);

        return view('admin.lab_categories.edit', compact('category'));
    }

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

    public function toggleActive(int $id)
    {
        $category = LabTestCategory::withCount(['tests' => fn ($q) => $q->where('is_active', 1)])->findOrFail($id);

        if ($category->is_active && $category->tests_count > 0) {
            return back()->withErrors([
                'toggle' => "Cannot deactivate: this category still has {$category->tests_count} active lab test(s). Deactivate the tests first.",
            ]);
        }

        $newActive = ! $category->is_active;
        $category->update(['is_active' => $newActive]);

        $label = $newActive ? 'Active' : 'Inactive';

        AuditLogger::log('UPDATE', 'Maintenance', 'lab_test_categories', $category->lab_category_id,
            "Admin set category status to {$label}");

        return back()->with('status', "Category is now {$label}.");
    }

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
