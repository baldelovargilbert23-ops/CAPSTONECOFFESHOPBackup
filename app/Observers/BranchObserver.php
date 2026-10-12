<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\BranchLoyaltySetting;

class BranchObserver
{
    /**
     * Handle the Branch "created" event.
     * Idempotent — safe to fire multiple times.
     */
    public function created(Branch $branch): void
    {
        BranchLoyaltySetting::firstOrCreate(
            ['branch_id' => $branch->id],
            [
                'tenant_id'  => $branch->tenant_id,
                'is_enabled' => true,
            ]
        );
    }
}