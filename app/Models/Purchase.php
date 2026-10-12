<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['tenant_id', 'branch_id', 'total_cost', 'status'])]
class Purchase extends Model
{
    use HasFactory, BranchScope;

    protected $fillable = [
        'tenant_id',
        'branch_id', // ADD THIS
        'total_cost',
        'status',
    ];

   

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo // ADD THIS
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}