<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CuriarShippingController extends Controller
{
    protected $apiKey;
    protected $baseUrl = 'https://api.curiar.com/v1';

    public function __construct()
    {
        $this->apiKey = '';
    }

    /**
     * Create a new shipment tracking in Curiar
     */
    public function createShipment(Order $order)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/shipments', [
            'reference' => $order->order_number,
            'customer' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
                'phone' => $order->user->phone,
            ],
            'delivery_address' => [
                'street' => $order->shipping_address,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'postal_code' => $order->shipping_postal_code,
                'country' => $order->shipping_country,
            ],
            'items' => $order->items->map(function($item) {
                return [
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'value' => $item->price,
                ];
            }),
            'service_level' => 'standard',
            'carrier_id' => 'fedex', // Or dynamically select based on order
        ]);

        if ($response->successful()) {
            $trackingData = $response->json();

            // Create or update the shipment record
            $shipment = OrderShipment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'tracking_number' => $trackingData['tracking_number'],
                    'tracking_url' => $trackingData['tracking_url'],
                    'carrier' => $trackingData['carrier'],
                    'status' => $trackingData['status'],
                    'estimated_delivery' => $trackingData['estimated_delivery'],
                    'curiar_shipment_id' => $trackingData['id'],
                ]
            );

            return response()->json($shipment);
        }

        return response()->json(['error' => 'Failed to create shipment'], 500);
    }

    /**
     * Get tracking information for a shipment
     */
    public function getTrackingInfo($trackingNumber)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->baseUrl . '/tracking/' . $trackingNumber);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to retrieve tracking information'], 500);
    }


    /**
     * Update shipment status from webhook
     */
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('X-Curiar-Signature');
        $payload = $request->getContent();

        if (!$this->verifyWebhookSignature($signature, $payload)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->json()->all();

        // Handle different event types
        switch ($data['event']) {
            case 'shipment.status_updated':
                $shipment = OrderShipment::where('curiar_shipment_id', $data['shipment_id'])->first();

                if ($shipment) {
                    $shipment->status = $data['status'];
                    $shipment->last_update = now();
                    $shipment->tracking_details = json_encode($data['tracking_details']);
                    $shipment->save();
                }
                break;

            case 'shipment.delivered':
                $shipment = OrderShipment::where('curiar_shipment_id', $data['shipment_id'])->first();

                if ($shipment) {
                    $shipment->status = 'delivered';
                    $shipment->delivered_at = $data['delivered_at'];
                    $shipment->save();

                    // Update the order status
                    $shipment->order->update(['status' => 'delivered']);
                }
                break;
        }

        return response()->json(['success' => true]);
    }


    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature($signature, $payload)
    {
        $webhookSecret = '';
        $computedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($computedSignature, $signature);
    }
}
