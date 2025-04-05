<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $orderItem = $order->orderItems()->create($validated);

        return response()->json($orderItem, 201);
    }
}
