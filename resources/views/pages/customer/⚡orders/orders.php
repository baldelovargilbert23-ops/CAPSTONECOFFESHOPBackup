<?php

namespace App\Livewire\Customer;

use App\Models\Order;
use App\Models\Review;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Tenant;
use App\Support\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component
{
    public $showReviewModal = false;
    public $rating = 5;
    public $comment = '';
    public $selectedOrderId = null;
    public $selectedProductId = null;
    public $orderItems = [];

    #[Url(history: true, as: 'tab')]
    public $activeTab = 'active';

    public $showReceiptModal = false;
    public $receiptOrder = null;

    // ✅ Search
    public $search = '';

    protected $statusConfig = [
        'pending' => [
            'color' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
            'icon' => 'fa-clock',
            'label' => 'Pending',
            'note' => 'Waiting for shop',
            'progress' => 25,
            'eta' => '~10 min',
        ],
        'preparing' => [
            'color' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30',
            'icon' => 'fa-fire',
            'label' => 'Preparing',
            'note' => 'Barista is brewing',
            'progress' => 50,
            'eta' => '~5 min',
        ],
        'ready' => [
            'color' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/30',
            'icon' => 'fa-check-circle',
            'label' => 'Ready',
            'note' => 'Come pick up',
            'progress' => 75,
            'eta' => 'Now',
        ],
        'completed' => [
            'color' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
            'icon' => 'fa-check-double',
            'label' => 'Completed',
            'note' => 'Completed',
            'progress' => 100,
            'eta' => 'Picked up',
        ],
        'cancelled' => [
            'color' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/30',
            'icon' => 'fa-ban',
            'label' => 'Cancelled',
            'note' => 'Cancelled',
            'progress' => 0,
            'eta' => '—',
        ],
    ];

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
    }

    public function openReceiptModal($orderId)
    {
        $this->receiptOrder = Order::with(['items.product', 'branch', 'tenant', 'user'])
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$this->receiptOrder) {
            session()->flash('error', 'Order not found.');
            return;
        }

        $this->showReceiptModal = true;
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->receiptOrder = null;
    }

    public function reorder($orderId)
    {
        $order = Order::with('items.product')
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        $branchId = $order->branch_id === null ? null : (int) $order->branch_id;
        if ($branchId !== null) {
            $branch = Branch::where('tenant_id', $order->tenant_id)
                ->where('id', $branchId)
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->first();
            if (! $branch) {
                session()->flash('error', 'This branch is no longer accepting orders.');
                return;
            }
        } elseif (! Tenant::whereKey($order->tenant_id)->where('status', 'active')->exists()) {
            session()->flash('error', 'This shop is no longer accepting orders.');
            return;
        }

        $productIds = $order->items->pluck('product_id')->filter()->unique();
        $products = Product::forLocation($order->tenant_id, $branchId)
            ->where('type', 'finished')
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->with('recipe.ingredient')
            ->get()
            ->keyBy('id');
        $available = StockService::availableQuantities($products->values(), $branchId, (int) $order->tenant_id);

        $cart = [];
        $skipped = [];

        foreach ($order->items as $item) {
            if (!$item->product_id) {
                $skipped[] = $item->item_name ?? 'Unknown';
                continue;
            }

            $product = $products->get($item->product_id);
            if (!$product) {
                $skipped[] = $item->product->name ?? 'Discontinued';
                continue;
            }

            $availableStock = $available[$item->product_id] ?? 0;
            if ($availableStock <= 0) {
                $skipped[] = $product->name . ' (out of stock)';
                continue;
            }

            $qty = min($item->quantity, $availableStock);

            $cart[$item->product_id] = [
                'id' => $item->product_id,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $qty,
            ];
        }

        if (empty($cart)) {
            session()->flash('error', 'None of the items are currently available.');
            return;
        }

        session()->put('cart_' . ($branchId ?? 0), $cart);

        if (!empty($skipped)) {
            session()->flash('success', count($cart) . ' item(s) added. Skipped: ' . implode(', ', $skipped));
        } else {
            session()->flash('success', 'Items added to cart!');
        }

        return redirect()->route('customer.shopmenu', [
            'tenant' => $order->tenant_id,
            'branch' => $branchId ?? 0,
        ]);
    }

    // ✅ NEW: Modify a pending order — cancels it, restores stock, loads into cart
    public function modifyOrder($orderId)
    {
        $order = Order::with('items.product')
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->where('payment_status', 'pending')
            ->whereNull('payment_session_id')
            ->first();

        if (!$order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        if ($order->status !== 'pending') {
            session()->flash('error', 'Cannot modify — order is already ' . $order->status . '.');
            return;
        }

        $branchId = $order->branch_id === null ? null : (int) $order->branch_id;
        if ($branchId !== null) {
            $branch = Branch::where('tenant_id', $order->tenant_id)
                ->where('id', $branchId)
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->first();
            if (! $branch) {
                session()->flash('error', 'This branch is no longer accepting orders.');
                return;
            }
        } elseif (! Tenant::whereKey($order->tenant_id)->where('status', 'active')->exists()) {
            session()->flash('error', 'This shop is no longer accepting orders.');
            return;
        }

        try {
            DB::transaction(function () use ($order, $branchId) {
                $lockedOrder = Order::whereKey($order->id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== 'pending'
                    || $lockedOrder->payment_status !== 'pending'
                    || $lockedOrder->payment_session_id) {
                    throw new \RuntimeException('Cannot modify — the order has already been processed.');
                }

                $lockedOrder->loadMissing('items.product');
                $lockedOrder->update(['status' => 'cancelled']);
                StockService::restoreOrder($lockedOrder, Auth::id());
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to modify: ' . $e->getMessage());
            return;
        }

        // Load items into cart
        $productIds = $order->items->pluck('product_id')->filter()->unique();
        $products = Product::forLocation($order->tenant_id, $branchId)
            ->where('type', 'finished')
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->with('recipe.ingredient')
            ->get()
            ->keyBy('id');
        $available = StockService::availableQuantities($products->values(), $branchId, (int) $order->tenant_id);

        $cart = [];

        foreach ($order->items as $item) {
            if (!$item->product_id) continue;

            $product = $products->get($item->product_id);
            if (!$product) continue;

            $availableStock = $available[$item->product_id] ?? 0;
            $qty = min((int) $item->quantity, (int) $availableStock);
            if ($qty <= 0) continue;

            $cart[$item->product_id] = [
                'id' => $item->product_id,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $qty,
            ];
        }

        session()->put('cart_' . ($branchId ?? 0), $cart);
        session()->flash('success', 'Order cancelled — you can now modify your items.');

        return redirect()->route('customer.shopmenu', [
            'tenant' => $order->tenant_id,
            'branch' => $branchId ?? 0,
        ]);
    }

    public function openReviewModal($orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);

        $this->selectedOrderId = $orderId;
        $this->orderItems = $order->items;
        $this->selectedProductId = $this->orderItems->first()?->product_id;
        $this->showReviewModal = true;
        $this->reset(['rating', 'comment']);
    }

    public function closeReviewModal()
    {
        $this->showReviewModal = false;
        $this->reset(['selectedOrderId', 'selectedProductId', 'orderItems', 'rating', 'comment']);
    }

    public function submitReview()
{
    $this->validate([
        'selectedProductId' => 'required|exists:products,id',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:500',
    ]);

    $order = Order::where('user_id', Auth::id())->findOrFail($this->selectedOrderId);

    $branchId = $order->branch_id;
    if (!$branchId) {
        $product = Product::forLocation((int) $order->tenant_id, null)->find($this->selectedProductId);
        $branchId = $product->branch_id ?? null;
    } else {
        $product = Product::forLocation((int) $order->tenant_id, (int) $branchId)->find($this->selectedProductId);
    }

    if (! $product || ! $order->items()->where('product_id', $product->id)->exists()) {
        session()->flash('error', 'You can only review products from this order.');
        return;
    }

    // ✅ Enforce "one review per user per product" via updateOrCreate
    //    If the user already reviewed this product, update their existing review.
    //    Otherwise, create a new one.
    $existing = Review::where('user_id', Auth::id())
        ->where('product_id', $this->selectedProductId)
        ->first();

    if ($existing) {
        $existing->update([
            'rating'     => $this->rating,
            'comment'    => $this->comment,
            'is_visible' => true,
        ]);

        $message = 'Your review has been updated.';
    } else {
        Review::create([
            'tenant_id'  => $order->tenant_id,
            'branch_id'  => $branchId,
            'product_id' => $this->selectedProductId,
            'user_id'    => Auth::id(),
            'rating'     => $this->rating,
            'comment'    => $this->comment,
            'is_visible' => true,
        ]);

        $message = 'Thank you! Your review has been posted.';
    }

    $this->closeReviewModal();
    session()->flash('success', $message);
}

    public function cancelOrder($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->where('payment_status', 'pending')
            ->whereNull('payment_session_id')
            ->first();

        if (!$order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::whereKey($order->id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== 'pending'
                    || $lockedOrder->payment_status !== 'pending'
                    || $lockedOrder->payment_session_id) {
                    throw new \RuntimeException('Cannot cancel — order is already ' . $lockedOrder->status . '.');
                }

                $lockedOrder->loadMissing('items.product');
                $lockedOrder->update(['status' => 'cancelled']);
                StockService::restoreOrder($lockedOrder, Auth::id());
            });
            session()->flash('success', 'Order #' . $order->order_number . ' cancelled.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to cancel: ' . $e->getMessage());
        }
    }

    public function getStatusConfig($status)
    {
        return $this->statusConfig[$status] ?? $this->statusConfig['pending'];
    }

    public function isActiveOrder($status)
    {
        return in_array($status, ['pending', 'preparing', 'ready']);
    }

    public function render()
    {
        $query = Order::where('user_id', Auth::id())
            ->with(['tenant', 'branch', 'items.product'])
            ->latest();

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                  ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', $term))
                  ->orWhereHas('branch', fn($b) => $b->where('name', 'like', $term));
            });
        }

        $allOrders = (clone $query)->get();

        $orders = match ($this->activeTab) {
            'active' => $allOrders->filter(fn($o) => in_array($o->status, ['pending', 'preparing', 'ready'])),
            'completed' => $allOrders->where('status', 'completed'),
            'cancelled' => $allOrders->where('status', 'cancelled'),
            default => $allOrders,
        };

        $tabCounts = [
            'active' => $allOrders->filter(fn($o) => in_array($o->status, ['pending', 'preparing', 'ready']))->count(),
            'completed' => $allOrders->where('status', 'completed')->count(),
            'cancelled' => $allOrders->where('status', 'cancelled')->count(),
            'all' => $allOrders->count(),
        ];

        return view('pages.customer.⚡orders.orders', [
            'orders' => $orders,
            'tabCounts' => $tabCounts,
        ]);
    }
};
