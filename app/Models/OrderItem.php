<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory, BranchScope;

    protected $fillable = [
        'order_id', 'branch_id', 'product_id',
        'item_name', 'is_reward_item',
        'quantity', 'price', 'subtotal',
    ];

    protected $casts = [
        'is_reward_item' => 'boolean',
        'price'          => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    public function order(): BelongsTo   { return $this->belongsTo(Order::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function isRewardItem(): bool { return $this->is_reward_item ?? false; }
}