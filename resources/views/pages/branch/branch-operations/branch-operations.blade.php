<?php

use App\Models\Branch;
use App\Models\CustomerPoint;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\LoyaltySetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RewardItem;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.branch')] class extends Component
{
    #[Locked]
    public string $activeTab = 'orders';
    public array $cart = [];
    public ?int $stock_product_id = null;
    public string $stock_type = 'IN';
    public float $stock_quantity = 0;
    public string $stock_note = '';
    public string $staff_name = '';
    public string $staff_email = '';
    public string $staff_password = '';
    public string $staff_role = 'cashier';
    public ?int $editing_staff_id = null;
    
    // Loyalty
    public $customerId = null;
    public $customerSearch = '';
    public $customerPoints = 0;
    public $isLoyaltyEnabled = false;

    #[Locked]
    public int $tenantId;

    #[Locked]
    public int $branchId;

    protected function rules()
    {
        return [
            'staff_name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s.]+$/',
            'staff_email' => 'required|email|max:255|unique:users,email,' . ($this->editing_staff_id ?? 'NULL'),
            'staff_password' => $this->editing_staff_id ? 'nullable|min:8|max:255' : 'required|min:8|max:255',
            'staff_role' => 'required|in:cashier,barista',
        ];
    }

    public function mount(): void
    {
        $user = Auth::user();
        $this->tenantId = $user->tenant_id;
        $this->branchId = $user->branch_id;

        session(['branch_id' => $this->branchId]);

        if (request()->query('tab')) {
            $this->setTab(request()->query('tab'));
        }

        $this->checkLoyaltyStatus();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['stock', 'orders'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function checkLoyaltyStatus()
    {
        $branch = Branch::where('tenant_id', $this->tenantId)->find($this->branchId);
        
        if (!$branch || !$branch->isLoyaltyEnabled()) {
            $this->isLoyaltyEnabled = false;
            return;
        }

        $tenantSettings = LoyaltySetting::where('tenant_id', $this->tenantId)->first();
        $this->isLoyaltyEnabled = $tenantSettings && $tenantSettings->is_enabled;
    }

    public function searchCustomer()
    {
        if (empty($this->customerSearch)) {
            $this->customerId = null;
            $this->customerPoints = 0;
            return;
        }

        $customer = User::where('tenant_id', $this->tenantId)
            ->where(function($query) {
                $query->where('name', 'like', '%' . $this->customerSearch . '%')
                    ->orWhere('email', 'like', '%' . $this->customerSearch . '%')
                    ->orWhere('phone', 'like', '%' . $this->customerSearch . '%');
            })
            ->where('role', 'customer')
            ->first();

        if ($customer) {
            $this->customerId = $customer->id;
            $this->loadCustomerPoints();
            session()->flash('success', 'Customer found: ' . $customer->name);
        } else {
            $this->customerId = null;
            $this->customerPoints = 0;
            session()->flash('error', 'Customer not found.');
        }
    }

    public function loadCustomerPoints()
    {
        if (!$this->customerId) {
            $this->customerPoints = 0;
            return;
        }

        $points = CustomerPoint::where('tenant_id', $this->tenantId)
            ->where('user_id', $this->customerId)
            ->first();

        $this->customerPoints = $points ? $points->points : 0;
    }

    public function getAvailableRewardsProperty()
    {
        if (!$this->isLoyaltyEnabled || !$this->customerId) {
            return collect();
        }

        return RewardItem::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->where('points_required', '<=', $this->customerPoints)
            ->get();
    }

    public function getCartTotalProperty(): float
    {
        $total = 0;
        foreach ($this->cart as $productId => $qty) {
            $product = Product::forLocation($this->tenantId, $this->branchId)->find($productId);
            if ($product) {
                $total += $product->price * $qty;
            }
        }
        return $total;
    }

    public function addToCart($productId)
    {
        $product = Product::forLocation($this->tenantId, $this->branchId)
            ->where('is_active', true)
            ->find($productId);

        if (!$product) {
            session()->flash('error', 'Product not found.');
            return;
        }

        $inventory = Inventory::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('product_id', $productId)
            ->first();

        if (!$inventory || $inventory->quantity < 1) {
            session()->flash('error', 'Product out of stock.');
            return;
        }

        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId] >= $inventory->quantity) {
                session()->flash('error', 'Not enough stock available.');
                return;
            }
            $this->cart[$productId]++;
        } else {
            $this->cart[$productId] = 1;
        }
    }

    public function decrementCartItem($productId)
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]--;
            if ($this->cart[$productId] <= 0) {
                unset($this->cart[$productId]);
            }
        }
    }

    public function removeCartItem($productId)
    {
        unset($this->cart[$productId]);
    }

    public function clearCart()
    {
        $this->cart = [];
    }

    public function placeOrder()
    {
        abort_unless(Auth::user()?->role === 'cashier', 403, 'Sales are handled by the cashier team.');

        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty.');
            return;
        }

        try {
            DB::beginTransaction();

            $orderNumber = Order::where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->max('order_number') + 1;

            $total = $this->cartTotal;

            $order = Order::create([
                'tenant_id' => $this->tenantId,
                'branch_id' => $this->branchId,
                'user_id' => $this->customerId ?? null,
                'order_number' => $orderNumber,
                'total_amount' => $total,
                'status' => 'pending',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'is_reward_order' => false,
            ]);

            foreach ($this->cart as $productId => $qty) {
                $product = Product::forLocation($this->tenantId, $this->branchId)->find($productId);
                if ($product) {
                    OrderItem::create([
                        'tenant_id' => $this->tenantId,
                        'branch_id' => $this->branchId,
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'price' => $product->price,
                        'subtotal' => $product->price * $qty,
                    ]);

                    $inventory = Inventory::where('tenant_id', $this->tenantId)
                        ->where('branch_id', $this->branchId)
                        ->where('product_id', $productId)
                        ->first();

                    if ($inventory) {
                        $inventory->decrement('quantity', $qty);
                    }
                }
            }

            DB::commit();

            $this->cart = [];
            $this->customerId = null;
            $this->customerPoints = 0;
            $this->customerSearch = '';

            session()->flash('success', "Order #" . str_pad($orderNumber, 4, '0', STR_PAD_LEFT) . " logged successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error placing order: ' . $e->getMessage());
        }
    }

    public function adjustStock()
    {
        $this->validate([
            'stock_product_id' => 'required|exists:products,id',
            'stock_quantity' => 'required|numeric|min:0.0001|max:999999',
        ]);

        if (! Product::forLocation($this->tenantId, $this->branchId)->whereKey($this->stock_product_id)->exists()) {
            $this->addError('stock_product_id', 'Choose an item from this coffee shop.');
            return;
        }

        $product = Product::forLocation($this->tenantId, $this->branchId)
            ->find($this->stock_product_id);

        if (!$product) {
            session()->flash('error', 'Product not found.');
            return;
        }

        $inventory = Inventory::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('product_id', $this->stock_product_id)
            ->first();

        if ($this->stock_type === 'IN') {
            if ($inventory) {
                $inventory->increment('quantity', $this->stock_quantity);
            } else {
                Inventory::create([
                    'tenant_id' => $this->tenantId,
                    'branch_id' => $this->branchId,
                    'product_id' => $this->stock_product_id,
                    'quantity' => $this->stock_quantity,
                    'min_stock' => 5,
                ]);
            }
            $message = "Added {$this->stock_quantity} units to {$product->name}";
        } elseif ($this->stock_type === 'OUT') {
            if (!$inventory || $inventory->quantity < $this->stock_quantity) {
                session()->flash('error', 'Insufficient stock.');
                return;
            }
            $inventory->decrement('quantity', $this->stock_quantity);
            $message = "Removed {$this->stock_quantity} units from {$product->name}";
        } else {
            if ($inventory) {
                $inventory->update(['quantity' => $this->stock_quantity]);
            } else {
                Inventory::create([
                    'tenant_id' => $this->tenantId,
                    'branch_id' => $this->branchId,
                    'product_id' => $this->stock_product_id,
                    'quantity' => $this->stock_quantity,
                    'min_stock' => 5,
                ]);
            }
            $message = "Set {$product->name} stock to {$this->stock_quantity}";
        }

        InventoryLog::create([
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchId,
            'product_id' => $this->stock_product_id,
            'quantity' => $this->stock_quantity,
            'type' => $this->stock_type,
            'note' => $this->stock_note ?? 'Stock adjustment',
            'user_id' => Auth::id(),
        ]);

        $this->reset(['stock_product_id', 'stock_quantity', 'stock_note']);
        session()->flash('success', $message);
    }

    public function advanceOrderStatus($orderId)
    {
        abort_unless(Auth::user()?->role === 'barista', 403, 'Order preparation is managed by the barista team.');

        $order = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->find($orderId);

        if (!$order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        $statusFlow = ['pending', 'preparing', 'ready', 'completed'];
        $currentIndex = array_search($order->status, $statusFlow);
        
        if ($currentIndex !== false && $currentIndex < count($statusFlow) - 1) {
            $nextStatus = $statusFlow[$currentIndex + 1];
            $order->update(['status' => $nextStatus]);
            session()->flash('success', "Order #{$order->order_number} status updated to " . ucfirst($nextStatus));
        }
    }

    public function render()
    {
        if (! in_array($this->activeTab, ['stock', 'orders'], true)) {
            $this->activeTab = 'orders';
        }

        $products = Product::forLocation($this->tenantId, $this->branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventories = Inventory::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->get()
            ->keyBy('product_id');

        $staff = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->orderBy('name')
            ->get();

        $todaysOrders = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->get();

        return view('pages.branch.branch-operations.branch-operations', [
            'products' => $products,
            'inventories' => $inventories,
            'staff' => $staff,
            'todaysOrders' => $todaysOrders,
            'availableRewards' => $this->availableRewards,
            'isLoyaltyEnabled' => $this->isLoyaltyEnabled,
            'customerPoints' => $this->customerPoints,
        ]);
    }
}
?>
<div class="relative min-h-screen bg-slate-100/70 dark:bg-[#090C12] text-slate-800 dark:text-slate-200 transition-colors duration-200 font-sans">
    
    {{-- Lightweight Static Glow --}}
    <div class="fixed inset-0 pointer-events-none opacity-40 dark:opacity-20" 
         style="background: radial-gradient(circle at 15% 10%, rgba(210, 150, 75, 0.15), transparent 45%), radial-gradient(circle at 85% 20%, rgba(16, 185, 129, 0.12), transparent 40%);">
    </div>

    <main class="relative z-10 p-3 sm:p-4 md:p-5 max-w-7xl mx-auto space-y-3.5">

        {{-- ========================================================================= --}}
        {{-- HEADER HUD WITH TAB CONTROLLER --}}
        {{-- ========================================================================= --}}
        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-[#0F141F] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#d2964b] to-[#91561f] flex items-center justify-center text-white shadow-md shadow-[#d2964b]/20 flex-shrink-0">
                    <i class="fa-solid fa-cash-register text-sm"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-black tracking-tight text-slate-900 dark:text-white">
                        Branch Operations Station
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                        Branch workspace • inventory and order overview
                    </p>
                </div>
            </div>

            {{-- Segmented Tabs Controller --}}
            <div class="inline-flex p-1 rounded-xl bg-slate-100 dark:bg-[#07090F] border border-slate-200 dark:border-slate-800 flex-wrap">
                <button wire:click="setTab('stock')" 
                    class="px-3 py-1 text-xs font-mono font-bold rounded-lg transition {{ $activeTab === 'stock' ? 'bg-[#d2964b] text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                    <i class="fa-solid fa-dolly mr-1"></i> Stock Adjust
                </button>
                <button wire:click="setTab('orders')" 
                    class="px-3 py-1 text-xs font-mono font-bold rounded-lg transition {{ $activeTab === 'orders' ? 'bg-[#d2964b] text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                    <i class="fa-solid fa-list-check mr-1"></i> Orders ({{ $todaysOrders->count() }})
                </button>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" 
                 class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-mono font-bold flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" 
                 class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 text-xs font-mono font-bold flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- TAB 1: POS / CASHIER --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'pos')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">
            
            {{-- Product Selection Catalog (7 cols) --}}
            <div class="lg:col-span-7 p-4 rounded-2xl bg-white dark:bg-[#0F141F] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-mug-saucer text-[#d2964b]"></i> Fast Order Products
                        </h2>
                        <span class="text-[10px] font-mono text-slate-400">Click item to add to ticket</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-[460px] overflow-y-auto pr-1">
                        @forelse($products as $product)
                            @php $stock = $inventories[$product->id]->quantity ?? 0; @endphp
                            <button wire:click="addToCart({{ $product->id }})" 
                                {{ $stock < 1 ? 'disabled' : '' }}
                                class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between {{ $stock < 1 ? 'opacity-40 border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 cursor-not-allowed' : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/80 dark:bg-[#07090F] hover:border-[#d2964b] hover:shadow-sm' }}">
                                <div>
                                    <div class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ $product->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">Stock: {{ $stock }}</div>
                                </div>
                                <div class="mt-2 font-mono font-bold text-xs text-[#d2964b]">
                                    ₱{{ number_format($product->price, 2) }}
                                </div>
                            </button>
                        @empty
                            <div class="col-span-3 text-center py-10 text-xs font-mono text-slate-400">
                                No active products in branch menu.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Active Order Ticket & Cart (5 cols) --}}
            <div class="lg:col-span-5 p-4 rounded-2xl bg-white dark:bg-[#0F141F] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-[#d2964b]"></i> Active Ticket
                        </h2>
                        @if(!empty($cart))
                            <button wire:click="clearCart" class="text-[10px] font-mono text-rose-500 hover:underline">Clear Ticket</button>
                        @endif
                    </div>

                    {{-- Customer Search for Loyalty --}}
                    @if($isLoyaltyEnabled)
                    <div class="mb-3 p-2.5 rounded-xl bg-slate-50 dark:bg-[#07090F] border border-slate-200 dark:border-slate-800">
                        <label class="block text-[9px] font-mono font-bold uppercase text-slate-400 mb-1">Customer Loyalty Account</label>
                        <div class="flex gap-1.5">
                            <input type="text" wire:model="customerSearch" placeholder="Name or email..." 
                                class="flex-1 px-2.5 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F141F] text-slate-900 dark:text-white outline-none">
                            <button wire:click="searchCustomer" class="px-2.5 py-1 bg-[#d2964b] text-white rounded-lg text-xs font-mono font-bold hover:bg-[#b87c3a]">
                                Find
                            </button>
                        </div>
                        @if($customerId)
                            <div class="mt-1.5 text-[10px] font-mono text-emerald-500 font-bold flex items-center justify-between">
                                <span>Points: {{ $customerPoints }} pts</span>
                                <span class="text-slate-400">Account Linked</span>
                            </div>
                        @endif
                    </div>
                    @endif

                    {{-- Cart Items --}}
                    <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                        @forelse($cart as $pId => $qty)
                            @php $p = Product::forLocation($this->tenantId, $this->branchId)->find($pId); @endphp
                            @if($p)
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-[#07090F] border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <div class="truncate mr-2">
                                        <div class="font-bold text-slate-900 dark:text-white truncate">{{ $p->name }}</div>
                                        <div class="text-[10px] font-mono text-[#d2964b]">₱{{ number_format($p->price, 2) }} each</div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button wire:click="decrementCartItem({{ $pId }})" class="w-5 h-5 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold">-</button>
                                        <span class="font-mono font-bold">{{ $qty }}</span>
                                        <button wire:click="addToCart({{ $pId }})" class="w-5 h-5 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold">+</button>
                                        <button wire:click="removeCartItem({{ $pId }})" class="text-rose-500 hover:text-rose-700 ml-1"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="text-center py-8 text-xs font-mono text-slate-400">
                                Ticket is empty. Select items on the left.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Checkout Footer --}}
                <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex justify-between items-baseline mb-3 font-mono">
                        <span class="text-xs font-bold text-slate-500">Ticket Total:</span>
                        <span class="text-xl font-black text-[#d2964b]">₱{{ number_format($this->cartTotal, 2) }}</span>
                    </div>
                    <button wire:click="placeOrder" 
                        {{ empty($cart) ? 'disabled' : '' }}
                        class="w-full py-2.5 rounded-xl text-xs font-bold font-mono text-white bg-gradient-to-r from-[#d2964b] to-[#a36528] hover:shadow-md transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-check"></i> Place & Record Order
                    </button>
                </div>
            </div>

        </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- TAB 2: QUICK STOCK ADJUSTMENT --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'stock')
        <div class="max-w-2xl mx-auto p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#0F141F] border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-dolly text-[#d2964b]"></i> Quick Inventory Adjustment
                </h2>
                <span class="text-[10px] font-mono text-slate-400">Logged to Branch Ledger</span>
            </div>

            <form wire:submit.prevent="adjustStock" class="space-y-3">
                <div>
                    <label class="block text-[10px] font-mono font-bold uppercase text-slate-500 mb-1">Target Product</label>
                    <select wire:model="stock_product_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#07090F] text-slate-900 dark:text-white outline-none focus:border-[#d2964b]">
                        <option value="">Select product...</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }} (Current: {{ $inventories[$prod->id]->quantity ?? 0 }})</option>
                        @endforeach
                    </select>
                    @error('stock_product_id') <span class="text-[10px] text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase text-slate-500 mb-1">Adjustment Mode</label>
                        <select wire:model="stock_type" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#07090F] text-slate-900 dark:text-white outline-none focus:border-[#d2964b]">
                            <option value="IN">➕ Restock (Add)</option>
                            <option value="OUT">➖ Waste / Spoilage (Subtract)</option>
                            <option value="ADJUST">🔄 Exact Count (Set)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase text-slate-500 mb-1">Quantity</label>
                        <input type="number" step="0.0001" min="0.0001" wire:model="stock_quantity" placeholder="0" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#07090F] text-slate-900 dark:text-white outline-none focus:border-[#d2964b]">
                        @error('stock_quantity') <span class="text-[10px] text-rose-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-mono font-bold uppercase text-slate-500 mb-1">Adjustment Reason / Note</label>
                    <input type="text" wire:model="stock_note" placeholder="e.g. Daily restocking batch #4" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#07090F] text-slate-900 dark:text-white outline-none focus:border-[#d2964b]">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-bold font-mono text-white bg-[#d2964b] hover:bg-[#b87c3a] transition">
                    Commit Inventory Adjustment
                </button>
            </form>
        </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- TAB 3: ACTIVE ORDERS MONITOR --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'orders')
        <div class="rounded-2xl bg-white dark:bg-[#0F141F] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-[#d2964b]"></i> Today's Transactions Ledger
                </h2>
                <span class="text-[10px] font-mono text-slate-400">{{ $todaysOrders->count() }} Total Logged</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-[#07090F] text-slate-500 font-mono text-[10px] uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-2.5">Order #</th>
                            <th class="px-4 py-2.5">Time</th>
                            <th class="px-4 py-2.5">Amount</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Advance Flow</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($todaysOrders as $ord)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition">
                                <td class="px-4 py-2.5 font-mono font-bold text-[#d2964b]">
                                    #{{ str_pad($ord->order_number, 4, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-400 font-mono text-[11px]">
                                    {{ $ord->created_at->format('h:i A') }}
                                </td>
                                <td class="px-4 py-2.5 font-mono font-bold text-slate-900 dark:text-white">
                                    ₱{{ number_format($ord->total_amount, 2) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ ucfirst($ord->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-slate-500 font-mono text-[10px] font-bold">Barista manages order status</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-xs font-mono text-slate-400">
                                    No orders recorded today.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </main>
</div>
