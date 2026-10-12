<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PayMongoCheckoutService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create PayMongo checkout session and redirect customer
     */
    public function checkout($orderId, PayMongoCheckoutService $checkoutService)
    {
        $order = Order::withoutGlobalScopes()
            ->with('branch')
            ->where('user_id', Auth::id())
            ->findOrFail($orderId);
        
        // Ensure order belongs to the current user
        if ($order->payment_status === 'paid') {
            return redirect()->route('payment.success', $order);
        }

        if ($order->status !== 'pending' || $order->payment_status !== 'pending' || $order->payment_method === 'cash') {
            return redirect()->route('customer.orders')->with('error', 'This order is no longer available for online payment.');
        }

        try {
            $session = $checkoutService->createSession($order);

            // The PayMongo payload ID lives at data.id, not data.attributes.id.
            $sessionId = $session->id();
            $checkoutUrl = $session->attr('checkout_url');

            if (! $sessionId || ! is_string($checkoutUrl) || ! str_starts_with($checkoutUrl, 'https://')) {
                throw new \UnexpectedValueException('PayMongo did not return a valid checkout session.');
            }

            // Store the checkout session ID before sending the customer to PayMongo.
            $order->update([
                'payment_session_id' => $sessionId,
            ]);

            return redirect()->away($checkoutUrl);

        } catch (\Throwable $e) {
            Log::error('PayMongo checkout initialization failed.', [
                'order_id' => $order->id,
                'exception' => $e,
            ]);

            return redirect()->route('customer.orders')->with(
                'error',
                'We could not start your online checkout. Your order is still pending; you can try again from Your Orders.'
            );
        }
    }

    /**
     * ✅ Handle successful payment (redirect from PayMongo)
     */
    public function success(Order $order)
    {
        abort_unless((int) $order->user_id === (int) Auth::id(), 403, 'Unauthorized.');

        $order->refresh();
        if ($order->status === 'cancelled') {
            return redirect()->route('payment.cancel', $order);
        }

        // The browser return URL is not proof of payment. The signed provider
        // webhook marks an order paid; keep unpaid orders out of the success state.
        if ($order->payment_status !== 'paid') {
            return redirect()->route('customer.orders')->with(
                'info',
                'Your payment is awaiting PayMongo confirmation. Check your orders again shortly.'
            );
        }

        return view('pages.payments.⚡success', compact('order'));
    }

    /**
     * A browser return is not authoritative evidence of cancellation.
     */
    public function cancel(Order $order)
    {
        abort_unless((int) $order->user_id === (int) Auth::id(), 403, 'Unauthorized.');

        if ($order->payment_status === 'paid' && $order->status !== 'cancelled') {
            return redirect()->route('payment.success', $order);
        }

        return redirect()->route('customer.orders')->with(
            'info',
            'Checkout closed. Check your order\'s current payment status below.'
        );
    }
}
