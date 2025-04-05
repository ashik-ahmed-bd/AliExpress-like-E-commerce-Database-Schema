<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderShipmentController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'tracking_number' => 'nullable|string|max:100',
            'carrier' => 'required|string',
            'status' => 'required|string',
        ]);

        $orderShipment = $order->shipments()->create($validated);

        return response()->json($orderShipment, 201);
    }
}
