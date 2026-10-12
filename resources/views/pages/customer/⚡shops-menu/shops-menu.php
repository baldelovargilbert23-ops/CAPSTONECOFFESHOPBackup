<?php

namespace App\Livewire\Pages\Customer;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Support\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component
{
    #[Locked]
    public ?int $branchId = null;          // NULL = main store

    #[Locked]
    public int $tenantId;
    public string $branchName = '';
    public ?string $branchAddress = null;
    public ?string $branchLogoUrl = null;
    public string $tenantName = '';
    public bool $branchIsMain = false;

    #[Url(history: true, as: 'cat')]
    public $activeCategory = 'all';

    public int $step = 1;
    public int $totalSteps = 4;

    public array $cart = [];

    public string $orderType = 'pickup';
    public string $paymentMethod = 'cash';

    public ?int $detailProductId = null;
    public int $detailQty = 1;

    protected array $onlinePaymentMethods = ['paymongo', 'gcash', 'card', 'paymaya', 'grab_pay'];

    /**
     * Route: /customer/shop/menu/{tenant}/{branch}
     *   $tenant — required, the shop that owns the menu
     *   $branch — 0 for main store (branch_id = NULL), or a real branch ID
     */
    public function mount($tenant, $branch = 0): void
    {
        $tenantModel = Tenant::where('id', $tenant)
            ->where('status', 'active')
            ->firstOrFail();

        $this->tenantId   = $tenantModel->id;
        $this->tenantName = $tenantModel->name;

        if ((int) $branch === 0) {
            // Main store — the tenant's own store (branch_id = NULL)
            $this->branchId      = null;
            $this->branchName    = $tenantModel->name . ' (Main Store)';
            $this->branchAddress = $tenantModel->address ?? null;
            $this->branchLogoUrl = $tenantModel->logo_url ?? null;
            $this->branchIsMain  = true;
        } else {
            // Sub-branch — must belong to this tenant
            $b = Branch::withoutGlobalScope('branch')
                ->where('id', $branch)
                ->where('tenant_id', $tenantModel->id)   // ownership check
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->first();

            if (! $b) {
                abort(404, 'Branch not found.');
            }

            $this->branchId      = $b->id;
            $this->branchName    = $b->name;
            $this->branchAddress = $b->address;
            $this->branchLogoUrl = $b->logo_url ?? null;
            $this->branchIsMain  = false;
        }

        $this->cart = Auth::check()
            ? (array) session()->get('cart_' . ($this->branchId ?? 0), [])
            : [];

        $this->syncCart();
    }

    /* ================================================================
     *  COMPUTED
     * ================================================================ */

    #[Computed]
    public function stockMap(): array
    {
        $products = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->with('recipe.ingredient')
            ->get();

        return StockService::availableQuantities($products, $this->branchId, $this->tenantId);
    }

    #[Computed]
    public function products()
    {
        $stock = $this->stockMap;
        $stockedIds = array_keys(array_filter($stock, fn ($quantity) => $quantity > 0));
        if (empty($stockedIds)) {
            return collect();
        }

        $query = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->whereIn('id', $stockedIds)
            ->withCount(['reviews as reviews_count' => $this->visibleReviews()])
            ->withAvg(['reviews as reviews_avg_rating' => $this->visibleReviews()], 'rating')
            ->orderBy('name');

        if (is_numeric($this->activeCategory)) {
            $query->where('category_id', (int) $this->activeCategory);
        }

        return $query->get()->each(function ($p) use ($stock) {
            $qty = $stock[$p->id] ?? 0;
            $p->setAttribute('stock', $qty);
            $p->setAttribute('in_stock', $qty > 0);
        });
    }

    #[Computed]
    public function categories()
    {
        $stockedIds = array_keys(array_filter($this->stockMap, fn ($quantity) => $quantity > 0));
        if (empty($stockedIds)) {
            return collect();
        }

        return Category::where('tenant_id', $this->tenantId)
            ->withCount(['products' => fn ($q) => $q->whereIn('id', $stockedIds)->where('is_active', true)])
            ->orderBy('cat_name')
            ->get()
            ->filter(fn ($c) => $c->products_count > 0)
            ->values();
    }

    #[Computed]
    public function cartItems(): array
    {
        if (empty($this->cart)) {
            return [];
        }

        $ids      = array_map('intval', array_keys($this->cart));
        $products = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($this->cart as $id => $qty) {
            $id = (int) $id;
            $p  = $products->get($id);
            if (! $p) continue;

            $price = (float) $p->price;
            $qty   = (int) $qty;

            $items[$id] = [
                'id'        => $id,
                'name'      => $p->name,
                'price'     => $price,
                'image'     => $p->image_url,
                'qty'       => $qty,
                'available' => $this->stockMap[$id] ?? 0,
                'subtotal'  => $price * $qty,
            ];
        }

        return $items;
    }

