<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['purchase_id', 'branch_id', 'product_id', 'quantity', 'cost', 'subtotal'])]
class PurchaseItem extends Model
{
    use HasFactory, BranchScope;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'branch_id', // ADD THIS
        'product_id',
        'quantity',
        'cost',
        'subtotal',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function branch(): BelongsTo // ADD THIS
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}