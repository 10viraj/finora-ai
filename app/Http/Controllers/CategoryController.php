<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $categories = Category::forUser($userId)
            ->parentsOnly()
            ->with(['children' => function ($q) use ($userId) {
                $q->forUser($userId);
            }])
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $category = Category::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'icon' => $validated['icon'] ?? 'bi-tag',
            'color' => $validated['color'] ?? '#64748b',
            'parent_id' => $validated['parent_id'] ?? null,
            'is_default' => false,
            'status' => true,
        ]);

        return back()->with('success', "Category '{$category->name}' created successfully.");
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== null && $category->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'boolean'],
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->user_id === null || $category->user_id !== Auth::id()) {
            abort(403, 'System default categories cannot be deleted.');
        }

        $category->delete();

        return back()->with('success', 'Category removed successfully. Historic transactions remain preserved.');
    }
}

