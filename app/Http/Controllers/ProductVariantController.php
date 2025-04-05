<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'sku' => 'required|string|unique:product_variants,sku',
            'variant_name' => 'required|string|max:255',
            'price_adjustment' => 'required|numeric',
            'quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('variant-images', 'public');
        }

        $variant = $product->variants()->create($validated);

        return response()->json($variant, 201);
    }
}
