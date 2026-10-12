<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ApprovalController extends Controller
{
    // ================================================================
    // TENANT APPROVALS
    // ================================================================

    /**
     * Show all pending tenant approvals
     */
    public function tenantIndex()
    {
        $pendingTenants = Tenant::where('status', 'pending')
            ->with(['users' => function($q) {
                $q->where('role', 'owner');
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        $stats = [
            'pending' => Tenant::where('status', 'pending')->count(),
            'approved' => Tenant::where('status', 'active')->count(),
            'rejected' => Tenant::where('status', 'rejected')->count(),
            'total' => Tenant::count(),
        ];

        return view('superadmin.tenant-approvals.index', compact('pendingTenants', 'stats'));
    }

    /**
     * Approve a tenant
     */
    public function approveTenant(Tenant $tenant)
    {
        try {
            DB::transaction(function () use ($tenant) {
                $tenant->update([
                    'status' => 'active',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'rejection_reason' => null,
                ]);

                // Send approval notification to tenant
                // Mail::to($tenant->email)->send(new TenantApprovedNotification($tenant));
            });

            return redirect()->route('admin.tenant-approvals')
                ->with('success', "✅ Tenant '{$tenant->name}' has been approved successfully!");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve tenant: ' . $e->getMessage());
        }
    }

    /**
     * Show reject form for tenant
     */
    public function showTenantRejectForm(Tenant $tenant)
    {
        return view('superadmin.tenant-approvals.reject', compact('tenant'));
    }

    /**
     * Reject a tenant
     */
    public function rejectTenant(Request $request, Tenant $tenant)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ]);

        try {
            DB::transaction(function () use ($tenant, $request) {
                $tenant->update([
                    'status' => 'rejected',
                    'rejection_reason' => $request->rejection_reason,
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);

                // Send rejection notification to tenant
                // Mail::to($tenant->email)->send(new TenantRejectedNotification($tenant));
            });

            return redirect()->route('admin.tenant-approvals')
                ->with('info', "❌ Tenant '{$tenant->name}' has been rejected.");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject tenant: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve tenants
     */
    public function bulkApproveTenants(Request $request)
    {
        $request->validate([
            'tenant_ids' => 'required|array',
            'tenant_ids.*' => 'exists:tenants,id',
        ]);

        try {
            $count = 0;
            foreach ($request->tenant_ids as $tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant && $tenant->status === 'pending') {
                    $tenant->update([
                        'status' => 'active',
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                        'rejection_reason' => null,
                    ]);
                    $count++;
                }
            }

            return back()->with('success', "✅ {$count} tenant(s) approved successfully!");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to bulk approve tenants: ' . $e->getMessage());
        }
    }

    // ================================================================
    // BRANCH APPROVALS
    // ================================================================

    /**
     * Show all pending branch approvals
     */
    public function branchIndex()
    {
        $pendingBranches = Branch::where('approval_status', 'pending')
            ->with(['tenant', 'approvedBy'])
            ->orderBy('created_at', 'asc')
            ->get();

        $stats = [
            'pending' => Branch::where('approval_status', 'pending')->count(),
            'approved' => Branch::where('approval_status', 'approved')->count(),
            'rejected' => Branch::where('approval_status', 'rejected')->count(),
            'total' => Branch::count(),
        ];

        return view('superadmin.branch-approvals.index', compact('pendingBranches', 'stats'));
    }

    /**
     * Approve a branch
     */
    public function approveBranch(Branch $branch)
    {
        try {
            DB::transaction(function () use ($branch) {
                $branch->update([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'rejection_reason' => null,
                ]);

                // Optionally activate the branch
                $branch->update(['is_active' => true]);

                // Send approval notification to tenant
                // Mail::to($branch->tenant->email)->send(new BranchApprovedNotification($branch));
            });

            return redirect()->route('admin.branch-approvals')
                ->with('success', "✅ Branch '{$branch->name}' has been approved successfully!");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve branch: ' . $e->getMessage());
        }
    }

    /**
     * Show reject form for branch
     */
    public function showBranchRejectForm(Branch $branch)
    {
        return view('superadmin.branch-approvals.reject', compact('branch'));
    }

    /**
     * Reject a branch
     */
    public function rejectBranch(Request $request, Branch $branch)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ]);

        try {
            DB::transaction(function () use ($branch, $request) {
                $branch->update([
                    'approval_status' => 'rejected',
                    'rejection_reason' => $request->rejection_reason,
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'is_active' => false,
                ]);

                // Send rejection notification to tenant
                // Mail::to($branch->tenant->email)->send(new BranchRejectedNotification($branch));
            });

            return redirect()->route('admin.branch-approvals')
                ->with('info', "❌ Branch '{$branch->name}' has been rejected.");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject branch: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve branches
     */
    public function bulkApproveBranches(Request $request)
    {
        $request->validate([
            'branch_ids' => 'required|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        try {
            $count = 0;
            foreach ($request->branch_ids as $branchId) {
                $branch = Branch::find($branchId);
                if ($branch && $branch->approval_status === 'pending') {
                    $branch->update([
                        'approval_status' => 'approved',
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                        'is_active' => true,
                    ]);
                    $count++;
                }
            }

            return back()->with('success', "✅ {$count} branch(es) approved successfully!");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to bulk approve branches: ' . $e->getMessage());
        }
    }

    /**
     * Check approval status for polling requests from the approval-pending page.
     * Called by JavaScript fetch() from the pending approval view.
     */
    public function checkStatus(Request $request, string $type)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'status'   => 'unauthenticated',
                'redirect' => route('login'),
            ]);
        }

        if ($type === 'tenant') {
            $tenant = $user->tenant_id ? Tenant::find($user->tenant_id) : null;

            if (! $tenant) {
                return response()->json([
                    'status'   => 'not_found',
                    'redirect' => null,
                ]);
            }

            if ($tenant->status === 'active') {
                $hasApprovedBranch = Branch::where('tenant_id', $tenant->id)
                    ->where('approval_status', 'approved')
                    ->exists();

                if ($hasApprovedBranch) {
                    return response()->json([
                        'status'   => 'approved',
                        'redirect' => route('owner.dashboard'),
                    ]);
                }

                $hasPendingBranch = Branch::where('tenant_id', $tenant->id)
                    ->where('approval_status', 'pending')
                    ->exists();

                if ($hasPendingBranch) {
                    return response()->json([
                        'status'   => 'approved',
                        'redirect' => route('approval-pending', ['type' => 'branch']),
                    ]);
                }

                return response()->json([
                    'status'   => 'approved',
                    'redirect' => route('owner.branches'),
                ]);
            }

            return response()->json([
                'status'   => $tenant->status,
                'redirect' => null,
            ]);
        }

        if ($type === 'branch') {
            $branch = Branch::where('tenant_id', $user->tenant_id)
                ->whereIn('approval_status', ['pending', 'approved', 'rejected'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $branch) {
                return response()->json([
                    'status'   => 'not_found',
                    'redirect' => null,
                ]);
            }

            if ($branch->approval_status === 'approved') {
                return response()->json([
                    'status'   => 'approved',
                    'redirect' => route('owner.dashboard'),
                ]);
            }

            return response()->json([
                'status'   => $branch->approval_status,
                'redirect' => null,
            ]);
        }

        return response()->json([
            'status'   => 'unknown',
            'redirect' => null,
        ]);
    }
}