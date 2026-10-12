<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * Under the new model, the tenant IS its own store.
     * Users are never auto-linked to a "main branch":
     *   - branch_id = null → user belongs to the tenant's own store
     *   - branch_id = <id> → user belongs to a specific extra branch
     *
     * Nothing to auto-fill here.
     */
    public function created(User $user): void
    {
        // Intentionally empty.
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Intentionally empty.
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Intentionally empty.
    }
}