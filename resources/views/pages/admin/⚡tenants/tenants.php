<?php

namespace App\Livewire\Pages\Admin;

use App\Models\Branch;
use App\Models\Tenant;
use App\Notifications\BranchApprovedNotification;
use App\Notifications\BranchRejectedNotification;
use App\Notifications\TenantApprovedNotification;
use App\Notifications\TenantRejectedNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.admin')] class extends Component
{
    public $search = '';
    public $activeTab = 'tenants';

    // Reject modal
    public $showRejectModal = false;
    public $rejectionReason = '';
    public $rejectType = '';
    public $rejectId = null;

    // Details modal
    public $showDetailsModal = false;
    public $detailsType = '';
    public $detailsId = null;
    public $detailsRecord = null;

    public $historySearch = '';
    public $historyFilter = 'all';

    // ================================================================
    // TAB CONTROLS
    // ================================================================
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->historySearch = '';
        $this->historyFilter = 'all';
        $this->closeRejectModal();
        $this->closeDetailsModal();
    }

    // ================================================================
    // TENANT APPROVALS
    // ================================================================
    public function approveTenant($tenantId)
    {
        try {
            $tenant = Tenant::with(['users' => fn($q) => $q->where('role', 'owner')])
                ->findOrFail($tenantId);

            // ✅ Guard: only allow approving pending tenants
            if ($tenant->status !== 'pending') {
                session()->flash('warning', "Tenant '{$tenant->name}' is already {$tenant->status}.");
                $this->closeDetailsModal();
                return;
            }

            DB::transaction(function () use ($tenant) {
                $tenant->update([
                    'status'           => 'active',
                    'approved_at'      => now(),
                    'approved_by'      => auth()->id(),
                    'rejection_reason' => null,
                ]);
            });

            $this->sendNotificationNow(
                $tenant->users->first(),
                new TenantApprovedNotification($tenant),
                "Tenant #{$tenant->id} approval"
            );

            session()->flash('success', "✅ Tenant '{$tenant->name}' has been approved and notified by email.");
            $this->closeDetailsModal();
        } catch (\Exception $e) {
            Log::error('Tenant approval failed: ' . $e->getMessage());
            session()->flash('error', 'Failed to approve tenant.');
        }
    }

    public function rejectTenant($tenantId)
    {
        $this->openRejectModal('tenant', $tenantId);
    }

    // ================================================================
    // BRANCH APPROVALS
    // ================================================================
    public function approveBranch($branchId)
    {
        try {
            $branch = Branch::with(['tenant', 'manager'])->findOrFail($branchId);

            // ✅ Guard: only allow approving pending branches
            if ($branch->approval_status !== 'pending') {
                session()->flash('warning', "Branch '{$branch->name}' is already {$branch->approval_status}.");
                $this->closeDetailsModal();
                return;
            }

            DB::transaction(function () use ($branch) {
                $branch->update([
                    'approval_status'  => 'approved',
                    'approved_at'      => now(),
                    'approved_by'      => auth()->id(),
                    'is_active'        => true,
                    'rejection_reason' => null,
                ]);
            });

            $this->sendNotificationNow(
                $this->getBranchContact($branch),
                new BranchApprovedNotification($branch),
                "Branch #{$branch->id} approval"
            );

            session()->flash('success', "✅ Branch '{$branch->name}' has been approved and notified by email.");
            $this->closeDetailsModal();
        } catch (\Exception $e) {
            Log::error('Branch approval failed: ' . $e->getMessage());
            session()->flash('error', 'Failed to approve branch.');
        }
    }

    public function rejectBranch($branchId)
    {
        $this->openRejectModal('branch', $branchId);
    }

    // ================================================================
    // ✅ COMBINED APPROVE + CLOSE (fixes wire:click with ';')
    // ================================================================
    public function approveAndClose($type, $id)
    {
        if ($type === 'tenant') {
            $this->approveTenant($id);
        } else {
            $this->approveBranch($id);
        }
        $this->closeDetailsModal();
    }

    // ================================================================
    // REJECT MODAL
    // ================================================================
    public function openRejectModal($type, $id)
    {
        // ✅ Reset validation BEFORE setting new values
        $this->resetValidation();

        $this->rejectType = $type;
        $this->rejectId   = $id;

        $this->rejectionReason = $type === 'tenant'
            ? 'Your application did not meet our requirements at this time.'
            : 'The branch details or business permit provided were incomplete or invalid.';

        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectionReason = '';
        $this->rejectType = '';
        $this->rejectId = null;
        $this->resetValidation();
    }

    public function confirmReject()
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rejectionReason.required' => 'Please enter a reason for rejection.',
            'rejectionReason.min'      => 'The reason must be at least 5 characters.',
            'rejectionReason.max'      => 'The reason must not exceed 1000 characters.',
        ]);

        try {
            if ($this->rejectType === 'tenant') {
                $this->performRejectTenant($this->rejectId, $this->rejectionReason);
            } elseif ($this->rejectType === 'branch') {
                $this->performRejectBranch($this->rejectId, $this->rejectionReason);
            }
            $this->closeRejectModal();
        } catch (\Exception $e) {
            Log::error('Rejection failed: ' . $e->getMessage());
            session()->flash('error', 'Failed to reject.');
        }
    }

    protected function performRejectTenant($tenantId, $reason)
    {
        $tenant = Tenant::with(['users' => fn($q) => $q->where('role', 'owner')])
            ->findOrFail($tenantId);

        // ✅ Guard: can't reject something that isn't pending
        if ($tenant->status !== 'pending') {
            session()->flash('warning', "Tenant '{$tenant->name}' is already {$tenant->status} — cannot reject.");
            return;
        }

        DB::transaction(function () use ($tenant, $reason) {
            $tenant->update([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
                'approved_at'      => now(),
                'approved_by'      => auth()->id(),
            ]);
        });

        $this->sendNotificationNow(
            $tenant->users->first(),
            new TenantRejectedNotification($tenant, $reason),
            "Tenant #{$tenant->id} rejection"
        );

        session()->flash('error', "❌ Tenant '{$tenant->name}' has been rejected. Reason sent to their email.");
    }

    protected function performRejectBranch($branchId, $reason)
    {
        $branch = Branch::with(['tenant', 'manager'])->findOrFail($branchId);

        // ✅ Guard: can't reject something that isn't pending
        if ($branch->approval_status !== 'pending') {
            session()->flash('warning', "Branch '{$branch->name}' is already {$branch->approval_status} — cannot reject.");
            return;
        }

        DB::transaction(function () use ($branch, $reason) {
            $branch->update([
                'approval_status'  => 'rejected',
                'rejection_reason' => $reason,
                'approved_at'      => now(),
                'approved_by'      => auth()->id(),
                'is_active'        => false,
            ]);
        });

        $this->sendNotificationNow(
            $this->getBranchContact($branch),
            new BranchRejectedNotification($branch, $reason),
            "Branch #{$branch->id} rejection"
        );

        session()->flash('error', "❌ Branch '{$branch->name}' has been rejected. Reason sent to their email.");
    }

    // ================================================================
    // DETAILS MODAL
    // ================================================================
    public function viewDetails($type, $id)
    {
        try {
            if ($type === 'tenant') {
                $this->detailsRecord = Tenant::with(['users' => fn($q) => $q->where('role', 'owner')])
                    ->findOrFail($id);
            } else {
                $this->detailsRecord = Branch::with(['tenant', 'manager'])
                    ->findOrFail($id);
            }

            $this->detailsType = $type;
            $this->detailsId = $id;
            $this->showDetailsModal = true;
        } catch (\Throwable $e) {
            Log::error('View details failed: ' . $e->getMessage());
            session()->flash('error', 'Record not found.');
            $this->closeDetailsModal();
        }
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->detailsRecord = null;
        $this->detailsType = '';
        $this->detailsId = null;
    }

    // ================================================================
    // HELPERS
    // ================================================================
    protected function getBranchContact(Branch $branch)
    {
        return $branch->manager
            ?? $branch->tenant?->users()->where('role', 'owner')->first();
    }

    /**
     * ✅ Use notifyNow() — sends immediately via sync without mutating global config.
     */
    protected function sendNotificationNow($notifiable, $notification, string $context = ''): void
    {
        if (! $notifiable || empty($notifiable->email)) {
            Log::warning("No recipient for {$context} — skipping notification.");
            return;
        }

        try {
            $notifiable->notifyNow($notification);
            Log::info("Notification sent for {$context} to {$notifiable->email}");
        } catch (\Throwable $e) {
            Log::error("Notification failed for {$context}: " . $e->getMessage());
        }
    }

    // ================================================================
    // RENDER
    // ================================================================
    public function render()
    {
        // ============================================================
        // PENDING TENANTS
        // ============================================================
        $queryTenants = Tenant::where('status', 'pending')
            ->with(['users' => fn($q) => $q->where('role', 'owner')]);

        if ($this->search && $this->activeTab === 'tenants') {
            $queryTenants->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhereHas('users', fn($u) => $u->where('name', 'like', "%{$this->search}%"));
            });
        }

        $pendingTenants = $queryTenants->latest()->get();

        // ============================================================
        // PENDING BRANCHES
        // ============================================================
        $queryBranches = Branch::where('approval_status', 'pending')
            ->with(['tenant']);

        if ($this->search && $this->activeTab === 'branches') {
            $queryBranches->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$this->search}%"));
            });
        }

        $pendingBranches = $queryBranches->latest()->get();

        // ============================================================
        // HISTORY — only load when the History tab is active (perf win)
        // ============================================================
        $historyTenants = collect();
        $historyBranches = collect();

        if ($this->activeTab === 'history') {
            $historyTenants = Tenant::whereIn('status', ['active', 'rejected'])
                ->with(['users' => fn($q) => $q->where('role', 'owner')])
                ->when($this->historySearch, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', "%{$this->historySearch}%")
                              ->orWhere('email', 'like', "%{$this->historySearch}%")
                              ->orWhereHas('users', fn($u) => $u->where('name', 'like', "%{$this->historySearch}%"));
                    });
                })
                ->when($this->historyFilter === 'approved', fn($q) => $q->where('status', 'active'))
                ->when($this->historyFilter === 'rejected', fn($q) => $q->where('status', 'rejected'))
                ->latest('updated_at')
                ->get();

            $historyBranches = Branch::whereIn('approval_status', ['approved', 'rejected'])
                ->with(['tenant', 'manager'])
                ->when($this->historySearch, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', "%{$this->historySearch}%")
                              ->orWhere('code', 'like', "%{$this->historySearch}%")
                              ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$this->historySearch}%"));
                    });
                })
                ->when($this->historyFilter === 'approved', fn($q) => $q->where('approval_status', 'approved'))
                ->when($this->historyFilter === 'rejected', fn($q) => $q->where('approval_status', 'rejected'))
                ->latest('updated_at')
                ->get();
        }

        // ============================================================
        // STATS
        // ============================================================
        $stats = [
            'tenants' => [
                'pending'  => Tenant::where('status', 'pending')->count(),
                'approved' => Tenant::where('status', 'active')->count(),
                'rejected' => Tenant::where('status', 'rejected')->count(),
                'total'    => Tenant::count(),
            ],
            'branches' => [
                'pending'  => Branch::where('approval_status', 'pending')->count(),
                'approved' => Branch::where('approval_status', 'approved')->count(),
                'rejected' => Branch::where('approval_status', 'rejected')->count(),
                'total'    => Branch::count(),
            ],
        ];

        return view('pages.admin.⚡tenants.tenants', [
            'pendingTenants'  => $pendingTenants,
            'pendingBranches' => $pendingBranches,
            'historyTenants'  => $historyTenants,
            'historyBranches' => $historyBranches,
            'stats'           => $stats,
            'activeTab'       => $this->activeTab,
        ]);
    }
};