    #[Computed]
    public function cartTotal(): float
    {
        return (float) array_sum(array_column($this->cartItems, 'subtotal'));
    }

    #[Computed]
    public function cartCount(): int
    {
        return (int) array_sum(array_column($this->cartItems, 'qty'));
    }

    #[Computed]
    public function detail(): ?array
    {
        if (! $this->detailProductId) return null;

        $p = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->find($this->detailProductId);

        if (! $p) return null;

        $base = fn () => $p->reviews()->withoutGlobalScope('branch')->where('is_visible', true);

        $counts = $base()->selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $dist = [];
        $total = 0;
        $sum = 0;
        for ($i = 5; $i >= 1; $i--) {
            $n = (int) ($counts[$i] ?? 0);
            $dist[$i] = $n;
            $total += $n;
            $sum += $i * $n;
        }

        return [
            'product' => $p,
            'stock'   => $this->stockMap[$p->id] ?? 0,
            'avg'     => $total ? round($sum / $total, 1) : 0,
            'total'   => $total,
            'dist'    => $dist,
            'reviews' => $base()->with('user:id,name')->latest()->limit(3)->get(),
        ];
    }

    protected function visibleReviews(): \Closure
    {
        return fn ($q) => $q->withoutGlobalScope('branch')->where('is_visible', true);
    }

    /* ================================================================
     *  HELPERS
     * ================================================================ */

    protected function notify(string $type, string $message): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    protected function persistCart(): void
    {
        if (Auth::check()) {
            session()->put('cart_' . ($this->branchId ?? 0), $this->cart);
        }
        unset($this->cartItems, $this->cartTotal, $this->cartCount);
    }

    protected function syncCart(): bool
    {
        if (empty($this->cart)) return false;

        $before = $this->cart;
        $ids    = array_map('intval', array_keys($this->cart));
        $valid  = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $clean = [];
        foreach ($this->cart as $id => $qty) {
            $id  = (int) $id;
            $qty = is_array($qty) ? (int) ($qty['qty'] ?? 0) : (int) $qty;

            if (! in_array($id, $valid, true)) continue;
            $qty = min($qty, $this->stockMap[$id] ?? 0);
            if ($qty > 0) $clean[$id] = $qty;
        }

        $this->cart = $clean;
        $this->persistCart();

        return $clean !== $before;
    }

    /* ================================================================
     *  WIZARD
     * ================================================================ */

    public function setCategory($categoryId): void
    {
        $this->activeCategory = is_numeric($categoryId) ? (int) $categoryId : 'all';
    }

    public function nextStep(): void
    {
        if ($this->step < 3 && empty($this->cart)) {
            $this->notify('error', 'Add at least one item to continue.');
            return;
        }

        if ($this->step === 2 && $this->syncCart()) {
            $this->notify('error', 'Some items in your cart changed. Please review them.');
            if (empty($this->cart)) $this->step = 1;
            return;
        }

        if ($this->step < $this->totalSteps) $this->step++;
    }

