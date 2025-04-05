<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SteadFastShippingController extends Controller
{
    protected $apiKey;
    protected $secretKey;
    protected $baseUrl = 'https://portal.packzy.com/api/v1';

    public function __construct()
    {
        $this->apiKey = 'thwhtbydpw0nl6xncn1zixd6nmh1gv1i ';
        $this->secretKey = '9mnlhxkkbo6c7o799hxyitnl';
    }


    /**
     * Create a parcel request in SteadFast
     */
    public function createParcel(Order $order)
    {
        // Generate unique parcel invoice ID
        $parcelInvoiceID = 'INV-' . $order->id . '-' . time();

        $response = Http::withHeaders([
            'Api-Key' => $this->apiKey,
            'Secret-Key' => $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/parcel-requests', [
            'invoice' => $parcelInvoiceID,
            'recipient_name' => $order->user->name,
            'recipient_phone' => $order->user->phone,
            'recipient_address' => $order->shipping_address,
            'recipient_city' => $order->shipping_city,
            'recipient_zone' => $order->shipping_zone ?? '',
            'recipient_area' => $order->shipping_area ?? '',
            'parcel_weight' => $this->calculateWeight($order),
            'payment_method' => $this->mapPaymentMethod($order->payment_method),
            'requested_delivery_time' => 'any',
            'parcel_type' => 'regular',
            'item_description' => $this->getItemDescription($order),
            'special_instructions' => $order->notes ?? '',
            'amount_to_collect' => $order->payment_status === 'pending' ? $order->total_amount : 0,
            'amount_to_pay' => 0, // Already paid to merchant
            'no_of_items' => $order->orderItems->sum('quantity'),
        ]);

        if ($response->successful()) {
            $trackingData = $response->json();

            // Create or update the shipment record
            $shipment = OrderShipment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'tracking_number' => $trackingData['data']['tracking_code'],
                    'carrier' => 'SteadFast',
                    'status' => 'processing',
                    'notes' => 'Created via SteadFast API',
                    'steadfast_parcel_id' => $trackingData['data']['parcel_id'],
                    'steadfast_invoice_id' => $parcelInvoiceID,
                ]
            );

            return response()->json($shipment);
        }

        return response()->json([
            'error' => 'Failed to create parcel request',
            'details' => $response->json()
        ], 500);
    }


    /**
     * Get tracking information for a parcel
     */
    public function getTrackingInfo($trackingNumber)
    {
        $response = Http::withHeaders([
            'Api-Key' => $this->apiKey,
            'Secret-Key' => $this->secretKey,
        ])->get($this->baseUrl . '/parcel-tracking/' . $trackingNumber);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to retrieve tracking information'], 500);
    }


    /**
     * Cancel a parcel request
     */
    public function cancelParcel(OrderShipment $shipment)
    {
        if (!$shipment->steadfast_parcel_id) {
            return response()->json(['error' => 'No SteadFast parcel ID found'], 400);
        }

        $response = Http::withHeaders([
            'Api-Key' => $this->apiKey,
            'Secret-Key' => $this->secretKey,
        ])->post($this->baseUrl . '/cancel-parcel', [
            'parcel_id' => $shipment->steadfast_parcel_id,
            'reason' => 'Order cancelled by customer'
        ]);

        if ($response->successful()) {
            $shipment->status = 'cancelled';
            $shipment->notes = 'Cancelled via SteadFast API at ' . now();
            $shipment->save();

            return response()->json(['message' => 'Parcel cancelled successfully']);
        }

        return response()->json(['error' => 'Failed to cancel parcel'], 500);
    }


    /**
     * Handle SteadFast webhook notifications
     */
    public function handleWebhook(Request $request)
    {
        // Validate the webhook origin
        if (!$this->validateWebhook($request)) {
            return response()->json(['error' => 'Unauthorized webhook request'], 401);
        }

        $data = $request->json()->all();

        // Find shipment by SteadFast tracking code
        $shipment = OrderShipment::where('tracking_number', $data['tracking_code'])->first();

        if (!$shipment) {
            return response()->json(['error' => 'Shipment not found'], 404);
        }

        switch ($data['status']) {
            case 'picked':
                $shipment->status = 'picked';
                $shipment->shipped_at = now();
                break;

            case 'in_progress':
                $shipment->status = 'in_transit';
                break;

            case 'delivered':
                $shipment->status = 'delivered';
                $shipment->delivered_at = now();

                // Update the order status
                $shipment->order->update(['status' => 'delivered']);
                break;

            case 'returned':
                $shipment->status = 'returned';
                break;

            default:
                $shipment->status = strtolower($data['status']);
        }

        $shipment->save();

        return response()->json(['success' => true]);
    }


    /**
     * Calculate package weight based on order items
     */
    private function calculateWeight(Order $order)
    {
        // Default weight in kg if not specified in products
        $totalWeight = 0;

        foreach ($order->orderItems as $item) {
            // Add 0.5kg per item or use product weight if available
            $itemWeight = $item->product->weight ?? 0.5;
            $totalWeight += ($itemWeight * $item->quantity);
        }

        return max(0.5, $totalWeight); // Minimum 0.5kg
    }

    /**
     * Map payment method to SteadFast supported values
     */
    private function mapPaymentMethod($paymentMethod)
    {
        switch (strtolower($paymentMethod)) {
            case 'cod':
            case 'cash on delivery':
                return 'COD';
            default:
                return 'Digital Payment';
        }
    }


    /**
     * Generate item description for the parcel
     */
    private function getItemDescription(Order $order)
    {
        $items = $order->orderItems->map(function($item) {
            return $item->quantity . 'x ' . $item->product->name;
        })->take(3)->join(', ');

        if ($order->orderItems->count() > 3) {
            $items .= ' and ' . ($order->orderItems->count() - 3) . ' more items';
        }

        return $items;
    }


    /**
     * Validate incoming webhook from SteadFast
     */
    private function validateWebhook(Request $request)
    {
        $webhookToken = $request->header('X-SteadFast-Token');
        $expectedToken = '';

        return hash_equals($expectedToken, $webhookToken);
    }

}
