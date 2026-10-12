<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Order $order): bool
    {
        // Customers can view their own orders
        if ($user->isCustomer() && $order->user_id === $user->id) {
            return true;
        }

        if ($user->canManageTenant() && $user->tenant_id === $order->tenant_id) {
            return true;
        }

        return $user->branch_id === $order->branch_id
            && $user->tenant_id === $order->tenant_id;
    }

    /**
     * Who can ring up a sale.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'branch_manager', 'cashier'], true)
            && $user->branch_id !== null;
    }

    /**
     * Update status (preparing/ready/completed/cancelled).
     */
    public function update(User $user, Order $order): bool
    {
        // Barista assigned to the order can update status
        if ($user->isBarista() && $order->barista_id === $user->id) {
            return true;
        }

        if ($user->canManageTenant() && $user->tenant_id === $order->tenant_id) {
            return true;
        }

        return in_array($user->role, ['branch_manager', 'cashier'], true)
            && $user->branch_id === $order->branch_id;
    }

    /**
     * Cancel — managers and above.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->canManageTenant() && $user->tenant_id === $order->tenant_id) {
            return true;
        }

        return $user->isBranchManager() && $user->branch_id === $order->branch_id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->canManageTenant() && $user->tenant_id === $order->tenant_id;
    }
}