<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory, BranchScope;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_id',
        'user_id',
        'type',           // ✅ NEW
        'rating',
        'comment',
        'photo_paths',    // ✅ NEW
        'owner_reply',
        'is_visible',
    ];

    protected $casts = [
        'photo_paths' => 'array',   // ✅ NEW — auto-decodes JSON to PHP array
        'is_visible'  => 'boolean', // ✅ NEW — 0/1 → true/false
        'rating'      => 'integer', // ✅ NEW — always int
    ];

    // ================================================================
    // RELATIONSHIPS (existing — untouched)
    // ================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ================================================================
    // SCOPES (✅ NEW)
    // ================================================================

    /**
     * Only product reviews.
     */
    public function scopeProductReviews($query)
    {
        return $query->where('type', 'product');
    }

    /**
     * Only branch reviews.
     */
    public function scopeBranchReviews($query)
    {
        return $query->where('type', 'branch');
    }

    /**
     * Only visible reviews.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Reviews that have photos.
     */
    public function scopeWithPhotos($query)
    {
        return $query->whereNotNull('photo_paths')
                     ->where('photo_paths', '!=', '[]');
    }

    // ================================================================
    // HELPERS (✅ NEW)
    // ================================================================

    /**
     * Is this a product review?
     */
    public function isProductReview(): bool
    {
        return $this->type === 'product';
    }

    /**
     * Is this a branch review?
     */
    public function isBranchReview(): bool
    {
        return $this->type === 'branch';
    }

    /**
     * Does this review have any photos?
     */
    public function hasPhotos(): bool
    {
        return is_array($this->photo_paths) && count($this->photo_paths) > 0;
    }

    /**
     * Get public URLs for all photos.
     */
    public function photoUrls(): array
    {
        if (!$this->hasPhotos()) {
            return [];
        }

        return array_map(
            fn($path) => \Storage::disk('public')->url($path),
            $this->photo_paths
        );
    }

    /**
     * Does the owner have a reply yet?
     */
    public function hasOwnerReply(): bool
    {
        return !is_null($this->owner_reply) && trim($this->owner_reply) !== '';
    }
}