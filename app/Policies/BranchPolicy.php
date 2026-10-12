<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageTenant();
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->canManageTenant() && $user->tenant_id === $branch->tenant_id) {
            return true;
        }

        return $user->branch_id === $branch->id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner() && $user->tenant_id !== null;
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->canManageTenant() && $user->tenant_id === $branch->tenant_id) {
            return true;
        }

        return $user->isBranchManager() && $user->branch_id === $branch->id;
    }

    /**
     * Only owners can delete, and any branch of their tenant can be deleted.
     * Under the new model there is no main branch to protect —
     * the tenant IS the own store.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->isOwner()
            && $user->tenant_id === $branch->tenant_id;
    }

    public function approve(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function toggleActive(User $user, Branch $branch): bool
    {
        return $user->canManageTenant() && $user->tenant_id === $branch->tenant_id;
    }
}