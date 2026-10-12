<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['user_id', 'tenant_id', 'role'])]
class UserTenantRole extends Model
{
    use HasFactory;

    protected $table = 'user_tenant_roles';

    /**
     * The user this role assignment belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The shop (tenant) this role is associated with.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the role name (using the role column directly)
     */
    public function getRoleNameAttribute()
    {
        return $this->role; // Just use the role column
    }
}