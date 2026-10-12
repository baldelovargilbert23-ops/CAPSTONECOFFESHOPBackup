<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasFactory, BranchScope;

    protected $table = 'inventory_logs';

    protected $fillable = [
        'tenant_id', 'branch_id', 'product_id', 'user_id',
        'type', 'quantity', 'reference', 'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function tenant(): BelongsTo   { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
