<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Inventory $inventory): bool
    {
        // Owners see all
        if ($user->canManageTenant() && $user->tenant_id === $inventory->tenant_id) {
            return true;
        }

        // Staff see only their branch
        return $user->branch_id === $inventory->branch_id
            && $user->tenant_id === $inventory->tenant_id;
    }

    /**
     * Who can adjust stock.
     */
    public function adjust(User $user, Inventory $inventory): bool
    {
        if (! in_array($user->role, ['owner', 'admin', 'branch_manager'], true)) {
            return false;
        }

        if ($user->canManageTenant() && $user->tenant_id === $inventory->tenant_id) {
            return true;
        }

        return $user->isBranchManager() && $user->branch_id === $inventory->branch_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'branch_manager'], true);
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return $this->adjust($user, $inventory);
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return $user->canManageTenant() && $user->tenant_id === $inventory->tenant_id;
    }
}