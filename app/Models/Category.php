<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;   // ⚠️ NO BranchScope — tenant-wide

    protected $fillable = ['tenant_id', 'cat_name', 'type'];

    protected $casts = [
        'type' => 'string',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function tenant(): BelongsTo   { return $this->belongsTo(Tenant::class); }
    public function products(): HasMany   { return $this->hasMany(Product::class); }

    // ============================================
    // SCOPES
    // ============================================
    public function scopeIngredients($query)
    {
        return $query->where('type', 'ingredient');
    }

    public function scopeFinished($query)
    {
        return $query->where('type', 'finished');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ============================================
    // HELPERS
    // ============================================
    public function isIngredient(): bool
    {
        return $this->type === 'ingredient';
    }

    public function isFinished(): bool
    {
        return $this->type === 'finished';
    }
}