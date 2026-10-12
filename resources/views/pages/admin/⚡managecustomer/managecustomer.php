<?php

namespace App\Livewire\Pages\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

new #[Layout('layouts.admin')] class extends Component
{
    use WithPagination;

    // ================================================================
    // STATE
    // ================================================================
    public $search = '';
    public $targetUserId;
    public $targetUserName;

    // Manage modal
    public $showManageModal = false;
    public $editName = '';
    public $editEmail = '';
    public $editPassword = '';

    // ================================================================
    // SEARCH
    // ================================================================
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ================================================================
    // MODAL CONTROLS
    // ================================================================
    public function openManageModal($id)
    {
        $user = User::findOrFail($id);

        // ✅ Safety: only customers
        if ($user->role !== 'customer') {
            session()->flash('error', 'Only customer accounts can be managed here.');
            return;
        }

        $this->targetUserId    = $id;
        $this->targetUserName  = $user->name;
        $this->editName        = $user->name;
        $this->editEmail       = $user->email;
        $this->editPassword    = '';
        $this->showManageModal = true;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showManageModal = false;
        $this->resetValidation();
        $this->editPassword    = '';
        $this->targetUserId    = null;
        $this->targetUserName  = null;
    }

    // ================================================================
    // UPDATE CUSTOMER
    // ================================================================
    public function updateCustomer()
    {
        $this->validate([
            'editName' => 'required|string|min:2|max:255',
            'editEmail' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->targetUserId),
            ],
            'editPassword' => 'nullable|min:8',
        ]);

        $user = User::findOrFail($this->targetUserId);

        // ✅ Safety: only customers
        if ($user->role !== 'customer') {
            session()->flash('error', 'Only customer accounts can be updated here.');
            $this->closeModal();
            return;
        }

        $updateData = [
            'name'  => $this->editName,
            'email' => $this->editEmail,
        ];

        if (!empty($this->editPassword)) {
            $updateData['password'] = Hash::make($this->editPassword);
        }

        $user->update($updateData);

        $this->closeModal();
        session()->flash('success', "Customer account for {$user->name} has been updated successfully.");
    }

    // ================================================================
    // DELETE CUSTOMER — with cascading + transaction
    // ================================================================
    public function deleteCustomer($userId)
    {
        $user = User::findOrFail($userId);

        // ✅ Safety: only customers
        if ($user->role !== 'customer') {
            session()->flash('error', 'Only customer accounts can be deleted.');
            return;
        }

        $userName = $user->name;

        try {
            DB::transaction(function () use ($user) {
                // ✅ Clean up dependent rows (only if the table exists)
                if (Schema::hasTable('customer_points')) {
                    DB::table('customer_points')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('reward_redemptions')) {
                    DB::table('reward_redemptions')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('reviews')) {
                    DB::table('reviews')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('orders')) {
                    // ✅ Preserve order history — null the user_id instead of deleting orders.
                    // Change to ->delete() if you want order history gone too.
                    DB::table('orders')->where('user_id', $user->id)->update(['user_id' => null]);
                }

                $user->delete();
            });

            session()->flash('success', "Customer account {$userName} has been removed from the system.");
        } catch (\Throwable $e) {
            Log::error("Delete customer failed: {$e->getMessage()}");
            session()->flash('error', 'Failed to delete customer. Please try again.');
        }
    }

    // ================================================================
    // RENDER
    // ================================================================
    public function render()
    {
        // ============================================================
        // Customers — search runs only when needed
        // ============================================================
        $customers = User::where('role', 'customer')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);

        // ============================================================
        // Online user IDs — cached 20s so wire:poll doesn't hammer DB
        // ============================================================
        $activeSessionUserIds = Cache::remember('active_customer_sessions', 20, function () {
            return DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->unique()
                ->values()
                ->toArray();
        });

        // ✅ Count online across ALL customers (not just this page)
        $onlineCount = User::where('role', 'customer')
            ->whereIn('id', $activeSessionUserIds)
            ->count();

        $offlineCount = max($customers->total() - $onlineCount, 0);

        // ✅ Flag each row on the current page for the online dot
        $customers->getCollection()->transform(function ($customer) use ($activeSessionUserIds) {
            $customer->is_online = in_array($customer->id, $activeSessionUserIds, true);
            return $customer;
        });

        return view('pages.admin.⚡managecustomer.managecustomer', [
            'customers'    => $customers,
            'onlineCount'  => $onlineCount,
            'offlineCount' => $offlineCount,
        ]);
    }
};