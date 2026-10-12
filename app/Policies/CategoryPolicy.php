<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Category $category): bool
    {
        return $user->tenant_id === $category->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner() && $user->tenant_id !== null;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isOwner() && $user->tenant_id === $category->tenant_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isOwner() && $user->tenant_id === $category->tenant_id;
    }
}