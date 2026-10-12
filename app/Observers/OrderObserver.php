<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\LoyaltyService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && $order->status === 'completed') {
            app(LoyaltyService::class)->awardForOrder($order);
        }
    }
}