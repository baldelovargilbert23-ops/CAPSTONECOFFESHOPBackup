<?php
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Support\UnitConverter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
?>

﻿
<div class="inv-theme relative min-h-screen font-sans transition-colors duration-300" wire:poll.30s.visible>

    <style>
        .inv-theme {
            --bg-primary: #f5f0e8; --bg-elevated: #ffffff; --bg-inner: #f5f0e8;
            --text-primary: #1a3a3a; --text-muted: #6b7f7a;
            --accent: #c8965f; --accent-bright: #b8874a; --forest: #1a3a3a;
            --border-soft: rgba(26, 58, 58, 0.10);
            --ok: #16a34a; --warn: #d97706; --bad: #dc2626; --info: #2563eb; --violet: #7c3aed;
        }
        .dark .inv-theme {
            --bg-primary: #0a1f1a; --bg-elevated: #14332c; --bg-inner: #0a1f1a;
            --text-primary: #f0ebe0; --text-muted: #8a9a94;
            --accent: #d4a574; --accent-bright: #c8965f; --forest: #f0ebe0;
            --border-soft: rgba(212, 165, 116, 0.12);
            --ok: #4ade80; --warn: #fbbf24; --bad: #f87171; --info: #60a5fa; --violet: #a78bfa;
        }
        .inv-theme { background-color: var(--bg-primary); color: var(--text-primary); }
        .inv-theme .card  { background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; }
        .inv-theme .inner { background-color: var(--bg-inner); border: 1px solid var(--border-soft); border-radius: 0.75rem; }
        .inv-theme .muted { color: var(--text-muted); }
        .inv-theme .input-theme { background-color: var(--bg-inner); color: var(--text-primary); border: 1px solid var(--border-soft); outline: none; }
        .inv-theme .input-theme:focus { border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent); }
        .inv-theme .input-theme:disabled { opacity: .6; cursor: not-allowed; }
        .inv-theme .btn-accent { background-image: linear-gradient(135deg, color-mix(in srgb, var(--accent) 95%, white 5%), color-mix(in srgb, var(--accent-bright) 90%, black 10%)); color: #fff; }
        .inv-theme button:disabled { opacity: .45; cursor: not-allowed; }
        .inv-theme .pill { border-radius: .375rem; font-weight: 700; }
        .inv-theme .pill-ok     { background: color-mix(in srgb, var(--ok) 12%, transparent);   color: var(--ok);   border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); }
        .inv-theme .pill-low    { background: color-mix(in srgb, var(--warn) 12%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent); }
        .inv-theme .pill-out    { background: color-mix(in srgb, var(--bad) 12%, transparent);  color: var(--bad);  border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); }
        .inv-theme .pill-info   { background: color-mix(in srgb, var(--info) 12%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent); }
        .inv-theme .pill-violet { background: color-mix(in srgb, var(--violet) 12%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent); }
        .inv-theme .pill-accent { background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent); }
        .inv-theme .lbl { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color: var(--text-muted); margin-bottom:.25rem; }
        .inv-theme .err { font-size: 10px; color: var(--bad); display:block; margin-top:.2rem; }
    </style>

    <?php
        $fmt   = fn ($q) => \App\Support\UnitConverter::format((float) $q);
        $tone  = ['ok' => 'var(--ok)', 'low' => 'var(--warn)', 'out' => 'var(--bad)'];
        $words = ['ok' => 'OK', 'low' => 'LOW', 'out' => 'OUT'];
        $stats = $this->stats;
        $list  = $this->products;

        $adjustable = $this->allProducts->where('is_derived_stock', false);
        $selected   = $product_id ? $this->allProducts->firstWhere('id', (int) $product_id) : null;
        $adjUnits   = $selected
            ? \App\Support\UnitConverter::compatibleUnits($selected->stock_unit)
            : array_keys(\App\Support\UnitConverter::UNITS);
        $hasFilters = $search !== '' || $categoryFilter || $stockFilter !== 'all' || $typeFilter !== 'all';
        $visibleCats = $this->categories->when($typeFilter !== 'all', fn ($c) => $c->where('type', $typeFilter));
    ?>

    <main class="relative z-10 p-3 sm:p-4 md:p-5 max-w-7xl mx-auto space-y-3.5">

        
        <div class="card p-3 sm:p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa-solid fa-boxes-stacked text-sm"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black tracking-tight">Inventory Control</h1>
                        <span class="pill pill-accent inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span>
                            <?php echo e($stats['total']); ?> ITEMS
                        </span>
                    </div>
                    <p class="text-xs font-mono truncate muted"><?php echo e($branch->name ?? 'Branch'); ?> • Live Stock</p>
                </div>
            </div>

            <div class="relative w-full sm:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs muted"></i>
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="Search name, SKU or category…"
                       class="input-theme w-full pl-8 pr-3 py-2 rounded-xl text-xs">
            </div>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['success' => ['ok', 'fa-circle-check'], 'error' => ['bad', 'fa-circle-exclamation'], 'info' => ['info', 'fa-circle-info']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$c, $ic]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has($key)): ?>
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" role="status"
                     class="p-3 rounded-xl text-xs font-bold flex items-center justify-between gap-2"
                     style="background-color: color-mix(in srgb, var(--<?php echo e($c); ?>) 10%, transparent); border: 1px solid color-mix(in srgb, var(--<?php echo e($c); ?>) 30%, transparent); color: var(--<?php echo e($c); ?>);">
                    <span class="flex items-center gap-2"><i class="fa-solid <?php echo e($ic); ?>"></i> <?php echo e(session($key)); ?></span>
                    <button type="button" @click="show = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->stockRequests->isNotEmpty()): ?>
            <div class="card p-3 sm:p-4 shadow-sm" style="border-color: color-mix(in srgb, var(--bad) 30%, transparent);">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center pill-out"><i class="fa-solid fa-box-open text-sm"></i></div>
                        <div>
                            <h3 class="text-xs font-mono font-bold uppercase tracking-wider">Stock Requests from Cashiers</h3>
                            <p class="text-[10px] font-mono muted"><?php echo e($this->stockRequests->count()); ?> pending</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->stockRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $elapsed = (int) $req->created_at->diffInMinutes(now(), true);
                            $urgency = $elapsed >= 60 ? 'var(--bad)' : ($elapsed >= 15 ? 'var(--warn)' : 'var(--ok)');
                            $note = '';
                            if ($req->notes && preg_match('/\|\s*Note:\s*(.+?)(?:\n|$)/i', $req->notes, $m)) $note = trim($m[1]);
                            $totalLines = $req->items->count();
                        ?>
                        <div class="inner p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'req-'.e($req->id).''; ?>wire:key="req-<?php echo e($req->id); ?>">
                            <div class="min-w-0 flex-1">
                                <div class="space-y-1">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $req->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <?php $pUnit = ($item->product?->type === 'ingredient') ? ($item->product->unit ?: 'g') : 'pcs'; ?>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-bold text-xs truncate"><?php echo e($item->product->name ?? 'Unknown product'); ?></p>
                                            <span class="pill pill-out px-1.5 py-0.5 text-[10px] font-mono">+<?php echo e($fmt($item->quantity)); ?> <?php echo e($pUnit); ?></span>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-[10px] font-mono muted flex-wrap">
                                    <span><i class="fa-solid fa-user-tie text-[9px] mr-1"></i><?php echo e($req->user->name ?? 'Cashier'); ?></span>
                                    <span><i class="fa-solid fa-clock text-[9px] mr-1"></i><?php echo e($req->created_at->format('h:i A')); ?></span>
                                    <span class="font-bold" style="color: <?php echo e($urgency); ?>;"><?php echo e($elapsed >= 60 ? intdiv($elapsed, 60) . 'h ' . ($elapsed % 60) . 'm waiting' : $elapsed . 'm waiting'); ?></span>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($note): ?><p class="text-[10px] italic mt-1 truncate muted">"<?php echo e($note); ?>"</p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" wire:click="approveStockRequest(<?php echo e($req->id); ?>)"
                                        wire:loading.attr="disabled" wire:target="approveStockRequest(<?php echo e($req->id); ?>)"
                                        wire:confirm="Approve this request and add <?php echo e($totalLines); ?> item(s) to inventory?"
                                        class="px-3 py-1.5 rounded-lg text-[11px] font-mono font-bold text-white"
                                        style="background-color: var(--ok);">
                                    <i class="fa-solid fa-check text-[10px] mr-1"></i> Approve
                                </button>
                                <button type="button" wire:click="rejectStockRequest(<?php echo e($req->id); ?>)"
                                        wire:loading.attr="disabled" wire:target="rejectStockRequest(<?php echo e($req->id); ?>)"
                                        wire:confirm="Reject this stock request?"
                                        class="px-3 py-1.5 rounded-lg text-[11px] font-mono font-bold muted"
                                        style="border: 1px solid var(--border-soft);">
                                    <i class="fa-solid fa-xmark text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['out'] > 0 || $stats['low'] > 0): ?>
            <div class="p-3 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 pill-low">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <p class="text-xs font-bold">
                        Stock alert:
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['out'] > 0): ?> <?php echo e($stats['out']); ?> out <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['out'] > 0 && $stats['low'] > 0): ?> · <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['low'] > 0): ?> <?php echo e($stats['low']); ?> running low <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                </div>
                <button type="button" wire:click="setStockFilter('<?php echo e($stats['out'] > 0 ? 'out' : 'low'); ?>')"
                        class="px-3 py-1.5 rounded-xl text-white text-[11px] font-mono font-bold whitespace-nowrap" style="background-color: var(--warn);">
                    <i class="fa-solid fa-eye text-[10px] mr-1"></i> View <?php echo e($stats['out'] > 0 ? 'out of stock' : 'low stock'); ?>

                </button>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <div class="card p-3 sm:p-4 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <i class="fa-solid fa-arrows-rotate text-xs" style="color: var(--accent);"></i>
                <h3 class="text-xs font-mono font-bold uppercase tracking-wider">Stock Adjustment</h3>
            </div>

            <form wire:submit.prevent="updateStock" class="grid grid-cols-2 sm:grid-cols-12 gap-2 items-start">
                <div class="col-span-2 sm:col-span-4">
                    <select wire:model.live="product_id" class="input-theme w-full px-3 py-2 rounded-xl text-xs">
                        <option value="">Select item…</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $adjustable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?> — <?php echo e($fmt($p->stock)); ?> <?php echo e($p->stock_unit); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err font-mono"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="col-span-2 sm:col-span-2">
                    <select wire:model="adjustment_type" class="input-theme w-full px-3 py-2 rounded-xl text-xs font-mono font-bold">
                        <option value="add">+ Add</option>
                        <option value="subtract">− Remove</option>
                    </select>
                </div>

                <div class="col-span-1 sm:col-span-2">
                    <input type="number" step="any" min="0" wire:model="quantity_to_add" placeholder="Qty"
                           class="input-theme w-full px-3 py-2 rounded-xl text-xs font-mono">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['quantity_to_add'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err font-mono"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="col-span-1 sm:col-span-2">
                    <select wire:model="adjust_unit" class="input-theme w-full px-3 py-2 rounded-xl text-xs font-mono">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $adjUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($u); ?>"><?php echo e($u); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['adjust_unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err font-mono"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="col-span-2 sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateStock"
                            class="w-full py-2 rounded-xl text-xs font-mono font-bold flex items-center justify-center gap-1"
                            style="background-color: var(--forest); color: var(--bg-primary);">
                        <i class="fa-solid fa-check text-[10px]"></i> Apply
                    </button>
                </div>
            </form>

            <p class="text-[10px] muted mt-2">
                You can enter any compatible unit (e.g. add <strong>2 kg</strong> to an item stocked in g — it's converted for you).
                Products made from a recipe aren't listed: their stock is calculated from ingredients.
            </p>
        </div>

        
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [
                ['Catalog', $stats['total'], 'var(--accent)', 'fa-cubes'],
                ['In Stock', $stats['in_stock'], 'var(--ok)', 'fa-circle-check'],
                ['Low', $stats['low'], 'var(--warn)', 'fa-triangle-exclamation'],
                ['Out', $stats['out'], 'var(--bad)', 'fa-circle-xmark'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$kl, $kv, $kc, $ki]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="card p-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider" style="color: <?php echo e($kc); ?>;"><?php echo e($kl); ?></span>
                        <div class="text-lg font-black font-mono" style="color: <?php echo e($kc); ?>;"><?php echo e($kv); ?></div>
                    </div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs"
                         style="background-color: color-mix(in srgb, <?php echo e($kc); ?> 10%, transparent); color: <?php echo e($kc); ?>;"><i class="fa-solid <?php echo e($ki); ?>"></i></div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

            <div class="p-3 rounded-2xl col-span-2 lg:col-span-1 flex items-center justify-between shadow-sm"
                 style="background-color: var(--forest); color: var(--bg-primary);">
                <div class="min-w-0">
                    <span class="text-[10px] font-mono font-bold uppercase tracking-wider opacity-80">Stock Value (at cost)</span>
                    <div class="text-base font-black font-mono truncate">₱<?php echo e(number_format($stats['value'], 2)); ?></div>
                </div>
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>

        
        <div class="card overflow-hidden">
            <div class="p-3 flex flex-wrap items-center justify-between gap-2 inner" style="border-radius: 0;">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['all' => 'All', 'finished' => 'Products (' . $stats['fin'] . ')', 'ingredient' => 'Ingredients (' . $stats['ing'] . ')']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <button type="button" wire:click="setTypeFilter('<?php echo e($k); ?>')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold"
                                style="<?php echo e($typeFilter === $k ? 'background-color: var(--forest); color: var(--bg-primary);' : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);'); ?>"><?php echo e($l); ?></button>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <span class="mx-1 muted">|</span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['all' => 'Any stock', 'in_stock' => 'In stock', 'low' => 'Low', 'out' => 'Out']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <button type="button" wire:click="setStockFilter('<?php echo e($k); ?>')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold"
                                style="<?php echo e($stockFilter === $k ? 'background-color: var(--accent); color: #fff;' : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);'); ?>"><?php echo e($l); ?></button>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model.live="categoryFilter" class="input-theme px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold">
                        <option value="">All categories</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $visibleCats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($cat->id); ?>"><?php echo e($cat->cat_name); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                    <select wire:model.live="sortBy" class="input-theme px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold">
                        <option value="name">Name A→Z</option>
                        <option value="stock_asc">Stock ↑ (lowest first)</option>
                        <option value="stock_desc">Stock ↓</option>
                        <option value="price_asc">Price ↑</option>
                        <option value="price_desc">Price ↓</option>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasFilters): ?>
                        <button type="button" wire:click="clearFilters" class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold inner">
                            <i class="fa-solid fa-rotate-left text-[10px]"></i> Clear
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="font-mono text-[10px] uppercase tracking-wider"
                           style="background-color: var(--bg-inner); color: var(--text-muted); border-bottom: 1px solid var(--border-soft);">
                        <tr>
                            <th class="px-4 py-2.5">Item & SKU</th>
                            <th class="px-4 py-2.5">Category</th>
                            <th class="px-4 py-2.5">Price / Cost</th>
                            <th class="px-4 py-2.5">Stock</th>
                            <th class="px-4 py-2.5">Quick adjust</th>
                            <th class="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $unit = $product->stock_unit;
                                $step = in_array($unit, ['g', 'ml'], true) ? 100 : 1;
                            ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'inv-'.e($product->id).''; ?>wire:key="inv-<?php echo e($product->id); ?>">
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-lg flex-shrink-0 overflow-hidden flex items-center justify-center inner">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->image): ?>
                                                <img src="<?php echo e(Storage::url($product->image)); ?>" alt="" loading="lazy" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fa-solid <?php echo e($product->is_ingredient ? 'fa-wheat-awn' : 'fa-mug-hot'); ?> text-xs" style="color: var(--accent);"></i>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-xs truncate max-w-[200px]"><?php echo e($product->name); ?></div>
                                            <div class="text-[10px] font-mono truncate muted">
                                                <?php echo e($product->is_ingredient ? 'Ingredient' : ($product->is_derived_stock ? 'Recipe product' : 'Ready-made')); ?> • <?php echo e($product->sku ?? 'N/A'); ?>

                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="pill <?php echo e($product->is_ingredient ? 'pill-violet' : 'pill-accent'); ?> inline-flex px-2 py-0.5 text-[10px] font-mono">
                                        <?php echo e($product->category->cat_name ?? 'General'); ?>

                                    </span>
                                </td>

                                <td class="px-4 py-2.5 font-mono">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_ingredient): ?>
                                        <div class="font-bold">₱<?php echo e(number_format((float) $product->cost, 2)); ?><span class="muted">/<?php echo e($unit); ?></span></div>
                                    <?php else: ?>
                                        <div class="font-bold">₱<?php echo e(number_format((float) $product->price, 2)); ?></div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->unit_cost > 0): ?>
                                            <div class="text-[10px]" style="color: <?php echo e($product->margin >= 0 ? 'var(--ok)' : 'var(--bad)'); ?>;">
                                                cost ₱<?php echo e(number_format($product->unit_cost, 2)); ?> · <?php echo e($product->margin_pct); ?>%
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="pill pill-<?php echo e($product->state); ?> inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono"
                                          <?php if($product->is_derived_stock): ?> title="Calculated from ingredient stock" <?php endif; ?>>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_derived_stock): ?><i class="fa-solid fa-flask text-[9px]"></i><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php echo e($fmt($product->display_stock)); ?><?php echo e($product->is_derived_stock ? ' can make' : ' ' . $unit); ?>

                                        · <?php echo e($words[$product->state]); ?>

                                    </span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_ingredient && $product->stock > 0): ?>
                                        <div class="text-[10px] font-mono muted mt-0.5">= <?php echo e(\App\Support\UnitConverter::baseLabel($product->stock, $unit)); ?></div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_derived_stock && $product->limiting && $product->display_stock <= 20): ?>
                                        <div class="text-[10px] muted mt-0.5">limited by <?php echo e($product->limiting); ?></div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->recipe_issue): ?>
                                        <div class="text-[10px] font-bold mt-0.5" style="color: var(--bad);">Unit mismatch in recipe</div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <div class="text-[10px] font-mono muted mt-0.5">alert ≤ <?php echo e($fmt($product->min_stock)); ?></div>
                                </td>

                                <td class="px-4 py-2.5">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_derived_stock): ?>
                                        <span class="text-[10px] muted">Auto (recipe)</span>
                                    <?php else: ?>
                                        <div class="inline-flex items-center rounded-lg overflow-hidden" style="border: 1px solid var(--border-soft);">
                                            <button type="button" wire:click="quickAdjust(<?php echo e($product->id); ?>, -1)" wire:loading.attr="disabled"
                                                    class="px-2 py-1 muted" title="Remove <?php echo e($step); ?> <?php echo e($unit); ?>">
                                                <i class="fa-solid fa-minus text-[9px]"></i>
                                            </button>
                                            <span class="px-2.5 py-1 font-mono font-bold text-[10px] min-w-[56px] text-center"
                                                  style="background-color: var(--bg-inner); border-left: 1px solid var(--border-soft); border-right: 1px solid var(--border-soft);">
                                                <?php echo e($step); ?> <?php echo e($unit); ?>

                                            </span>
                                            <button type="button" wire:click="quickAdjust(<?php echo e($product->id); ?>, 1)" wire:loading.attr="disabled"
                                                    class="px-2 py-1 muted" title="Add <?php echo e($step); ?> <?php echo e($unit); ?>">
                                                <i class="fa-solid fa-plus text-[9px]"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="editProduct(<?php echo e($product->id); ?>)" class="p-1.5 rounded-lg muted" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" wire:click="deleteProduct(<?php echo e($product->id); ?>)"
                                                wire:confirm="Remove '<?php echo e($product->name); ?>' from this branch? This cannot be undone."
                                                class="p-1.5 rounded-lg" style="color: var(--bad);" title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <i class="fa-solid fa-box-open text-xl muted mb-2"></i>
                                    <p class="text-xs font-mono muted"><?php echo e($hasFilters ? 'No items match your filters.' : 'No inventory items yet. Add items from the Branch Catalog.'); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasFilters): ?>
                                        <button type="button" wire:click="clearFilters" class="mt-3 px-3 py-1.5 rounded-lg text-[11px] font-mono font-bold btn-accent">Clear filters</button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-2.5 text-[10px] font-mono flex items-center justify-between muted" style="border-top: 1px solid var(--border-soft);">
                <span>Showing <?php echo e($list->count()); ?> of <?php echo e($stats['total']); ?> items</span>
                <span class="font-bold flex items-center gap-1" style="color: var(--ok);">
                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span> Auto-refreshing
                </span>
            </div>
        </div>
    </main>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showEditModal): ?>
        <?php $editBase = $edit_type === 'ingredient' ? \App\Support\UnitConverter::baseLabel((float) $edit_stock, $edit_unit) : null; ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 overflow-y-auto" role="dialog" aria-modal="true"
             wire:keydown.escape.window="closeEditModal">
            <div class="fixed inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="closeEditModal"></div>
            <div class="card w-full max-w-md shadow-2xl relative z-10 overflow-hidden">

                <div class="p-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-soft);">
                    <h3 class="text-sm font-bold flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square" style="color: var(--accent);"></i>
                        Edit <?php echo e($edit_type === 'ingredient' ? 'Ingredient' : 'Product'); ?>

                    </h3>
                    <button type="button" wire:click="closeEditModal" class="muted"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-4 space-y-3 text-xs max-h-[70vh] overflow-y-auto">
                    <div>
                        <label class="lbl">Name</label>
                        <input type="text" wire:model="edit_name" class="input-theme w-full px-3 py-2 rounded-xl">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div>
                        <label class="lbl">Category</label>
                        <select wire:model="edit_category_id" class="input-theme w-full px-3 py-2 rounded-xl">
                            <option value="">— Select —</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->editCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($category->id); ?>"><?php echo e($category->cat_name); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($edit_type === 'ingredient'): ?>
                        <div>
                            <label class="lbl">Cost per <?php echo e($edit_unit); ?> (₱)</label>
                            <input type="number" step="any" min="0" wire:model="edit_cost" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_cost'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="lbl">Selling price (₱)</label>
                                <input type="number" step="0.01" min="0" wire:model="edit_price" class="input-theme w-full px-3 py-2 rounded-xl font-mono font-bold">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <label class="lbl">Buying cost (₱)</label>
                                <input type="number" step="0.01" min="0" wire:model="edit_cost" <?php if($edit_has_recipe): echo 'disabled'; endif; ?>
                                       class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_cost'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($edit_has_recipe): ?>
                        <div class="p-3 rounded-xl text-[11px] pill-info">
                            <i class="fa-solid fa-flask"></i>
                            This product has a recipe, so its cost and stock are <strong>calculated from ingredients</strong>.
                            Edit the recipe in the Branch Catalog.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="lbl">Stock (<?php echo e($edit_unit); ?>)</label>
                                <input type="number" step="any" min="0" wire:model.live.debounce.300ms="edit_stock" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editBase): ?><p class="text-[10px] font-mono muted mt-1">= <?php echo e($editBase); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <label class="lbl">Low-stock alert (<?php echo e($edit_unit); ?>)</label>
                                <input type="number" step="any" min="0" wire:model="edit_min_stock" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_min_stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($edit_has_recipe): ?>
                        <div>
                            <label class="lbl">Low-stock alert (units you can make)</label>
                            <input type="number" step="1" min="0" wire:model="edit_min_stock" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['edit_min_stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="err"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($edit_type === 'ingredient'): ?>
                        <p class="text-[10px] muted"><i class="fa-solid fa-lock text-[9px]"></i> Unit (<?php echo e($edit_unit); ?>) is locked because stock and recipes depend on it.</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="p-3 flex gap-2" style="border-top: 1px solid var(--border-soft);">
                    <button type="button" wire:click="closeEditModal" class="flex-1 py-2.5 text-xs font-mono rounded-xl font-bold inner">Cancel</button>
                    <button type="button" wire:click="updateProduct" wire:loading.attr="disabled" wire:target="updateProduct"
                            class="flex-1 py-2.5 text-xs font-mono rounded-xl font-bold btn-accent">
                        <span wire:loading.remove wire:target="updateProduct">Save changes</span>
                        <span wire:loading wire:target="updateProduct">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\Capstone\backup\CAPSTONECOFFESHOPBackup\storage\framework\views/livewire/views/993412ad.blade.php ENDPATH**/ ?>