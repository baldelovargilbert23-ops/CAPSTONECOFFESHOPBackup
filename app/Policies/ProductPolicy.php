<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Anyone on the tenant can browse products.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Same tenant, any role → can view.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id;
    }

    /**
     * Only owners create products.
     */
    public function create(User $user): bool
    {
        return $user->isOwner() && $user->tenant_id !== null;
    }

    /**
     * Only owners update products.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->isOwner() && $user->tenant_id === $product->tenant_id;
    }

    /**
     * Only owners delete products.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->isOwner() && $user->tenant_id === $product->tenant_id;
    }

    /**
     * Toggle active — owners only.
     */
    public function toggleActive(User $user, Product $product): bool
    {
        return $user->isOwner() && $user->tenant_id === $product->tenant_id;
    }
}