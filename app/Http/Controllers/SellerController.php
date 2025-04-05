<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function index()
    {
        return Seller::with('user')->get();
    }

    public function show(Seller $seller)
    {
        return $seller->load('user');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('seller-logos', 'public');
        }

        $seller = Auth::user()->seller()->create($validated);

        return response()->json($seller, 201);
    }

    public function update(Request $request, Seller $seller)
    {
        $this->authorize('update', $seller);

        $validated = $request->validate([
            'shop_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('seller-logos', 'public');
        }

        $seller->update($validated);

        return response()->json($seller);
    }
}
