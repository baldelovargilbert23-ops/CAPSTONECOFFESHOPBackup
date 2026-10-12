<?php

namespace App\Policies;

use App\Models\User;

class StaffPolicy
{
    /**
     * Owners see all staff; branch managers see their own branch's staff.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageTenant() || $user->isBranchManager();
    }

    /**
     * Owners can view any staff in their tenant.
     * Branch managers can view staff assigned to their branch.
     * A user can always view themselves.
     */
    public function view(User $user, User $staff): bool
    {
        if ($user->id === $staff->id) {
            return true;
        }

        if ($user->canManageTenant() && $user->tenant_id === $staff->tenant_id) {
            return true;
        }

        return $user->isBranchManager()
            && $user->branch_id === $staff->branch_id
            && $user->tenant_id === $staff->tenant_id;
    }

    /**
     * Only owners can create staff.
     * (Branch managers can add cashiers/baristas — enable this if you want.)
     */
    public function create(User $user): bool
    {
        return $user->isOwner() && $user->tenant_id !== null;
    }

    /**
     * Owners can update any staff in their tenant.
     * Branch managers can update staff in their own branch — but not other managers.
     */
    public function update(User $user, User $staff): bool
    {
        if ($user->canManageTenant() && $user->tenant_id === $staff->tenant_id) {
            return true;
        }

        if (! $user->isBranchManager()) {
            return false;
        }

        // Branch manager can edit staff in their own branch...
        if ($user->branch_id !== $staff->branch_id || $user->tenant_id !== $staff->tenant_id) {
            return false;
        }

        // ...but cannot edit other branch managers
        if ($staff->isBranchManager() && $staff->id !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Only owners can delete staff.
     * Branch managers can only deactivate (handled elsewhere).
     */
    public function delete(User $user, User $staff): bool
    {
        if (! $user->isOwner() || $user->tenant_id !== $staff->tenant_id) {
            return false;
        }

        // Can't delete yourself
        if ($user->id === $staff->id) {
            return false;
        }

        return true;
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(User $user, User $staff): bool
    {
        // Can't deactivate yourself
        if ($user->id === $staff->id) {
            return false;
        }

        if ($user->canManageTenant() && $user->tenant_id === $staff->tenant_id) {
            return true;
        }

        // Branch manager can toggle non-manager staff in their branch
        return $user->isBranchManager()
            && $user->branch_id === $staff->branch_id
            && ! $staff->isBranchManager();
    }
}