<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'attributes' => 'required|array',
            'attributes.*.attribute_name' => 'required|string|max:100',
            'attributes.*.attribute_value' => 'required|string|max:100',
        ]);

        $attributes = collect($validated['attributes'])->map(function ($attr) {
            return [
                'attribute_name' => $attr['attribute_name'],
                'attribute_value' => $attr['attribute_value'],
            ];
        });

        $product->attributes()->createMany($attributes);

        return response()->json(['message' => 'Attributes added successfully']);
    }
}