    public function previousStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step < $this->step) $this->step = $step;
    }

    /* ================================================================
     *  CART
     * ================================================================ */

    protected function addQty(int $productId, int $qty = 1): bool
    {
        if (! Auth::check()) {
            session()->flash('error', 'Please sign in to order.');
            $this->redirectRoute('login');
            return false;
        }

        $product = Product::forLocation($this->tenantId, $this->branchId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->find($productId);

        if (! $product) {
            $this->notify('error', 'That item is no longer available.');
            return false;
        }

        $available = $this->stockMap[$productId] ?? 0;
        $current   = (int) ($this->cart[$productId] ?? 0);
        $qty       = max(1, $qty);

        if ($available <= 0) {
            $this->notify('error', "{$product->name} is sold out.");
            return false;
        }
        if ($current + $qty > $available) {
            $this->notify('error', "Only {$available} available for {$product->name}.");
            return false;
        }

        $this->cart[$productId] = $current + $qty;
        $this->persistCart();
        $this->notify('success', $qty > 1 ? "{$product->name} × {$qty} added to cart." : "{$product->name} added to cart.");

        return true;
    }

    public function addToCart($productId): void
    {
        $this->addQty((int) $productId, 1);
    }

    public function removeFromCart($productId): void
    {
        unset($this->cart[(int) $productId]);
        $this->persistCart();

        if (empty($this->cart) && $this->step > 1) $this->step = 1;
    }

    public function updateQuantity($productId, $newQty): void
    {
        $productId = (int) $productId;
        $newQty    = (int) $newQty;

        if (! isset($this->cart[$productId])) return;
        if ($newQty <= 0) {
            $this->removeFromCart($productId);
            return;
        }

        $available = $this->stockMap[$productId] ?? 0;
        if ($newQty > $available) {
            $this->notify('error', "Only {$available} available.");
            return;
        }

        $this->cart[$productId] = $newQty;
        $this->persistCart();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        session()->forget('cart_' . ($this->branchId ?? 0));
        $this->persistCart();
        $this->step = 1;
    }

    /* ================================================================
     *  PRODUCT DETAIL
     * ================================================================ */

    public function openProductDetail($productId): void
    {
        $this->detailProductId = (int) $productId;
        $this->detailQty       = 1;
    }

    public function closeProductDetail(): void
    {
        $this->detailProductId = null;
        $this->detailQty       = 1;
    }

    public function incrementDetailQty(): void
    {
        $stock = (int) ($this->detail['stock'] ?? 1);
        $left  = max(1, $stock - (int) ($this->cart[$this->detailProductId] ?? 0));
        $this->detailQty = min($left, $this->detailQty + 1);
    }

    public function decrementDetailQty(): void
    {
        $this->detailQty = max(1, $this->detailQty - 1);
    }

    public function addToCartFromDetail(): void
    {
        if ($this->detailProductId && $this->addQty($this->detailProductId, $this->detailQty)) {
            $this->closeProductDetail();
        }
    }

    /* ================================================================
     *  PLACE ORDER
     * ================================================================ */

    public function placeOrder()
    {
        if (! Auth::check()) {
            return $this->redirectRoute('login');
        }

        if (! in_array($this->orderType, ['pickup', 'dine_in'], true)) {
            $this->orderType = 'pickup';
        }

        if (! in_array($this->paymentMethod, array_merge(['cash'], $this->onlinePaymentMethods), true)) {
            $this->notify('error', 'Invalid payment method.');
            return;
        }

        $this->syncCart();
        if (empty($this->cart)) {
            $this->notify('error', 'Your cart is empty.');
            $this->step = 1;
            return;
        }

        $isOnline = in_array($this->paymentMethod, $this->onlinePaymentMethods, true);

        try {
            $order = DB::transaction(function () {
                $ids = array_map('intval', array_keys($this->cart));
                sort($ids);

                $products = Product::forLocation($this->tenantId, $this->branchId)
                    ->where('type', 'finished')
                    ->where('is_active', true)
                    ->whereIn('id', $ids)
                    ->with('recipe.ingredient')
                    ->get()
                    ->keyBy('id');

                $lines = [];
                $total = 0;
                $availableByProduct = StockService::availableQuantities(
                    $products->values(),
                    $this->branchId,
                    $this->tenantId
                );

                foreach ($ids as $id) {
                    $qty = (int) ($this->cart[$id] ?? 0);
                    $p   = $products->get($id);

                    if (! $p || $qty < 1) {
                        throw new \RuntimeException('An item in your cart is no longer available.');
                    }

                    $available = $availableByProduct[$id] ?? 0;
                    if ($available < $qty) {
                        throw new \RuntimeException("{$p->name} only has {$available} left.");
                    }

                    $price   = (float) $p->price;
                    $total  += $price * $qty;
                    $lines[] = ['id' => $id, 'qty' => $qty, 'price' => $price];
                }

                $order = Order::create([
                    'order_number'    => 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                    'tenant_id'       => $this->tenantId,
                    'branch_id'       => $this->branchId,   // NULL = main store
                    'user_id'         => Auth::id(),
                    'total_amount'    => $total,
                    'payment_method'  => $this->paymentMethod,
                    'payment_status'  => 'pending',
                    'status'          => 'pending',
                    'notes'           => 'Type: ' . ucfirst(str_replace('_', ' ', $this->orderType)),
                    'is_reward_order' => false,
                ]);

                $stockToDeduct = [];
                foreach ($lines as $line) {
                    OrderItem::create([
                        'order_id'       => $order->id,
                        'branch_id'      => $this->branchId,
                        'product_id'     => $line['id'],
                        'quantity'       => $line['qty'],
                        'price'          => $line['price'],
                        'subtotal'       => $line['price'] * $line['qty'],
                        'is_reward_item' => false,
                    ]);

                    $stockToDeduct[$line['id']] = $line['qty'];
                }

                StockService::deductItems(
                    $stockToDeduct,
                    $this->branchId,
                    $this->tenantId,
                    $order->order_number,
                    Auth::id()
                );

                return $order;
            });
        } catch (\RuntimeException $e) {
            $this->notify('error', $e->getMessage());
            $this->syncCart();
            return;
        } catch (\Throwable $e) {
            report($e);
            $this->notify('error', 'Something went wrong while placing your order. Please try again.');
            return;
        }

        $this->cart = [];
        session()->forget('cart_' . ($this->branchId ?? 0));
        $this->step = 1;

        if ($isOnline) {
            session()->put('pending_order_id', $order->id);
            return $this->redirectRoute('payment.checkout', ['order' => $order->id]);
        }

        session()->flash('success', "Order #{$order->order_number} placed. Show this number to the cashier.");
        return $this->redirectRoute('customer.orders');
    }
};
?>
