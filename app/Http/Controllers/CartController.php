<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'session_id' => 'nullable|string',
        ]);

        $cart = Cart::create($validated);

        return response()->json($cart, 201);
    }
}
