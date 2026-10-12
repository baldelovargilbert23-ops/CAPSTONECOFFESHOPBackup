<?php

namespace App\Livewire\Pages\Admin;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.admin')] class extends Component
{
    public $search = '';
    public $statusFilter = '';
    public $dateFilter = '';
    public $selectedTenant = null;
    public $selectedBranch = null;
    public $showRejectModal = false;
    public $showDeleteModal = false;
    public $showBranchModal = false;
    public $rejectionReason = '';
    public $activeTab = 'tenants';
    public $tenantToDelete = null;
    public $deleteConfirmation = '';

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->selectedTenant = null;
        $this->selectedBranch = null;
    }

    public function viewTenant($tenantId)
    {
        $this->selectedTenant = Tenant::with([
            'users' => function($q) {
                $q->where('role', 'owner');
            },
            'branches' => function($q) {
                $q->with(['manager', 'users']);
            },
            'branches.orders' => function($q) {
                $q->where('status', 'completed');
            }
        ])->find($tenantId);

        if ($this->selectedTenant) {
            $this->selectedTenant->total_revenue = Order::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->sum('total_amount');

            $this->selectedTenant->total_orders = Order::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->count();

            $this->selectedTenant->total_staff = User::where('tenant_id', $tenantId)
                ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                ->count();
        }

        $this->showBranchModal = true;
    }

    public function closeTenantModal()
    {
        $this->selectedTenant = null;
        $this->showBranchModal = false;
    }

    public function viewBranch($branchId)
    {
        $this->selectedBranch = Branch::with([
            'tenant',
            'manager',
            'approvedBy',
            'users' => function($q) {
                $q->whereIn('role', ['branch_manager', 'cashier', 'barista']);
            }
        ])->find($branchId);

        $this->showBranchModal = true;
    }

    public function closeBranchModal()
    {
        $this->selectedBranch = null;
        $this->showBranchModal = false;
    }

    public function approveTenant($tenantId)
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            DB::transaction(function () use ($tenant) {
                $tenant->update([
                    'status' => 'active',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'rejection_reason' => null,
                ]);
            });

            session()->flash('success', "✅ Tenant '{$tenant->name}' has been approved!");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve tenant: ' . $e->getMessage());
        }
    }

    public function rejectTenant($tenantId)
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            DB::transaction(function () use ($tenant) {
                $tenant->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Rejected by admin',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
            });

            session()->flash('error', "❌ Tenant '{$tenant->name}' has been rejected.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reject tenant: ' . $e->getMessage());
        }
    }

    public function showDeleteTenantModal($tenantId)
    {
        $this->tenantToDelete = Tenant::with(['branches', 'users', 'orders'])->find($tenantId);

        if (!$this->tenantToDelete) {
            session()->flash('error', 'Tenant not found.');
            return;
        }

        $branchCount = $this->tenantToDelete->branches()->count();
        $orderCount = $this->tenantToDelete->orders()->count();
        $userCount = $this->tenantToDelete->users()->count();

        if ($branchCount > 0) {
            session()->flash('warning', "⚠️ This tenant has {$branchCount} branch(es), {$orderCount} order(s), and {$userCount} user(s). Deleting will remove all associated data.");
        }

        $this->deleteConfirmation = '';
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->tenantToDelete = null;
        $this->deleteConfirmation = '';
    }

    public function deleteTenant()
    {
        $this->validate([
            'deleteConfirmation' => 'required|in:DELETE',
        ], [
            'deleteConfirmation.required' => 'Please type DELETE to confirm.',
            'deleteConfirmation.in' => 'Please type DELETE exactly as shown.',
        ]);

        $tenant = $this->tenantToDelete;

        if (!$tenant) {
            session()->flash('error', 'Tenant not found.');
            $this->closeDeleteModal();
            return;
        }

        try {
            DB::transaction(function () use ($tenant) {
                $tenantName = $tenant->name;

                \Log::info('Tenant deletion initiated', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'deleted_by' => auth()->id(),
                    'branches' => $tenant->branches()->count(),
                    'orders' => $tenant->orders()->count(),
                    'users' => $tenant->users()->count(),
                ]);

                Order::where('tenant_id', $tenant->id)->delete();
                Branch::where('tenant_id', $tenant->id)->delete();
                User::where('tenant_id', $tenant->id)->delete();
                $tenant->delete();

                session()->flash('success', "✅ Tenant '{$tenantName}' has been permanently deleted.");
            });

            $this->closeDeleteModal();

        } catch (\Exception $e) {
            \Log::error('Failed to delete tenant: ' . $e->getMessage());
            session()->flash('error', 'Failed to delete tenant: ' . $e->getMessage());
        }
    }

    public function approveBranch($branchId)
    {
        try {
            $branch = Branch::findOrFail($branchId);

            DB::transaction(function () use ($branch) {
                $branch->update([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'is_active' => true,
                    'rejection_reason' => null,
                ]);
            });

            session()->flash('success', "✅ Branch '{$branch->name}' has been approved!");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve branch: ' . $e->getMessage());
        }
    }

    public function rejectBranch($branchId)
    {
        try {
            $branch = Branch::findOrFail($branchId);

            DB::transaction(function () use ($branch) {
                $branch->update([
                    'approval_status' => 'rejected',
                    'rejection_reason' => 'Rejected by admin',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'is_active' => false,
                ]);
            });

            session()->flash('error', "❌ Branch '{$branch->name}' has been rejected.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reject branch: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $tenants = Tenant::with(['users' => function($q) {
            $q->where('role', 'owner');
        }])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tenants_export_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($tenants) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Shop Name', 'Code', 'Owner', 'Email', 'Branches', 'Revenue', 'Status', 'Created At']);

            foreach ($tenants as $tenant) {
                $owner = $tenant->users->first();
                fputcsv($file, [
                    $tenant->id,
                    $tenant->name,
                    $tenant->code,
                    $owner->name ?? 'N/A',
                    $owner->email ?? 'N/A',
                    $tenant->branches()->count(),
                    Order::where('tenant_id', $tenant->id)->where('status', 'completed')->sum('total_amount'),
                    $tenant->status,
                    $tenant->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        session()->flash('message', 'CSV export generated successfully!');
        return response()->stream($callback, 200, $headers);
    }

    // ================================================================
    // RENDER - ONLY Active Tenants & Approved Branches
    // ================================================================
    public function render()
    {
        // ================================================================
        // TENANTS - Only Active (Exclude Pending & Rejected)
        // ================================================================
        $queryTenants = Tenant::with(['users' => function($q) {
            $q->where('role', 'owner');
        }])->where('status', 'active'); // ✅ ONLY ACTIVE

        if ($this->search && $this->activeTab === 'tenants') {
            $queryTenants->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhereHas('users', function ($u) {
                      $u->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter && $this->activeTab === 'tenants') {
            // Only allow 'active' filter here (no pending/rejected options should show)
            if (in_array($this->statusFilter, ['active'])) {
                $queryTenants->where('status', $this->statusFilter);
            }
        }

        if ($this->dateFilter === 'month') {
            $queryTenants->whereMonth('created_at', now()->month);
        } elseif ($this->dateFilter === 'year') {
            $queryTenants->whereYear('created_at', now()->year);
        }

        $tenants = $queryTenants->latest()->get();

        // ================================================================
        // BRANCHES - Only Approved (Exclude Pending & Rejected)
        // ================================================================
        $queryBranches = Branch::with(['tenant', 'manager', 'approvedBy'])
            ->where('approval_status', 'approved'); // ✅ ONLY APPROVED

        if ($this->search && $this->activeTab === 'branches') {
            $queryBranches->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhereHas('tenant', function ($t) {
                      $t->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter && $this->activeTab === 'branches') {
            if (in_array($this->statusFilter, ['approved'])) {
                $queryBranches->where('approval_status', $this->statusFilter);
            }
        }

        if ($this->dateFilter === 'month') {
            $queryBranches->whereMonth('created_at', now()->month);
        } elseif ($this->dateFilter === 'year') {
            $queryBranches->whereYear('created_at', now()->year);
        }

        $branches = $queryBranches->latest()->get();

        // ================================================================
        // STATS (kept as-is for the metric cards)
        // ================================================================
        $stats = [
            'tenants' => [
                'pending' => Tenant::where('status', 'pending')->count(),
                'approved' => Tenant::where('status', 'active')->count(),
                'rejected' => Tenant::where('status', 'rejected')->count(),
                'total' => Tenant::count(),
            ],
            'branches' => [
                'pending' => Branch::where('approval_status', 'pending')->count(),
                'approved' => Branch::where('approval_status', 'approved')->count(),
                'rejected' => Branch::where('approval_status', 'rejected')->count(),
                'total' => Branch::count(),
            ]
        ];

        return view('pages.admin.⚡manage-tenant.manage-tenant', [
            'tenants' => $tenants,
            'branches' => $branches,
            'stats' => $stats,
            'activeTab' => $this->activeTab,
            'selectedTenant' => $this->selectedTenant,
            'selectedBranch' => $this->selectedBranch,
            'showRejectModal' => $this->showRejectModal,
            'showDeleteModal' => $this->showDeleteModal,
            'showBranchModal' => $this->showBranchModal,
            'rejectionReason' => $this->rejectionReason,
            'tenantToDelete' => $this->tenantToDelete,
            'deleteConfirmation' => $this->deleteConfirmation,
        ]);
    }
};
?>