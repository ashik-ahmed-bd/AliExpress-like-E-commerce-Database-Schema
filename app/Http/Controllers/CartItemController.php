<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function store(Request $request, Cart $cart)
    {
        $this->authorize('update', $cart);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $cartItem = $cart->cartItems()->create($validated);

        return response()->json($cartItem, 201);
    }
}
