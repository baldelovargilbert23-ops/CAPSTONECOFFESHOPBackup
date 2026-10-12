<?php

namespace App\Http\Livewire\Cashier;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.cashier')] class extends Component
{
    public $products = [];
    public $currentOrderItems = [];
    public $currentOrderTotal = 0;
    
    public $cash_received = 0;
    public $change = 0;
    public $payment_method = 'cash';
    public $search = '';
    public $activeTab = 'pos'; 

    public function mount()
    {
        $tenantId = Auth::user()->tenant_id ?? session('tenant_id');
        if (!$tenantId) return redirect()->route('login');
        $this->loadProducts($tenantId);
    }

    public function loadProducts($tenantId)
    {
        $this->products = Product::where('tenant_id', $tenantId)
            ->with('category')
            ->where('is_active', true)
            ->get();
    }

    public function updatedCashReceived()
    {
        $this->change = max(0, (float)$this->cash_received - (float)$this->currentOrderTotal);
    }

    public function addToOrder($productId)
    {
        $product = Product::findOrFail($productId);
        if ($product->stock <= 0) {
            $this->dispatch('notify', ['type' => 'error', 'message' => "Out of stock!"]);
            return;
        }

        $product->decrement('stock');

        if (isset($this->currentOrderItems[$productId])) {
            $this->currentOrderItems[$productId]['quantity']++;
            $this->currentOrderItems[$productId]['total'] = $this->currentOrderItems[$productId]['quantity'] * $product->price;
        } else {
            $this->currentOrderItems[$productId] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $product->price,
                'quantity'   => 1,
                'total'      => $product->price,
            ];
        }
        $this->calculateTotal();
        $this->updatedCashReceived();
    }

    public function checkoutOrder()
    {
        if (empty($this->currentOrderItems)) return;

        if ($this->payment_method === 'cash' && (float)$this->cash_received < (float)$this->currentOrderTotal) {
            session()->flash('error', 'Insufficient cash provided.');
            return;
        }

        $tenantId = auth()->user()->tenant_id ?? session('tenant_id');

        try {
            $order = DB::transaction(function () use ($tenantId) {
                $lastOrder = Order::where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->orderByRaw('CAST(order_number AS UNSIGNED) DESC')
                    ->first();

                $nextNumber = $lastOrder ? ((int)$lastOrder->order_number + 1) : 1;
                $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $newOrder = Order::create([
                    'tenant_id'      => $tenantId,
                    'user_id'        => auth()->id(),
                    'order_number'   => $formattedNumber, 
                    'total_amount'   => $this->currentOrderTotal,
                    'payment_method' => $this->payment_method,
                    'status'         => 'preparing', 
                ]);

                foreach ($this->currentOrderItems as $item) {
                    OrderItem::create([
                        'order_id'   => $newOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'],
                        'subtotal'   => $item['total'],
                    ]);
                }

                return $newOrder;
            });

            $finalChange = $this->change;
            $this->resetOrder();
            $this->loadProducts($tenantId);

            session()->flash('success', "Order #{$order->order_number} confirmed. Change: ₱" . number_format($finalChange, 2));

        } catch (\Exception $e) {
            \Log::error("Checkout Failed: " . $e->getMessage());
            session()->flash('error', 'Critical Error: ' . $e->getMessage());
        }
    }

    public function cancelOrder()
    {
        $tenantId = auth()->user()->tenant_id ?? session('tenant_id');
        foreach ($this->currentOrderItems as $item) {
            Product::find($item['product_id'])->increment('stock', $item['quantity']);
        }
        $this->resetOrder();
        $this->loadProducts($tenantId);
        session()->flash('info', 'Journal cleared.');
    }

    private function calculateTotal()
    {
        $this->currentOrderTotal = collect($this->currentOrderItems)->sum('total');
    }

    private function resetOrder()
    {
        $this->currentOrderItems = [];
        $this->currentOrderTotal = 0;
        $this->cash_received = 0;
        $this->change = 0;
    }

    public function render()
    {
        // ... (Your existing render logic for activeOrders and history)
        return view('livewire.cashier.cashier-component');
    }
};