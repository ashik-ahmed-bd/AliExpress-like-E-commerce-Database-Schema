<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return Order::with('user')->get();
    }

    public function show(Order $order)
    {
        return $order->load(['user', 'orderItems', 'shipments']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_number' => 'required|string|unique:orders,order_number',
            'status' => 'required|string',
            'total_amount' => 'required|numeric',
            'shipping_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'payment_method' => 'required|string',
            'payment_status' => 'required|string',
            'shipping_address' => 'required|string',
        ]);

        $order = Order::create($validated);

        return response()->json($order, 201);
    }
}
