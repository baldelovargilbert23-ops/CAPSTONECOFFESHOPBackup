<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     * Uses insertOrIgnore so a duplicate never throws.
     */
    public function created(Tenant $tenant): void
    {
        DB::table('loyalty_settings')->insertOrIgnore([
            'tenant_id'            => $tenant->id,
            'is_enabled'           => false,
            'points_per_peso'      => 1,
            'points_per_order'     => 10,
            'points_expiry_days'   => 365,
            'min_points_to_redeem' => 100,
            'points_value_in_peso' => 1,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    /**
     * Handle the Tenant "updated" event.
     * Uses a plain UPDATE — no INSERT possible.
     */
    public function updated(Tenant $tenant): void
    {
        if ($tenant->wasChanged('status') && $tenant->status === 'active') {
            DB::table('loyalty_settings')
                ->where('tenant_id', $tenant->id)
                ->update([
                    'is_enabled'           => true,
                    'points_per_peso'      => 1,
                    'points_per_order'     => 10,
                    'points_expiry_days'   => 365,
                    'min_points_to_redeem' => 100,
                    'points_value_in_peso' => 1,
                    'updated_at'           => now(),
                ]);
        }
    }
}