<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TenantRegisterController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ApprovalController;
use App\Http\Controllers\PaymentController;
use App\Http\Requests\CheckEmailRequest;
use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------------
// Password Reset (guest only)
// ------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])
        ->name('password.request');
    Route::post('/forgot-password/send-code', [PasswordResetController::class, 'sendResetCode'])
        ->name('password.send-code');
    Route::post('/forgot-password', [PasswordResetController::class, 'resetPassword'])
        ->name('password.reset');
});

// ------------------------------------------------------------------
// Email Verification
// ------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [EmailVerificationController::class, 'show'])
        ->name('verification.notice');
    Route::post('/verify-email', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');
    Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->name('verification.resend');
});

// ------------------------------------------------------------------
// Public & Authentication
// ------------------------------------------------------------------
Route::livewire('/', 'pages::public.landing')->name('public.landing');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Registration — Owner / Tenant
Route::get('/register/Tenant', [TenantRegisterController::class, 'showForm'])->name('register.owner.form');
Route::get('/register/Tenant/fresh', [TenantRegisterController::class, 'startOver'])->name('register.owner.fresh');
Route::post('/register/Tenant/send-code', [TenantRegisterController::class, 'sendCode'])->name('register.owner.send-code');
Route::post('/register/Tenant', [TenantRegisterController::class, 'registerOwner'])->name('register.owner');

// Registration — Customer
Route::get('/register/customer', [RegisterController::class, 'showForm'])->name('register.customer.form');
Route::get('/register/customer/fresh', [RegisterController::class, 'startOver'])->name('register.customer.fresh');
Route::post('/register/customer/send-code', [RegisterController::class, 'sendCode'])->name('register.customer.send-code');
Route::post('/register/customer', [RegisterController::class, 'registerCustomer'])->name('register.customer');

// Real-time email uniqueness check
Route::post('/register/check-email', function (CheckEmailRequest $request) {
    $email = $request->validated('email');

    $takenInTenants = Tenant::where('email', $email)->exists();
    $takenInUsers   = \App\Models\User::where('email', $email)->exists();

    return response()->json([
        'available' => ! ($takenInTenants || $takenInUsers),
    ]);
})->middleware('throttle:email-availability')->name('register.check-email');

