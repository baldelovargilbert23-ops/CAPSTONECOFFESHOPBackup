<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'tenant_id', 'branch_id', 'role',
        'is_active', 'preferred_barista_id',
        'is_on_shift', 'shift_started_at',
        'verification_code', 'verification_code_expires_at',
        'reset_code', 'reset_code_expires_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'verification_code', 'reset_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'            => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'reset_code_expires_at'        => 'datetime',
            'password'                     => 'hashed',
            'is_active'                    => 'boolean',
            'is_on_shift'                  => 'boolean',
            'shift_started_at'             => 'datetime',
        ];
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function tenant(): BelongsTo             { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo             { return $this->belongsTo(Branch::class); }
    public function preferredBarista(): BelongsTo   { return $this->belongsTo(User::class, 'preferred_barista_id'); }
    public function preferredBy(): HasMany          { return $this->hasMany(User::class, 'preferred_barista_id'); }
    public function assignedOrders(): HasMany       { return $this->hasMany(Order::class, 'barista_id'); }
    public function customerPoints(): HasOne        { return $this->hasOne(CustomerPoint::class); }
    public function reviews(): HasMany              { return $this->hasMany(Review::class); }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'barista_id')
            ->whereIn('status', ['pending', 'preparing', 'ready']);
    }

    // ============================================
    // ROLE HELPERS
    // ============================================
    public function isAdmin(): bool         { return $this->role === 'admin'; }
    public function isOwner(): bool         { return $this->role === 'owner'; }
    public function isCustomer(): bool      { return $this->role === 'customer'; }
    public function isBranchManager(): bool { return $this->role === 'branch_manager'; }
    public function isCashier(): bool       { return $this->role === 'cashier'; }
    public function isBarista(): bool       { return $this->role === 'barista'; }

    public function isBranchStaff(): bool
    {
        return in_array($this->role, ['branch_manager', 'cashier', 'barista'], true);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    // ============================================
    // PERMISSION HELPERS
    // ============================================
    public function canManageTenant(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    public function canManageBranch(int $branchId): bool
    {
        if (in_array($this->role, ['owner', 'admin'], true)) {
            return true;
        }
        return $this->role === 'branch_manager' && $this->branch_id === $branchId;
    }

    public function canSell(): bool
    {
        return in_array($this->role, ['owner', 'branch_manager', 'cashier'], true);
    }

    // ============================================
    // BARISTA SHIFT HELPERS
    // ============================================
    public function isAvailableForAssignment(int $maxActiveOrders = 5): bool
    {
        if (! $this->isBarista())  return false;
        if (! $this->is_active)    return false;
        if (! $this->is_on_shift)  return false;
        return $this->activeOrderCount() < $maxActiveOrders;
    }

    public function activeOrderCount(): int
    {
        return $this->activeOrders()->count();
    }

    public function startShift(): void
    {
        $this->update(['is_on_shift' => true, 'shift_started_at' => now()]);
    }

    public function endShift(): void
    {
        $this->update(['is_on_shift' => false, 'shift_started_at' => null]);
    }

    // ============================================
    // EMAIL VERIFICATION
    // ============================================
    public function generateVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'verification_code'            => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);
        return $code;
    }

    public function isVerificationCodeValid(string $code): bool
    {
        if (! $this->verification_code || ! $this->verification_code_expires_at) return false;
        if ($this->verification_code_expires_at->isPast()) return false;
        return hash_equals((string) $this->verification_code, $code);
    }

    public function markEmailAsVerified(): void
    {
        $this->update([
            'email_verified_at'            => now(),
            'verification_code'            => null,
            'verification_code_expires_at' => null,
        ]);
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    // ============================================
    // PASSWORD RESET
    // ============================================
    public function generateResetCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'reset_code'            => $code,
            'reset_code_expires_at' => now()->addMinutes(15),
        ]);
        return $code;
    }

    public function isResetCodeValid(string $code): bool
    {
        if (! $this->reset_code || ! $this->reset_code_expires_at) return false;
        if ($this->reset_code_expires_at->isPast()) return false;
        return hash_equals((string) $this->reset_code, $code);
    }

    public function clearResetCode(): void
    {
        $this->update(['reset_code' => null, 'reset_code_expires_at' => null]);
    }
}