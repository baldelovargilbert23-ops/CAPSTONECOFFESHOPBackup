<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'image',
        'points_required',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class, 'reward_item_id');
    }
}