// Combined pending page
Route::get('/pending/{type}', function ($type) {
    $user = auth()->user();
    $tenant = null;
    $branch = null;

    if ($type === 'tenant' && $user && $user->tenant_id) {
        $tenant = Tenant::find($user->tenant_id);
    }

    if ($type === 'branch' && $user && $user->tenant_id) {
        $branch = Branch::where('tenant_id', $user->tenant_id)
            ->whereIn('approval_status', ['pending', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    return view('pages.auth.approval-pending.approval-pending', compact('type', 'tenant', 'branch'));
})->name('approval-pending')->middleware('auth')->where('type', 'tenant|branch');

Route::get('/pending/{type}/check-status', [ApprovalController::class, 'checkStatus'])
    ->name('pending.check-status')
    ->middleware('auth')
    ->where('type', 'tenant|branch');

// ------------------------------------------------------------------
// PUBLIC CUSTOMER ROUTES
// ------------------------------------------------------------------
Route::livewire('/shop-list', 'pages::customer.shops-list')->name('customer.shoplist');

// Shop detail — component lives in ⚡shop-detail/shop-detail.blade.php
Route::livewire('/customer/shop/{tenant}', 'pages::customer.shop-detail')
    ->name('customer.shopdetail');

// Shop menu — takes BOTH tenant + branch
//   /customer/shop/menu/2/0  → tenant 2's main store  (branch_id = NULL)
//   /customer/shop/menu/2/5  → tenant 2, sub-branch 5
Route::livewire('/customer/shop/menu/{tenant}/{branch}', 'pages::customer.shops-menu')
    ->name('customer.shopmenu')
    ->where('branch', '[0-9]+');

// ------------------------------------------------------------------
// Super Admin Routes
// ------------------------------------------------------------------
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('/analytics', 'pages::admin.analytics')->name('admin.analytics');
    Route::livewire('/tenants', 'pages::admin.tenants')->name('admin.tenants');
    Route::livewire('/manage-tenant', 'pages::admin.manage-tenant')->name('admin.manage-tenant');
    Route::livewire('/manage-customer', 'pages::admin.managecustomer')->name('admin.manage-customer');
    Route::livewire('/theme-settings', 'pages::admin.theme-settings')->name('admin.theme-settings');
    Route::livewire('/announcements', 'pages::admin.announcements')->name('admin.announcements');

    Route::get('/tenant-approvals', [ApprovalController::class, 'tenantIndex'])
        ->name('admin.tenant-approvals');
    Route::post('/tenant-approvals/{tenant}/approve', [ApprovalController::class, 'approveTenant'])
        ->name('admin.tenant-approvals.approve');
    Route::get('/tenant-approvals/{tenant}/reject', [ApprovalController::class, 'showTenantRejectForm'])
        ->name('admin.tenant-approvals.reject-form');
    Route::post('/tenant-approvals/{tenant}/reject', [ApprovalController::class, 'rejectTenant'])
        ->name('admin.tenant-approvals.reject');
    Route::post('/tenant-approvals/bulk-approve', [ApprovalController::class, 'bulkApproveTenants'])
        ->name('admin.tenant-approvals.bulk-approve');

    Route::get('/branch-approvals', [ApprovalController::class, 'branchIndex'])
        ->name('admin.branch-approvals');
    Route::post('/branch-approvals/{branch}/approve', [ApprovalController::class, 'approveBranch'])
        ->name('admin.branch-approvals.approve');
    Route::get('/branch-approvals/{branch}/reject', [ApprovalController::class, 'showBranchRejectForm'])
        ->name('admin.branch-approvals.reject-form');
    Route::post('/branch-approvals/{branch}/reject', [ApprovalController::class, 'rejectBranch'])
        ->name('admin.branch-approvals.reject');
    Route::post('/branch-approvals/bulk-approve', [ApprovalController::class, 'bulkApproveBranches'])
        ->name('admin.branch-approvals.bulk-approve');
});

// ------------------------------------------------------------------
// Owner Routes (Tenant)
// ------------------------------------------------------------------
Route::prefix('owner')->middleware(['auth', 'owner'])->group(function () {
    Route::livewire('/', 'pages::shop_owner.dashboard')->name('owner.dashboard');

    // ── Catalog ──────────────────────────────────────────
    Route::livewire('/products', 'pages::shop_owner.products')->name('owner.products');
    Route::redirect('/add-products', '/owner/products')->name('owner.add-products');

    // ── Inventory ────────────────────────────────────────
    Route::livewire('/inventory', 'pages::shop_owner.inventory')->name('owner.inventory');
    Route::livewire('/inventories', 'pages::shop_owner.inventory')->name('owner.inventories');
    Route::redirect('/add-stocks', '/owner/inventory')->name('owner.add-stocks');

    // Sales are handled by cashier accounts, not tenant-owner accounts.
    Route::redirect('/cashier', '/owner')->name('owner.cashier');

    // ── Financial & Performance ──────────────────────────
    Route::livewire('/payments', 'pages::shop_owner.payments')->name('owner.payments');
    Route::livewire('/ratings', 'pages::shop_owner.ratings')->name('owner.ratings');
    Route::livewire('/analytics', 'pages::shop_owner.analytics')->name('owner.analytics');

    // ── Staff & Branch Management ────────────────────────
    Route::livewire('/staff', 'pages::shop_owner.staff')->name('owner.staff');
    Route::livewire('/branches', 'pages::shop_owner.branches')->name('owner.branches');
    Route::livewire('/branch-dashboard', 'pages::shop_owner.branch-dashboard')->name('owner.branch-dashboard');
    Route::livewire('/branch-staff', 'pages::shop_owner.branch-staff')->name('owner.branch-staff');
    Route::livewire('/branch-manage', 'pages::shop_owner.branch-manage')->name('owner.branch-manage');
    Route::livewire('/branch-analytics', 'pages::shop_owner.branch-analytics')->name('owner.branch-analytics');

    // ── Settings & Loyalty ───────────────────────────────
    Route::livewire('/settings', 'pages::shop_owner.settings')->name('owner.settings');
    Route::livewire('/loyalty', 'pages::shop_owner.loyalty-settings')->name('owner.loyalty');
});

// ------------------------------------------------------------------
// TENANT-LEVEL STAFF ROUTES
// For main-store staff (branch_id = null)
// ------------------------------------------------------------------
Route::prefix('tenant')->middleware(['auth', 'tenant.barista'])->group(function () {
    Route::livewire('/barista', 'pages::shop_owner.barista')->name('tenant.barista');
});

// ------------------------------------------------------------------
// Branch Routes
// ------------------------------------------------------------------
Route::prefix('branch')->middleware(['auth', 'branch.access'])->group(function () {
    Route::middleware(['branch.role:branch_manager'])->group(function () {
        Route::livewire('/dashboard', 'pages::branch.branch-dashboard')->name('branch.dashboard');
        Route::livewire('/operations', 'pages::branch.branch-operations')->name('branch.operations');
        Route::livewire('/product', 'pages::branch.branch-product')->name('branch.product');
        Route::livewire('/inventory', 'pages::branch.branch-inventory')->name('branch.inventory');
        Route::livewire('/staff', 'pages::branch.branch-staff')->name('branch.staff');
        Route::livewire('/ratings', 'pages::branch.branch-rating')->name('branch.rating');
        Route::livewire('/analytics', 'pages::branch.branch-analytics')->name('branch.analytics');
        Route::livewire('/settings', 'pages::branch.settings')->name('branch.settings');
    });

    Route::middleware(['branch.role:cashier'])->group(function () {
        Route::livewire('/cashier', 'pages::shop_owner.cashier')->name('branch.cashier');
    });

    Route::middleware(['branch.role:barista'])->group(function () {
        Route::livewire('/barista', 'pages::branch.barista-dashboard')->name('branch.barista');
    });
});

// ------------------------------------------------------------------
// Cashier & Customer Caller
// ------------------------------------------------------------------
Route::prefix('cashier')->middleware(['auth', 'cashier'])->group(function () {
    Route::livewire('/', 'pages::shop_owner.cashier')->name('cashier.dashboard');
});

Route::redirect('/caller', '/shop-list')->name('customer.caller');

// Shared notification inbox for every authenticated role.
Route::livewire('/notifications', 'pages::notifications.index')
    ->middleware('auth')
    ->name('notifications.index');

// ------------------------------------------------------------------
// AUTHENTICATED CUSTOMER ROUTES
// ------------------------------------------------------------------
Route::prefix('customer')->middleware(['auth', 'customer'])->group(function () {
    Route::livewire('/dashboard', 'pages::customer.dashboard')->name('customer.dashboard');
    Route::livewire('/orders', 'pages::customer.orders')->name('customer.orders');
    Route::livewire('/ratings', 'pages::customer.reviews')->name('customer.reviews');
    Route::livewire('/rewards', 'pages::customer.rewards')->name('customer.rewards');
    Route::redirect('/notification', '/notifications')->name('customer.notifications');
});

// ------------------------------------------------------------------
// Payment Routes
// ------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/payment/checkout/{order}', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel/{order}', [PaymentController::class, 'cancel'])->name('payment.cancel');
});
