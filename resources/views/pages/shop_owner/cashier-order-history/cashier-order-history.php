<?php

namespace App\Http\Livewire\Cashier;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.cashier')] class extends Component
{
    public $search = '';

    // Listen for events if other components (like the POS) create orders
    protected $listeners = ['orderCreated' => '$refresh'];

    /**
     * Update the status of an order (e.g., from Pending to Preparing)
     */
    public function updateOrderStatus($orderId, $status)
    {
        $tenantId = Auth::user()->tenant_id;
        $order = Order::where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($order) {
            // If cancelled, restore the stock
            if ($status === 'cancelled') {
                foreach ($order->items as $item) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => $status]);
            session()->flash('success', "Order #{$order->order_number} moved to {$status}.");
        }
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        // Fetch only active orders for the fulfillment board
        $activeOrdersQuery = Order::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->with(['items.product'])
            ->latest();

        // Apply search filter for Order Number or Product Name
        if ($this->search) {
            $activeOrdersQuery->where(function($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('items.product', function($pq) {
                      $pq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }
return view('pages.shop_owner.⚡cashier-order-history.cashier-order-history', [
            'activeOrders' => $activeOrdersQuery->get()
        ]);
    }
};