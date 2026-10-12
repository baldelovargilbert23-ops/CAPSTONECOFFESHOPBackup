<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltySetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'is_enabled',
        'points_per_peso',
        'points_per_order',
        'points_expiry_days',
        'min_points_to_redeem',
        'points_value_in_peso',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}