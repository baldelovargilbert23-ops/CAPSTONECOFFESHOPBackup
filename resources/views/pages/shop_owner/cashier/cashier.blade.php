<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.cashier')] class extends Component
{
    public $products = [];
    public $cart = [];
    public $cartTotal = 0;
    public $cashReceived = 0;
    public $change = 0;
    public $paymentMethod = 'cash';
    public $selectedEwallet = 'gcash';
    public $showQrModal = false;
    public $qrCodeUrl = '';
    public $activeTab = 'pos';
    public $searchProducts = '';

    public $categoryFilter = 'all';
    public $searchOrders = '';

    public $todaySales = 0;
    public $todayOrders = 0;
    public $todayCashSales = 0;
    public $todayEwalletSales = 0;

    public $pendingCount = 0;
    public $preparingCount = 0;
    public $readyCount = 0;

    #[Locked]
    public ?int $tenantId = null;

    #[Locked]
    public ?int $branchId = null;
    public $branch;
    public string $locationName = '';
    public $categories = [];

    // Stock Request (uses existing orders table - no new migration)
    public $showStockRequestModal = false;
    public $requestProductId = null;
    public $requestProductName = '';
    public $requestQuantity = 1;
    public $requestNote = '';

    public function mount()
    {
        $user = Auth::user();

        $this->tenantId = $user->tenant_id;
        $this->branchId = $user->branch_id;

        if (!$this->tenantId) {
            session()->flash('error', 'No coffee shop is assigned to this account.');
            return redirect()->route('login');
        }

        if ($this->branchId !== null) {
            $this->branch = Branch::withoutGlobalScope('branch')
                ->where('tenant_id', $this->tenantId)
                ->whereKey($this->branchId)
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->first();

            if (! $this->branch) {
                session()->flash('error', 'This branch is inactive or unavailable.');
                return redirect()->route('login');
            }

            $this->locationName = $this->branch->name;
        } else {
            $this->branch = null;
            $this->locationName = Tenant::whereKey($this->tenantId)->value('name') ?? 'Main Store';
        }

        session(['branch_id' => $this->branchId]);
        session(['tenant_id' => $this->tenantId]);

        $this->loadAllData();
    }

    #[On('refresh-orders')]
    public function refreshData()
    {
        $this->loadAllData();
    }

    public function loadAllData()
    {
        $this->loadProducts();
        $this->loadOrderCounts();
        $this->loadTodaySummary();
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = Category::where('tenant_id', $this->tenantId)
            ->withCount(['products' => function ($q) {
                $q->where('type', 'finished')->where('is_active', true);
            }])
            ->get();
    }

    public function loadProducts()
    {
        $this->products = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->with('category')
            ->with('recipe.ingredient')
            ->where('is_active', true)
            ->when($this->searchProducts, function ($query) {
                return $query->where('name', 'like', '%' . $this->searchProducts . '%');
            })
            ->when($this->categoryFilter !== 'all', function ($query) {
                return $query->where('category_id', $this->categoryFilter);
            })
            ->orderBy('name')
            ->get();

        $available = StockService::availableQuantities($this->products, $this->branchId, (int) $this->tenantId);
        foreach ($this->products as $product) {
            $product->setAttribute('stock', $available[$product->id] ?? 0);
        }
    }

    public function setCategoryFilter($catId)
    {
        $this->categoryFilter = $catId;
        $this->loadProducts();
    }

    /**
     * ✅ Filter pending orders so each cashier only sees:
     *   1. Orders explicitly assigned to them via [CASHIER:X] marker in notes
     *   2. Orders that are unassigned (no [CASHIER:] marker at all)
     */
    protected function applyCashierFilter($query)
    {
        $cashierMarker = '[CASHIER:' . Auth::id() . ']';

        return $query->where(function ($q) use ($cashierMarker) {
            $q->where('notes', 'like', '%' . $cashierMarker . '%')
              ->orWhere(function ($qq) {
                  $qq->where(function ($inner) {
                      $inner->whereNull('notes')
                            ->orWhere('notes', 'not like', '%[CASHIER:%');
                  });
              });
        });
    }

    public function loadOrderCounts()
    {
        // ✅ Per-cashier pending count
        $this->pendingCount = $this->applyCashierFilter(
            Order::where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->where('payment_method', 'cash')
                ->where('user_id', '!=', Auth::id())
        )->count();

        $this->preparingCount = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'preparing')
            ->count();

        $this->readyCount = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'ready')
            ->count();
    }

    public function loadTodaySummary()
    {
        $todayOrders = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['pending', 'preparing', 'ready', 'completed'])
            ->get();

        $todayOrders = $todayOrders->where('payment_status', 'paid');
        $this->todayOrders = $todayOrders->count();
        $this->todaySales = $todayOrders->sum('total_amount');
        $this->todayCashSales = $todayOrders->where('payment_method', 'cash')->sum('total_amount');
        $this->todayEwalletSales = $todayOrders
            ->whereIn('payment_method', ['gcash', 'paymongo', 'card', 'paymaya', 'grab_pay'])
            ->sum('total_amount');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedCashReceived()
    {
        $this->change = max(0, (float)$this->cashReceived - (float)$this->cartTotal);
    }

    public function setQuickCash($amount)
    {
        if ($amount === 'exact') {
            $this->cashReceived = $this->cartTotal;
        } else {
            $this->cashReceived = (float) $amount;
        }
        $this->updatedCashReceived();
    }

    public function addToCart($productId)
    {
        $product = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->findOrFail($productId);

        $stock = StockService::availableQuantity($product, $this->branchId, (int) $this->tenantId);

        if ($stock <= 0) {
            session()->flash('error', "{$product->name} is out of stock!");
            return;
        }

        if (isset($this->cart[$productId])) {
            $newQty = $this->cart[$productId]['quantity'] + 1;

            if ($newQty > $stock) {
                session()->flash('error', "Only {$stock} units available for {$product->name}");
                return;
            }

            $this->cart[$productId]['quantity'] = $newQty;
            $this->cart[$productId]['total'] = $newQty * $product->price;
        } else {
            $this->cart[$productId] = [
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

    public function updateQuantity($productId, $newQty)
    {
        $newQty = max(1, min(99, (int)$newQty));
        $product = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->find($productId);

        if (isset($this->cart[$productId])) {
            $stock = $product
                ? StockService::availableQuantity($product, $this->branchId, (int) $this->tenantId)
                : 0;

            if ($product && $newQty > $stock) {
                session()->flash('error', "Only {$stock} units available for {$product->name}");
                return;
            }

            $this->cart[$productId]['quantity'] = $newQty;
            $this->cart[$productId]['total'] = $newQty * $this->cart[$productId]['price'];
            $this->calculateTotal();
            $this->updatedCashReceived();
        }
    }

    public function removeFromCart($productId)
    {
        if (isset($this->cart[$productId])) {
            unset($this->cart[$productId]);
            $this->calculateTotal();
            $this->updatedCashReceived();
            session()->flash('success', 'Item removed.');
        }
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->cartTotal = 0;
        $this->cashReceived = 0;
        $this->change = 0;
        session()->flash('info', 'Cart cleared.');
    }

    // ============================================
    // STOCK REQUEST (uses `orders` table - no new migration)
    // ============================================
    public function openStockRequest($productId)
    {
        $product = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->findOrFail($productId);

        $this->requestProductId = $product->id;
        $this->requestProductName = $product->name;
        $this->requestQuantity = 1;
        $this->requestNote = '';
        $this->showStockRequestModal = true;
    }

    public function closeStockRequest()
    {
        $this->showStockRequestModal = false;
        $this->requestProductId = null;
        $this->requestProductName = '';
        $this->requestQuantity = 1;
        $this->requestNote = '';
    }

    public function submitStockRequest()
    {
        $requestProductRule = Rule::exists('products', 'id')
            ->where('tenant_id', $this->tenantId)
            ->where('type', 'finished')
            ->where('is_active', true);
        $requestProductRule = $this->branchId === null
            ? $requestProductRule->whereNull('branch_id')
            : $requestProductRule->where('branch_id', $this->branchId);

        $this->validate([
            'requestProductId' => [
                'required',
                'integer',
                $requestProductRule,
            ],
            'requestQuantity' => 'required|integer|min:1|max:9999',
            'requestNote' => 'nullable|string|max:500',
        ]);

        $product = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->find($this->requestProductId);

        if (! $product) {
            $this->addError('requestProductId', 'Choose an available product from this location.');
            return;
        }

        $requestQuantity = (int) $this->requestQuantity;
        $requestNote = filled($this->requestNote) ? trim($this->requestNote) : null;

        try {
            $productName = $product->name;

            DB::transaction(function () use ($product, $requestQuantity, $requestNote) {
                $timestamp = now()->format('YmdHis');
                $random = rand(100, 999);
                $orderNumber = "REQ-{$timestamp}-{$random}";

                $request = Order::create([
                    'tenant_id'      => $this->tenantId,
                    'branch_id'      => $this->branchId,
                    'user_id'        => Auth::id(),
                    'order_number'   => $orderNumber,
                    'total_amount'   => 0,
                    'payment_method' => 'cash',
                    'status'         => 'stock_request',
                    'payment_status' => 'pending',
                    'notes'          => "STOCK REQUEST: {$requestQuantity}x {$product->name}"
                                        . ($requestNote ? " | Note: {$requestNote}" : ''),
                ]);

                OrderItem::create([
                    'order_id'   => $request->id,
                    'branch_id'  => $this->branchId,
                    'product_id' => $product->id,
                    'quantity'   => $requestQuantity,
                    'price'      => 0,
                    'subtotal'   => 0,
                ]);
            });

            $this->closeStockRequest();
            $this->dispatch('refresh-orders');
            session()->flash('success', "✅ Stock request for {$productName} sent to Branch Manager!");
        } catch (\Exception $e) {
            \Log::error("Stock Request Failed: " . $e->getMessage());
            session()->flash('error', 'Failed to send the stock request. Please try again.');
        }
    }

    // ============================================
    // CHECKOUT
    // ============================================
    public function checkoutOrder()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty.');
            return;
        }

        $this->validate([
            'cart' => 'required|array|min:1|max:50',
            'cart.*' => 'required|array:product_id,name,price,quantity,total',
            'cart.*.quantity' => 'required|integer|min:1|max:99',
            'cashReceived' => 'required|numeric|min:0|max:1000000',
            'paymentMethod' => 'required|in:cash,gcash,paymaya,grabpay,card,paymongo',
        ]);

        try {
            $order = $this->createPaidOrder($this->paymentMethod);

            $changeAmount = max(0, (float) $this->cashReceived - (float) $order->total_amount);

            $this->clearCart();
            $this->loadAllData();

            $this->dispatch('refresh-orders');
            $this->dispatch('new-order-placed');

            session()->flash('success', "✅ Order #{$order->order_number} sent to Barista! Change: ₱" . number_format($changeAmount, 2));

        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Checkout could not be completed. Please try again.');
        }
    }

    private function createPaidOrder(string $paymentMethod): Order
    {
        if (! in_array($paymentMethod, ['cash', 'gcash', 'paymaya', 'grabpay', 'card', 'paymongo'], true)) {
            throw new \RuntimeException('Select a valid payment method.');
        }

        return DB::transaction(function () use ($paymentMethod) {
            $cart = $this->cart;
            ksort($cart);
            $productIds = array_map('intval', array_keys($cart));

            $products = Product::forLocation($this->tenantId, $this->branchId)
                ->where('type', 'finished')
                ->where('is_active', true)
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw new \RuntimeException('An item in the cart is no longer available.');
            }

            $total = 0.0;
            $stockToDeduct = [];
            foreach ($cart as $productId => $item) {
                $productId = (int) $productId;
                $product = $products->get($productId);
                $quantity = (int) ($item['quantity'] ?? 0);

                if (! $product || $quantity < 1 || $quantity > 99) {
                    throw new \RuntimeException('Check the quantities in your cart and try again.');
                }

                $stockToDeduct[$productId] = $quantity;
                $total += (float) $product->price * $quantity;
            }

            $total = round($total, 2);
            if ($paymentMethod === 'cash' && (float) $this->cashReceived < $total) {
                throw new \RuntimeException('The cash received is less than the order total.');
            }

            $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
            $order = Order::create([
                'tenant_id'      => $this->tenantId,
                'branch_id'      => $this->branchId,
                // Walk-in POS sales must never award loyalty points to the cashier.
                'user_id'        => null,
                'order_number'   => $orderNumber,
                'total_amount'   => $total,
                'payment_method' => $paymentMethod,
                'status'         => 'pending',
                'payment_status' => 'paid',
            ]);

            foreach ($stockToDeduct as $productId => $quantity) {
                $product = $products->get($productId);
                OrderItem::create([
                    'order_id'       => $order->id,
                    'branch_id'      => $this->branchId,
                    'product_id'     => $productId,
                    'quantity'       => $quantity,
                    'price'          => $product->price,
                    'subtotal'       => round((float) $product->price * $quantity, 2),
                    'is_reward_item' => false,
                ]);
            }

            StockService::deductItems(
                $stockToDeduct,
                $this->branchId,
                (int) $this->tenantId,
                $order->order_number,
                Auth::id()
            );

            Payment::create([
                'order_id'     => $order->id,
                'branch_id'    => $this->branchId,
                'amount'       => $order->total_amount,
                'method'       => $paymentMethod,
                'reference_no' => strtoupper($paymentMethod) . '-' . $order->order_number,
                'paid_at'      => now(),
            ]);

            return $order;
        });
    }

    public function confirmPayment($orderId)
    {
        try {
            DB::transaction(function () use ($orderId) {
                $order = Order::where('id', $orderId)
                    ->where('tenant_id', $this->tenantId)
                    ->where('branch_id', $this->branchId)
                    ->where('status', 'pending')
                    ->where('payment_status', 'pending')
                    ->where('payment_method', 'cash')
                    ->where('user_id', '!=', Auth::id())
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('Order already processed by another cashier or not found.');
                }

                $order->update([
                    'payment_status' => 'paid',
                    'notes' => ($order->notes ? $order->notes . "\n" : '') . '✅ Cash payment confirmed by ' . Auth::user()->name . ' at ' . now()->format('M d, Y h:i A'),
                ]);

                if (!$order->payment()->exists()) {
                    Payment::create([
                        'order_id'     => $order->id,
                        'branch_id'    => $order->branch_id,
                        'amount'       => $order->total_amount,
                        'method'       => 'cash',
                        'reference_no' => 'CASH-' . $order->order_number,
                        'paid_at'      => now(),
                    ]);
                }

            });

            $this->dispatch('refresh-orders');
            $this->loadAllData();

            session()->flash('success', "✅ Payment confirmed! Sent to barista queue.");
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Could not confirm this payment. Refresh the order list and try again.');
        }
    }

    public function updatedPaymentMethod($value)
    {
        if ($value === 'ewallet') {
            if (!in_array($this->selectedEwallet, ['gcash', 'paymaya', 'grabpay'])) {
                $this->selectedEwallet = 'gcash';
            }
            $this->qrCodeUrl = $this->generateQrCode($this->selectedEwallet);
        }
    }

    public function updatedSelectedEwallet($value)
    {
        $this->qrCodeUrl = $this->generateQrCode($value);
    }

    public function showEwalletQr($ewallet)
    {
        $this->selectedEwallet = $ewallet;
        $this->qrCodeUrl = $this->generateQrCode($ewallet);
        $this->showQrModal = true;
    }

    public function generateQrCode($ewallet)
    {
        $amount = $this->cartTotal;
        $branchName = $this->locationName ?: 'Coffee Shop';
        $orderNumber = 'WALKIN-' . now()->format('YmdHis');

        switch ($ewallet) {
            case 'gcash':
                return 'https://api.qrserver.com/v1/create-qr-code/?data=GCASH|' . urlencode($branchName) . '|' . $amount . '|' . $orderNumber . '&size=200x200';
            case 'paymaya':
                return 'https://api.qrserver.com/v1/create-qr-code/?data=MAYA|' . urlencode($branchName) . '|' . $amount . '|' . $orderNumber . '&size=200x200';
            case 'grabpay':
                return 'https://api.qrserver.com/v1/create-qr-code/?data=GRABPAY|' . urlencode($branchName) . '|' . $amount . '|' . $orderNumber . '&size=200x200';
            default:
                return '';
        }
    }

    public function confirmEwalletPayment()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty.');
            return;
        }

        try {
            $order = $this->createPaidOrder($this->selectedEwallet);

            $this->clearCart();
            $this->loadAllData();
            $this->dispatch('refresh-orders');
            $this->showQrModal = false;

            session()->flash('success', "✅ Order #{$order->order_number} placed via " . strtoupper($this->selectedEwallet) . "! Sent to barista queue.");

        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'E-wallet checkout could not be completed. Please try again.');
        }
    }

    public function cancelOrder($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where('payment_method', 'cash')
            ->where('user_id', '!=', Auth::id())
            ->first();

        if (!$order) {
            session()->flash('error', 'Order not found or cannot be cancelled.');
            return;
        }

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::whereKey($order->id)
                    ->where('tenant_id', $this->tenantId)
                    ->where('branch_id', $this->branchId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== 'pending' || $lockedOrder->payment_status !== 'pending') {
                    throw new \RuntimeException('This order has already been processed.');
                }

                $lockedOrder->loadMissing('items.product');
                $lockedOrder->update(['status' => 'cancelled']);
                StockService::restoreOrder($lockedOrder, Auth::id());
            });
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Could not cancel this order. Refresh the order list and try again.');
            return;
        }

        $this->dispatch('refresh-orders');
        $this->loadAllData();

        session()->flash('info', "❌ Order #{$order->order_number} cancelled.");
    }

    private function calculateTotal()
    {
        $this->cartTotal = collect($this->cart)->sum('total');
    }

    public function render()
    {
        // ✅ Pending orders filtered by cashier assignment
        $pendingOrders = $this->applyCashierFilter(
            Order::where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->where('payment_method', 'cash')
                ->where('user_id', '!=', Auth::id())
        )
            ->with(['items.product'])
            ->when($this->searchOrders, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('order_number', 'like', '%' . $this->searchOrders . '%')
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $this->searchOrders . '%'));
                });
            })
            ->latest()
            ->get();

        $baristaPendingOrders = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'pending')
            ->where('payment_status', 'paid')
            ->with(['items.product'])
            ->latest()
            ->get();

        $preparingOrders = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'preparing')
            ->with(['items.product'])
            ->latest()
            ->get();

        $readyOrders = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'ready')
            ->with(['items.product'])
            ->latest()
            ->get();

        $history = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->latest()
            ->take(20)
            ->get();

        $this->loadAllData();

        return view('pages.shop_owner.⚡cashier.cashier', [
            'pendingOrders' => $pendingOrders,
            'baristaPendingOrders' => $baristaPendingOrders,
            'preparingOrders' => $preparingOrders,
            'readyOrders' => $readyOrders,
            'history' => $history,
            'branch' => $this->branch,
        ]);
    }
}
?>
<div>
    {{-- Auto-refresh --}}
    <div wire:poll.5s="$refresh" class="hidden"></div>

    {{-- AMBIENT BACKGROUND --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index: 0;">
        <div class="absolute top-0 -left-20 w-96 h-96 bg-amber-400/20 rounded-full blur-[150px] animate-pulse"></div>
        <div class="absolute bottom-0 -right-20 w-80 h-80 bg-purple-500/10 rounded-full blur-[130px] animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-500/5 rounded-full blur-[100px]"></div>
    </div>

    <main class="relative min-h-screen bg-gray-50/80 dark:bg-[#0A0C10]/90 backdrop-blur-sm font-sans p-3 md:p-5 lg:p-6" style="z-index: 1;">

        {{-- HEADER --}}
        <div class="relative overflow-hidden rounded-2xl bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-xl border border-white/20 dark:border-white/5 shadow-xl p-4 md:p-5 mb-4">
            <div class="absolute inset-0 bg-gradient-to-r from-amber-500/5 via-transparent to-purple-500/5"></div>

            <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white shadow-lg shadow-amber-500/30">
                            <i class="fa-solid fa-cash-register text-base"></i>
                        </div>
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 rounded-full border-2 border-white dark:border-[#11131A] animate-pulse"></span>
                    </div>
                    <div>
                        <h1 class="text-lg md:text-xl font-black text-gray-800 dark:text-white tracking-tight">
                            Cashier <span class="text-amber-500 dark:text-amber-400">POS</span>
                        </h1>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-[9px] font-mono font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-gray-400">
                                {{ $branch->name ?? 'Branch' }} • {{ now()->format('h:i A') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                    <div class="px-3 py-1.5 rounded-xl text-center bg-emerald-50/80 dark:bg-emerald-500/10 border border-emerald-200/30 dark:border-emerald-500/20">
                        <p class="text-[6px] font-mono font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Today Sales</p>
                        <p class="text-sm font-black text-emerald-600 dark:text-emerald-400">₱{{ number_format($todaySales, 0) }}</p>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl text-center bg-blue-50/80 dark:bg-blue-500/10 border border-blue-200/30 dark:border-blue-500/20">
                        <p class="text-[6px] font-mono font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Orders</p>
                        <p class="text-sm font-black text-blue-600 dark:text-blue-400">{{ $todayOrders }}</p>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl text-center bg-amber-50/80 dark:bg-amber-500/10 border border-amber-200/30 dark:border-amber-500/20">
                        <p class="text-[6px] font-mono font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Pending</p>
                        <p class="text-sm font-black text-amber-600 dark:text-amber-400">{{ $pendingCount }}</p>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl text-center bg-purple-50/80 dark:bg-purple-500/10 border border-purple-200/30 dark:border-purple-500/20">
                        <p class="text-[6px] font-mono font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Barista Queue</p>
                        <p class="text-sm font-black text-purple-600 dark:text-purple-400">{{ $preparingCount + $readyCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- NOTIFICATIONS --}}
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="fixed top-20 right-4 md:right-6 z-50 max-w-sm w-full md:w-auto bg-white dark:bg-[#11131A] rounded-2xl shadow-2xl border-l-4 border-emerald-500 p-4 flex items-start gap-3 animate-slide-up">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-check-circle text-emerald-500"></i>
                </div>
                <p class="text-sm font-medium text-gray-800 dark:text-white flex-1">{{ session('success') }}</p>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        @if (session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="fixed top-20 right-4 md:right-6 z-50 max-w-sm w-full md:w-auto bg-white dark:bg-[#11131A] rounded-2xl shadow-2xl border-l-4 border-rose-500 p-4 flex items-start gap-3 animate-slide-up">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-rose-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-exclamation-circle text-rose-500"></i>
                </div>
                <p class="text-sm font-medium text-rose-600 dark:text-rose-400 flex-1">{{ session('error') }}</p>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        @if (session()->has('info'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="fixed top-20 right-4 md:right-6 z-50 max-w-sm w-full md:w-auto bg-white dark:bg-[#11131A] rounded-2xl shadow-2xl border-l-4 border-blue-500 p-4 flex items-start gap-3 animate-slide-up">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-info-circle text-blue-500"></i>
                </div>
                <p class="text-sm font-medium text-gray-800 dark:text-white flex-1">{{ session('info') }}</p>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        {{-- TABS --}}
        <div class="flex flex-wrap gap-1 bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-sm p-1 rounded-2xl border border-white/20 dark:border-white/5 shadow-sm mb-4">
            @php
                $tabs = [
                    ['id' => 'pos', 'icon' => 'fa-plus-circle', 'label' => 'New Order'],
                    ['id' => 'pending', 'icon' => 'fa-clock', 'label' => 'Pending', 'badge' => $pendingCount],
                    ['id' => 'history', 'icon' => 'fa-history', 'label' => 'History'],
                ];
            @endphp

            @foreach($tabs as $tab)
                <button wire:click="setTab('{{ $tab['id'] }}')"
                        class="relative flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl transition-all duration-300
                               {{ $activeTab === $tab['id']
                                   ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30 scale-[1.02]'
                                   : 'text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 hover:bg-gray-100 dark:hover:bg-white/5' }}">
                    <i class="fa-solid {{ $tab['icon'] }}"></i>
                    {{ $tab['label'] }}
                    @if(isset($tab['badge']) && $tab['badge'] > 0)
                        <span class="ml-0.5 px-1.5 py-0.5 rounded-full text-[8px] font-bold
                                   {{ $activeTab === $tab['id'] ? 'bg-white/20 text-white' : 'bg-amber-500/20 text-amber-500' }}">
                            {{ $tab['badge'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- POS TAB --}}
        @if($activeTab === 'pos')
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 md:gap-5">

            {{-- PRODUCTS --}}
            <div class="xl:col-span-8 space-y-4">

                <div class="relative">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input wire:model.live.debounce.300ms="searchProducts"
                           type="text"
                           placeholder="Search products... (press / to focus)"
                           id="product-search"
                           class="w-full bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-sm border border-white/20 dark:border-white/5 rounded-2xl pl-11 pr-4 py-3 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 outline-none transition text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm">
                </div>

                @if($categories->count() > 0)
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                        <button wire:click="setCategoryFilter('all')"
                                class="flex-shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition whitespace-nowrap
                                       {{ $categoryFilter === 'all'
                                           ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30'
                                           : 'bg-white/80 dark:bg-[#11131A]/80 text-gray-600 dark:text-gray-400 border border-white/20 dark:border-white/5 hover:text-amber-500' }}">
                            <i class="fa-solid fa-th-large text-[9px] mr-1"></i>
                            All
                            <span class="text-[9px] opacity-70">{{ $products->count() }}</span>
                        </button>
                        @foreach($categories as $cat)
                            @if($cat->products_count > 0)
                                <button wire:click="setCategoryFilter({{ $cat->id }})"
                                        class="flex-shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition whitespace-nowrap
                                               {{ $categoryFilter == $cat->id
                                                   ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30'
                                                   : 'bg-white/80 dark:bg-[#11131A]/80 text-gray-600 dark:text-gray-400 border border-white/20 dark:border-white/5 hover:text-amber-500' }}">
                                    {{ $cat->cat_name }}
                                    <span class="text-[9px] opacity-70">{{ $cat->products_count }}</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Products Grid with Images --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 gap-3">
                    @forelse($products as $product)
                    <div class="group bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-white/5 overflow-hidden hover:border-amber-400/50 hover:shadow-xl hover:shadow-amber-500/5 transition-all duration-300 card-hover flex flex-col">

                        {{-- Product Image --}}
                        <div class="relative aspect-square overflow-hidden bg-gradient-to-br from-amber-100/50 to-amber-200/30 dark:from-amber-500/10 dark:to-amber-700/5">
                            @if($product->image && Storage::disk('public')->exists($product->image))
                                <img src="{{ Storage::url($product->image) }}"
                                     alt="{{ $product->name }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fa-solid fa-mug-hot text-4xl text-amber-500/40"></i>
                                </div>
                            @endif

                            {{-- Stock badge (top-right overlay) --}}
                            @if($product->stock <= 0)
                                <div class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[8px] font-bold bg-rose-500 text-white shadow-md">
                                    <i class="fa-solid fa-times-circle mr-0.5"></i>Out
                                </div>
                            @elseif($product->stock <= 5)
                                <div class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[8px] font-bold bg-amber-500 text-white shadow-md">
                                    <i class="fa-solid fa-exclamation-circle mr-0.5"></i>{{ $product->stock }} left
                                </div>
                            @endif

                            {{-- Category badge (top-left overlay) --}}
                            <div class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[8px] font-mono font-bold uppercase tracking-wider bg-black/50 backdrop-blur-md text-white">
                                {{ $product->category->cat_name ?? 'General' }}
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="p-3 flex-grow flex flex-col">
                            <h3 class="font-bold text-gray-800 dark:text-white text-sm leading-tight line-clamp-1 mb-1">
                                {{ $product->name }}
                            </h3>
                            <p class="text-amber-500 dark:text-amber-400 font-black text-lg">₱{{ number_format($product->price, 2) }}</p>

                            {{-- Add Button (disabled when out of stock) --}}
                            <button wire:click="addToCart({{ $product->id }})"
                                    {{ $product->stock <= 0 ? 'disabled' : '' }}
                                    class="mt-auto pt-3 w-full py-2 text-xs font-bold rounded-xl border-2 transition-all duration-300
                                           {{ $product->stock <= 0
                                               ? 'border-gray-300 dark:border-gray-700 text-gray-400 cursor-not-allowed opacity-60'
                                               : 'border-amber-500/30 text-amber-500 hover:bg-amber-500 hover:text-white group-hover:border-amber-500' }}">
                                @if($product->stock <= 0)
                                    <i class="fa-solid fa-ban mr-1.5"></i> Out of Stock
                                @else
                                    <i class="fa-solid fa-plus mr-1.5"></i> Add
                                @endif
                            </button>

                            {{-- Request Stock Button (only when low or out) --}}
                            @if($product->stock <= 5)
                                <button wire:click="openStockRequest({{ $product->id }})"
                                        class="mt-2 w-full py-1.5 text-[10px] font-bold rounded-xl border border-dashed border-rose-400/50 text-rose-500 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-300">
                                    <i class="fa-solid fa-box-open mr-1"></i>
                                    Request Stock
                                </button>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full text-center py-16 text-gray-400 dark:text-gray-500">
                        <i class="fa-solid fa-box-open text-4xl mb-3 block opacity-50"></i>
                        <p class="text-sm font-medium">No products available</p>
                        <p class="text-xs mt-1">Try adjusting your search or filter</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- CART --}}
            <div class="xl:col-span-4">
                <div class="sticky top-6 bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-xl rounded-2xl border border-white/20 dark:border-white/5 shadow-xl overflow-hidden">

                    <div class="p-4 border-b border-gray-200/30 dark:border-white/5 bg-gradient-to-r from-amber-50/50 to-transparent dark:from-amber-500/5">
                        <h2 class="text-xs font-black uppercase text-gray-600 dark:text-gray-400 tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-shopping-cart text-amber-500"></i>
                            Current Order
                            <span class="ml-auto text-[10px] font-normal bg-gray-200/50 dark:bg-white/5 px-2 py-0.5 rounded-full">{{ count($cart) }} item(s)</span>
                        </h2>
                    </div>

                    <div class="p-3 max-h-[40vh] overflow-y-auto space-y-1.5 scrollbar-thin">
                        @forelse($cart as $productId => $item)
                        <div class="flex items-center justify-between py-2.5 px-2 rounded-xl hover:bg-gray-50/50 dark:hover:bg-white/5 transition group">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $item['name'] }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">₱{{ number_format($item['price'], 2) }}</span>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500">×</span>
                                    <input type="number"
                                           wire:change="updateQuantity({{ $productId }}, $event.target.value)"
                                           value="{{ $item['quantity'] }}"
                                           min="1" max="99"
                                           class="w-10 h-6 bg-transparent border border-gray-200 dark:border-gray-700 rounded-lg text-center text-xs font-bold text-gray-800 dark:text-white focus:border-amber-400 focus:ring-1 focus:ring-amber-400/20 outline-none">
                                </div>
                            </div>
                            <div class="flex items-center gap-3 ml-2">
                                <span class="text-sm font-bold text-amber-500 whitespace-nowrap">₱{{ number_format($item['total'], 2) }}</span>
                                <button wire:click="removeFromCart({{ $productId }})"
                                        class="text-gray-300 hover:text-rose-500 transition p-1 opacity-0 group-hover:opacity-100">
                                    <i class="fa-solid fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-shopping-cart text-3xl mb-3 block opacity-30"></i>
                            <p class="text-sm font-medium">Cart is empty</p>
                            <p class="text-xs mt-1">Add items to get started</p>
                        </div>
                        @endforelse
                    </div>

                    <div class="p-4 border-t border-gray-200/30 dark:border-white/5 bg-gray-50/50 dark:bg-[#0D0F14]/50">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Total</span>
                            <span class="text-2xl font-black text-amber-500">₱{{ number_format($cartTotal, 2) }}</span>
                        </div>

                        <div class="mb-3">
                            <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider block mb-2">
                                <i class="fa-solid fa-credit-card mr-1"></i> Payment Method
                            </label>

                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="$set('paymentMethod', 'cash')"
                                        class="flex items-center justify-center gap-2 py-2.5 text-xs font-bold rounded-xl border-2 transition-all duration-300
                                               {{ $paymentMethod === 'cash'
                                                   ? 'bg-amber-500 border-amber-500 text-white shadow-lg shadow-amber-500/30 scale-[1.02]'
                                                   : 'bg-white/50 dark:bg-[#1A1D26]/50 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-amber-400 hover:text-amber-500' }}">
                                    <span class="text-lg">💵</span>
                                    <span>Cash</span>
                                </button>

                                <button wire:click="$set('paymentMethod', 'ewallet')"
                                        class="flex items-center justify-center gap-2 py-2.5 text-xs font-bold rounded-xl border-2 transition-all duration-300
                                               {{ $paymentMethod === 'ewallet'
                                                   ? 'bg-amber-500 border-amber-500 text-white shadow-lg shadow-amber-500/30 scale-[1.02]'
                                                   : 'bg-white/50 dark:bg-[#1A1D26]/50 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-amber-400 hover:text-amber-500' }}">
                                    <span class="text-lg">📲</span>
                                    <span>E-Wallet</span>
                                </button>
                            </div>

                            @if($paymentMethod === 'ewallet')
                            <div class="mt-2 bg-gray-50/50 dark:bg-[#0D0F14]/50 rounded-xl p-2 border border-gray-200/30 dark:border-white/5">
                                <p class="text-[8px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 text-center">Select E-Wallet</p>
                                <div class="grid grid-cols-3 gap-1.5">
                                    @foreach([
                                        ['key' => 'gcash', 'label' => 'GCash', 'emoji' => '📱', 'color' => 'blue'],
                                        ['key' => 'paymaya', 'label' => 'Maya', 'emoji' => '💳', 'color' => 'green'],
                                        ['key' => 'grabpay', 'label' => 'GrabPay', 'emoji' => '🛵', 'color' => 'emerald'],
                                    ] as $ew)
                                    <button wire:click="$set('selectedEwallet', '{{ $ew['key'] }}')"
                                            class="flex flex-col items-center gap-0.5 py-2 rounded-xl border-2 transition-all duration-300
                                                   {{ $selectedEwallet === $ew['key']
                                                       ? 'bg-' . $ew['color'] . '-500 border-' . $ew['color'] . '-500 text-white shadow-md scale-[1.02]'
                                                       : 'bg-white/50 dark:bg-[#1A1D26]/50 border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-' . $ew['color'] . '-400' }}">
                                        <span class="text-lg leading-none">{{ $ew['emoji'] }}</span>
                                        <span class="text-[8px] font-bold">{{ $ew['label'] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        @if($paymentMethod === 'cash')
                        <div class="bg-gray-50/50 dark:bg-[#0D0F14]/50 rounded-xl p-3 mb-3 border border-gray-200/30 dark:border-white/5">
                            <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-money-bill-wave text-amber-500"></i>
                                Cash Received
                            </label>
                            <div class="relative mt-1">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">₱</span>
                                <input type="number" wire:model.live="cashReceived"
                                       class="w-full bg-transparent border-b-2 border-gray-300 dark:border-gray-600 pl-7 pr-2 py-1 text-xl font-bold focus:border-amber-500 outline-none text-gray-800 dark:text-white"
                                       placeholder="0.00">
                            </div>

                            <div class="grid grid-cols-4 gap-1 mt-2">
                                @foreach([20, 50, 100, 200, 500, 1000] as $amt)
                                    <button wire:click="setQuickCash({{ $amt }})"
                                            class="py-1.5 text-[10px] font-mono font-bold rounded-lg bg-white dark:bg-[#1A1D26] border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-amber-500 hover:text-amber-500 transition">
                                        ₱{{ $amt }}
                                    </button>
                                @endforeach
                                <button wire:click="setQuickCash('exact')"
                                        class="col-span-2 py-1.5 text-[10px] font-mono font-bold rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition">
                                    <i class="fa-solid fa-equals text-[8px] mr-1"></i>Exact
                                </button>
                                <button wire:click="$set('cashReceived', 0)"
                                        class="py-1.5 text-[10px] font-mono font-bold rounded-lg bg-white dark:bg-[#1A1D26] border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-rose-500 transition">
                                    <i class="fa-solid fa-times text-[8px]"></i>
                                </button>
                            </div>

                            <div class="flex justify-between mt-2 pt-2 border-t border-gray-200/30 dark:border-white/5">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400">Change</span>
                                <span class="text-lg font-bold text-emerald-500">₱{{ number_format($change, 2) }}</span>
                            </div>
                        </div>
                        @endif

                        @if($paymentMethod === 'ewallet' && !empty($cart))
                        @php
                            $ewalletLabels = ['gcash' => 'GCash', 'paymaya' => 'Maya', 'grabpay' => 'GrabPay'];
                            $ewalletColors = ['gcash' => 'blue', 'paymaya' => 'green', 'grabpay' => 'emerald'];
                            $ewalletEmojis = ['gcash' => '📱', 'paymaya' => '💳', 'grabpay' => '🛵'];
                            $currentLabel = $ewalletLabels[$selectedEwallet] ?? strtoupper($selectedEwallet);
                            $currentColor = $ewalletColors[$selectedEwallet] ?? 'amber';
                            $currentEmoji = $ewalletEmojis[$selectedEwallet] ?? '📲';
                            $qrData = $qrCodeUrl ?: 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($currentLabel . '|' . ($branch->name ?? 'Coffee Shop') . '|' . $cartTotal) . '&size=180x180';
                        @endphp
                        <div class="bg-white/50 dark:bg-[#0D0F14]/50 rounded-xl border border-gray-200/30 dark:border-white/5 overflow-hidden mb-3 shadow-sm">
                            <div class="flex items-center justify-between px-3 py-2 bg-{{ $currentColor }}-500">
                                <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                    {{ $currentEmoji }} {{ $currentLabel }}
                                </span>
                                <span class="text-sm font-extrabold text-white">₱{{ number_format($cartTotal, 2) }}</span>
                            </div>
                            <div class="flex flex-col items-center py-4 px-3 gap-3">
                                <div class="bg-white rounded-xl p-2 shadow-inner border border-gray-100 dark:border-gray-800">
                                    <img src="{{ $qrData }}" alt="{{ $currentLabel }} QR" class="w-32 h-32 object-contain">
                                </div>
                                <p class="text-[8px] text-gray-400 dark:text-gray-500 text-center">
                                    <i class="fa-solid fa-shield-alt mr-1"></i>
                                    Ask customer to scan QR code
                                </p>
                                <button wire:click="confirmEwalletPayment" wire:loading.attr="disabled"
                                        class="w-full py-2.5 text-xs font-bold text-white rounded-xl transition-all duration-300 hover:shadow-lg bg-{{ $currentColor }}-500 hover:bg-{{ $currentColor }}-600 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="confirmEwalletPayment">
                                        <i class="fa-solid fa-check-circle mr-1"></i> Confirm Payment
                                    </span>
                                    <span wire:loading wire:target="confirmEwalletPayment">
                                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Processing...
                                    </span>
                                </button>
                            </div>
                        </div>
                        @endif

                        @if($paymentMethod === 'cash')
                        <button wire:click="checkoutOrder" wire:loading.attr="disabled" {{ empty($cart) ? 'disabled' : '' }}
                                class="w-full py-3 text-sm font-bold text-white rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:shadow-lg hover:shadow-amber-500/30 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="checkoutOrder">
                                <i class="fa-solid fa-check mr-1.5"></i> Place Order
                            </span>
                            <span wire:loading wire:target="checkoutOrder">
                                <i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Processing...
                            </span>
                        </button>
                        @endif

                        <button wire:click="clearCart" class="w-full mt-1.5 text-[10px] text-gray-400 hover:text-rose-500 transition text-center">
                            <i class="fa-solid fa-trash mr-1"></i> Clear Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- PENDING TAB --}}
        @if($activeTab === 'pending')

        <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="w-1 h-6 bg-amber-500 rounded-full"></span>
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    Walk-in Orders (Awaiting Cash Payment)
                    @if($pendingCount > 0)
                        <span class="bg-amber-500 text-white px-2 py-0.5 rounded-full text-[8px]">{{ $pendingCount }}</span>
                    @endif
                </h3>
            </div>

            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="searchOrders"
                       placeholder="Search order # or customer..."
                       class="w-full bg-white/80 dark:bg-[#11131A]/80 border border-white/20 dark:border-white/5 rounded-xl pl-8 pr-3 py-2 text-xs focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 outline-none text-gray-800 dark:text-white placeholder-gray-400 transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($pendingOrders as $order)
                @php
                    $elapsed = $order->created_at->diffInMinutes(now());
                    $urgency = $elapsed >= 15 ? 'rose' : ($elapsed >= 5 ? 'amber' : 'emerald');
                @endphp
                <div class="group bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-sm rounded-2xl border-l-4 border-l-amber-500 border border-white/20 dark:border-white/5 p-5 shadow-sm hover:shadow-xl hover:shadow-amber-500/5 transition-all duration-300">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="font-mono font-bold text-amber-500 text-sm">#{{ $order->order_number }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ $order->created_at->format('h:i A') }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 text-[8px] font-bold">
                                    <i class="fa-solid fa-coins"></i>
                                    Cash
                                </span>
                                @if($order->user)
                                    <span class="text-[9px] text-gray-500 ml-1">{{ $order->user->name }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 rounded-full text-[8px] font-bold bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30">
                                <i class="fa-solid fa-clock mr-1"></i> Pending
                            </span>
                            <p class="text-[9px] font-mono font-bold mt-1 text-{{ $urgency }}-500 dark:text-{{ $urgency }}-400">
                                @if($urgency === 'rose')
                                    <i class="fa-solid fa-fire"></i> Waiting {{ $elapsed }}m
                                @elseif($urgency === 'amber')
                                    <i class="fa-solid fa-hourglass-half"></i> {{ $elapsed }}m
                                @else
                                    <i class="fa-solid fa-check"></i> Fresh {{ $elapsed }}m
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="space-y-0.5 mb-3 max-h-20 overflow-y-auto scrollbar-thin">
                        @foreach($order->items as $item)
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            <span class="font-bold text-amber-500">{{ $item->quantity }}×</span> {{ $item->product->name }}
                        </p>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between mb-3 pt-2 border-t border-gray-100/50 dark:border-white/5">
                        <span class="text-[10px] text-gray-500">
                            {{ $order->items->sum('quantity') }} items
                        </span>
                        <p class="text-base font-black text-amber-500">₱{{ number_format($order->total_amount, 2) }}</p>
                    </div>

                    <div class="flex gap-2">
                        <button wire:click="confirmPayment({{ $order->id }})"
                                class="flex-1 py-2 text-xs font-bold text-white rounded-xl bg-emerald-500 hover:bg-emerald-600 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/30">
                            <i class="fa-solid fa-check mr-1.5"></i> Confirm Payment
                        </button>
                        <button wire:click="cancelOrder({{ $order->id }})"
                                wire:confirm="Cancel this order?"
                                class="px-4 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-700 rounded-xl hover:border-rose-500 hover:text-rose-500 transition-all duration-300">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
            @empty
            <div class="col-span-full text-center py-16 text-gray-400 dark:text-gray-500 bg-white/50 dark:bg-[#11131A]/50 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700">
                <i class="fa-solid fa-inbox text-4xl mb-3 block opacity-30"></i>
                <p class="text-sm font-medium">No pending orders</p>
                <p class="text-xs mt-1">{{ $searchOrders ? 'Try a different search' : 'Walk-in orders will appear here' }}</p>
            </div>
            @endforelse
        </div>
        @endif

        {{-- HISTORY TAB --}}
        @if($activeTab === 'history')
        <div class="bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-white/5 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/50 dark:bg-[#0D0F14]/50 border-b border-gray-200/30 dark:border-white/5">
                        <tr class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">Order</th>
                            <th class="px-4 py-3 text-left">Items</th>
                            <th class="px-4 py-3 text-left">Total</th>
                            <th class="px-4 py-3 text-left">Payment</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/50 dark:divide-white/5">
                        @forelse($history as $order)
                        <tr class="hover:bg-gray-50/30 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3 font-mono font-bold text-amber-500 text-xs">#{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $order->items->sum('quantity') }} items</td>
                            <td class="px-4 py-3 font-bold text-gray-800 dark:text-white">₱{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ ucfirst($order->payment_method) }}</td>
                            <td class="px-4 py-3">
                                @if($order->status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                                        <i class="fa-solid fa-check-circle"></i> Completed
                                    </span>
                                @elseif($order->status === 'cancelled')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30">
                                        <i class="fa-solid fa-xmark-circle"></i> Cancelled
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30">
                                        <i class="fa-solid fa-spinner"></i> {{ ucfirst($order->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $order->created_at->format('M d, h:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-history text-4xl mb-3 block opacity-30"></i>
                                <p class="text-sm font-medium">No transaction history</p>
                                <p class="text-xs mt-1">Completed orders will appear here</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-2 border-t border-gray-200/30 dark:border-white/5 bg-gray-50/50 dark:bg-[#0D0F14]/50 flex items-center justify-between">
                <span class="text-[10px] text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-clock mr-1"></i> Showing last 20 transactions
                </span>
                <span class="text-[10px] text-gray-400 dark:text-gray-500 font-mono">
                    {{ $history->count() }} records
                </span>
            </div>
        </div>
        @endif

        {{-- ═════ STOCK REQUEST MODAL ═════ --}}
        @if($showStockRequestModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeStockRequest"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-[#11131A] rounded-2xl shadow-2xl border border-white/20 dark:border-white/5 overflow-hidden">
                {{-- Header --}}
                <div class="px-5 py-4 border-b border-gray-200/30 dark:border-white/5 bg-gradient-to-r from-rose-500/10 to-transparent">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl bg-rose-500/20 flex items-center justify-center">
                                <i class="fa-solid fa-box-open text-rose-500"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-gray-800 dark:text-white">Request Stock</h3>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Send to Branch Manager</p>
                            </div>
                        </div>
                        <button wire:click="closeStockRequest" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-5 space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Product</label>
                        <p class="text-sm font-bold text-gray-800 dark:text-white mt-1">{{ $requestProductName }}</p>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Quantity to Request</label>
                        <input type="number"
                               wire:model="requestQuantity"
                               min="1" max="9999"
                               class="mt-1 w-full bg-gray-50 dark:bg-[#0D0F14] border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-lg font-bold text-gray-800 dark:text-white focus:border-rose-400 outline-none">
                        @error('requestQuantity') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Note (optional)</label>
                        <textarea wire:model="requestNote"
                                  rows="2"
                                  placeholder="e.g., Running low, need for weekend rush"
                                  class="mt-1 w-full bg-gray-50 dark:bg-[#0D0F14] border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-800 dark:text-white focus:border-rose-400 outline-none resize-none"></textarea>
                        @error('requestNote') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-gray-200/30 dark:border-white/5 bg-gray-50/50 dark:bg-[#0D0F14]/50 flex justify-end gap-2">
                    <button wire:click="closeStockRequest"
                            class="px-4 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
                        Cancel
                    </button>
                    <button wire:click="submitStockRequest"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-xs font-bold text-white rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:shadow-lg hover:shadow-rose-500/30 transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="submitStockRequest">
                            <i class="fa-solid fa-paper-plane mr-1"></i> Send Request
                        </span>
                        <span wire:loading wire:target="submitStockRequest">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i> Sending...
                        </span>
                    </button>
                </div>
            </div>
        </div>
        @endif
    </main>
</div>

<style>
    .scrollbar-thin::-webkit-scrollbar { width: 3px; height: 3px; }
    .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: rgba(210, 150, 75, 0.3);
        border-radius: 10px;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: rgba(210, 150, 75, 0.5);
    }

    @keyframes slide-up {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .animate-slide-up {
        animation: slide-up 0.3s ease-out forwards;
    }

    .card-hover {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @media (max-width: 640px) {
        .stat-item { min-width: 44px; padding: 0.25rem 0.5rem; }
        .stat-item .stat-label { font-size: 5px; }
        .stat-item .stat-count { font-size: 0.875rem; }
    }
</style>

<script>
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2' || (e.key === '/' && !e.target.matches('input, textarea'))) {
            e.preventDefault();
            document.getElementById('product-search')?.focus();
        }
        if (e.key === 'Escape') {
            document.getElementById('product-search')?.blur();
        }
    });
</script>
