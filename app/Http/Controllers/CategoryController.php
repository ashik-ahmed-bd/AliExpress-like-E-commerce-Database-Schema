<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::where('is_active', true)
            ->withCount('products')
            ->get();
    }

    public function show(Category $category)
    {
        return $category->load(['children', 'products']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('category-images', 'public');
        }

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }
}
