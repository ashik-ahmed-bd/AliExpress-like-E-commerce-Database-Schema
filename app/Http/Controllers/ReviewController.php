<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $this->authorize('create', [Review::class, $product]);

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        $review = $product->reviews()->create($validated);

        return response()->json($review, 201);
    }
}
