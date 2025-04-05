<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['seller', 'category'])
            ->where('is_active', true);

        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Sort products
        if ($request->has('sort')) {
            switch($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        }

        return $query->paginate(20);
    }

    public function show(Product $product)
    {
        return $product->load(['seller', 'category', 'variants', 'attributes']);
    }

    public function store(Request $request)
    {
        $seller = Auth::user()->seller;
        $this->authorize('create', [Product::class, $seller]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'featured_image' => 'nullable|image|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('product-images', 'public');
        }

        $validated['seller_id'] = $seller->id;
        $validated['slug'] = Str::slug($validated['name']);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }
}
