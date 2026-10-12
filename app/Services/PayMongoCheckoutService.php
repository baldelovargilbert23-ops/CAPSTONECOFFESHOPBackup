<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use MongoSprout\PayMongo\Client\Payload;
use MongoSprout\PayMongo\Facades\PayMongo;
use MongoSprout\PayMongo\Money;

class PayMongoCheckoutService
{
    private const PAYMENT_METHODS = ['gcash', 'card', 'paymaya', 'grab_pay'];

    public function createSession(Order $order): Payload
    {
        $paymentMethods = config('services.paymongo.payment_methods', []);
        if (! is_array($paymentMethods) || $paymentMethods === []) {
            throw new InvalidArgumentException('Configure at least one supported PayMongo payment method.');
        }

        foreach ($paymentMethods as $paymentMethod) {
            if (! is_string($paymentMethod) || ! in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
                throw new InvalidArgumentException('The configured PayMongo payment methods are invalid.');
            }
        }

        return PayMongo::checkoutSessions()->create([
            'line_items' => [[
                'name' => 'Order #' . $order->order_number,
                'description' => 'Coffee Shop Order',
                'amount' => Money::ofMajor((string) $order->total_amount)->toCentavos(),
                'currency' => 'PHP',
                'quantity' => 1,
            ]],
            'payment_method_types' => array_values(array_unique($paymentMethods)),
            'reference_number' => $order->order_number,
            'metadata' => [
                'order_id' => (string) $order->id,
                'user_id' => (string) Auth::id(),
                'branch_id' => (string) $order->branch_id,
                'payment_method' => $order->payment_method,
                'tenant_id' => (string) $order->tenant_id,
            ],
            'success_url' => route('payment.success', $order),
            'cancel_url' => route('payment.cancel', $order),
        ]);
    }
}
