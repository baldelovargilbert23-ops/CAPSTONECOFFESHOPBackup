<?php

namespace App\Listeners;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use MongoSprout\PayMongo\Webhooks\Events\CheckoutSessionPaid;

class MarkOrderPaidFromPayMongo
{
    /**
     * Handle the PayMongo "checkout session paid" event.
     * Auto-discovered by Laravel because of the typed parameter.
     */
    public function handle(CheckoutSessionPaid $event): void
    {
        // The package exposes the raw webhook payload on the event.
        // We handle multiple possible shapes so this is robust:
        $payload = $event->payload
            ?? $event->data
            ?? $event->webhook
            ?? null;

        // If nothing matched, fall back to public properties of the event.
        if (! $payload) {
            $payload = (array) $event;
        }

        $eventType = data_get($payload, 'data.attributes.type')
            ?? data_get($payload, 'data.type')   // ✅ ADDED
            ?? data_get($payload, 'type');

        if (! in_array($eventType, ['payment.paid', 'checkout_session.payment.paid'], true)) {
            Log::info('[PayMongo listener] Ignored non-paid event', ['type' => $eventType]);
            return;
        }

        // 1) Preferred lookup: metadata.order_id (always present in your payloads)
        $orderId = data_get($payload, 'data.attributes.data.attributes.metadata.order_id');

        // 2) Fallback: checkout session ID (cs_...)
        $sessionId = data_get($payload, 'data.attributes.data.id');

        $order = null;

        if ($orderId) {
            $order = Order::withoutGlobalScopes()->find((int) $orderId);
        }

        if (! $order && $sessionId && str_starts_with($sessionId, 'cs_')) {
            $order = Order::withoutGlobalScopes()
                ->where('payment_session_id', $sessionId)
                ->first();
        }

        if (! $order) {
            Log::warning('[PayMongo listener] Paid event for unknown order', [
                'order_id'   => $orderId,
                'session_id' => $sessionId,
            ]);
            return;
        }

        if ($order->payment_status === 'paid') {
            return; // idempotent — safe if webhook fires multiple times
        }

        $order->update([
            'payment_status' => 'paid',
            'notes' => ($order->notes ? $order->notes . "\n" : '')
                     . 'Paid via PayMongo at ' . now()->format('M d, Y h:i A'),
        ]);

        Log::info('[PayMongo] Order marked paid', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'event_type'   => $eventType,
        ]);
    }
}