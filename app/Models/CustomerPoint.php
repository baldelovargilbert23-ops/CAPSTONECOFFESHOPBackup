<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPoint extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'points',
        'lifetime_points',
        'points_used',
        'points_expiry',
    ];

    protected $casts = [
        'points_expiry' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}