<?php

namespace App\Listeners;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MongoSprout\PayMongo\Webhooks\Events\CheckoutSessionPaid;

class HandleSuccessfulPayment
{
    public function handle(CheckoutSessionPaid $event): void
    {
        $orderId = filter_var($event->metadata('order_id'), FILTER_VALIDATE_INT);
        $tenantId = filter_var($event->metadata('tenant_id'), FILTER_VALIDATE_INT);

        if (! $orderId || ! $tenantId) {
            Log::warning('PayMongo checkout event is missing order metadata', [
                'checkout_session_id' => $event->resource()->id(),
            ]);

            return;
        }

        DB::transaction(function () use ($event, $orderId, $tenantId): void {
            $order = Order::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                Log::warning('PayMongo checkout event references an unknown order', [
                    'order_id' => $orderId,
                    'tenant_id' => $tenantId,
                ]);

                return;
            }

            $sessionId = $event->resource()->id();
            $storedSessionId = (string) $order->payment_session_id;
            if (! $sessionId || ($storedSessionId !== '' && ! hash_equals($storedSessionId, $sessionId))) {
                Log::warning('PayMongo checkout event does not match the order session', [
                    'order_id' => $orderId,
                    'checkout_session_id' => $sessionId,
                ]);

                return;
            }

            $paidCentavos = $event->resource()->centavos();
            $expectedCentavos = (int) round((float) $order->total_amount * 100);
            $currency = strtoupper((string) $event->resource()->attr('currency', 'PHP'));
            if (($paidCentavos !== null && $paidCentavos !== $expectedCentavos) || $currency !== 'PHP') {
                Log::warning('PayMongo checkout amount or currency does not match the order', [
                    'order_id' => $orderId,
                    'expected_centavos' => $expectedCentavos,
                    'paid_centavos' => $paidCentavos,
                    'currency' => $currency,
                ]);

                return;
            }

            if ($order->payment_status === 'paid') {
                return;
            }

            if ($order->status !== 'pending'
                || $order->payment_status !== 'pending'
                || ! in_array($order->payment_method, ['paymongo', 'gcash', 'card', 'paymaya', 'grab_pay'], true)) {
                Log::warning('PayMongo checkout paid event arrived for an order that cannot be paid', [
                    'order_id' => $orderId,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                ]);

                return;
            }

            $order->update([
                // Recover orders created before payment_session_id existed:
                // only a signed event with matching order metadata and amount
                // can populate this missing ID and confirm the payment.
                'payment_session_id' => $storedSessionId !== '' ? $storedSessionId : $sessionId,
                'payment_status' => 'paid',
                'notes' => ($order->notes ? $order->notes . "\n" : '')
                    . 'Online payment confirmed by PayMongo at ' . now()->format('M d, Y h:i A'),
            ]);

            if (! $order->payment()->exists()) {
                Payment::create([
                    'order_id' => $order->id,
                    'branch_id' => $order->branch_id,
                    'amount' => $order->total_amount,
                    'method' => $order->payment_method,
                    'reference_no' => $sessionId,
                    'paid_at' => now(),
                ]);
            }
        });
    }
}
