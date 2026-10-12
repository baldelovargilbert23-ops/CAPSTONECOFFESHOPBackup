<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
trait BranchScope
{
    public static function bootBranchScope(): void
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            $user = auth()->user();

            // No auth → no filter (CLI, seeders, queues, guest browsing)
            if (! $user) {
                return;
            }

            // Admin → no filter
            if ($user->role === 'admin') {
                return;
            }

            $model = $builder->getModel();

            // Owner roles → tenant-wide, but never branch-restricted
            if (in_array($user->role, ['owner', 'shop_owner', 'tenant_owner'], true)) {
                if ($user->tenant_id) {
                    static::scopeToTenant($builder, $model, (int) $user->tenant_id);
                }
                return;
            }

            // Customers can browse public tenant and branch pages.
            if ($user->role === 'customer') {
                return;
            }

            // Staff are limited to their tenant and location. Tenant-less
            // child tables inherit tenant ownership through their parent.
            if (in_array($user->role, ['branch_manager', 'cashier', 'barista'], true)) {
                if ($user->tenant_id) {
                    static::scopeToTenant($builder, $model, (int) $user->tenant_id);
                }

                if (static::hasBranchScopeColumn($model, 'branch_id')) {
                    if ($user->branch_id) {
                        $builder->where($model->getTable() . '.branch_id', $user->branch_id);
                    } else {
                        $builder->whereNull($model->getTable() . '.branch_id');
                    }
                }
            }
        });

        static::creating(function (Model $model) {
            $user = auth()->user();
            if (! $user) return;

            if (! in_array($user->role, ['owner', 'shop_owner', 'tenant_owner', 'branch_manager', 'cashier', 'barista'], true)) {
                return;
            }

            if (static::hasBranchScopeColumn($model, 'branch_id') && empty($model->branch_id) && $user->branch_id) {
                $model->branch_id = $user->branch_id;
            }

            if (static::hasBranchScopeColumn($model, 'tenant_id') && empty($model->tenant_id) && $user->tenant_id) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }

    protected static function scopeToTenant(Builder $builder, Model $model, int $tenantId): void
    {
        if (static::hasBranchScopeColumn($model, 'tenant_id')) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
            return;
        }

        // Payments and order items inherit ownership from their order;
        // purchase items inherit it from their purchase.
        $parentRelation = method_exists($model, 'order')
            ? 'order'
            : (method_exists($model, 'purchase') ? 'purchase' : null);

        if ($parentRelation) {
            $builder->whereHas($parentRelation, fn (Builder $parent) => $parent->where('tenant_id', $tenantId));
            return;
        }

        // Fail closed if a tenant-scoped model has no direct or parent tenant key.
        $builder->whereRaw('1 = 0');
    }

    protected static function hasBranchScopeColumn(Model $model, string $column): bool
    {
        return match ($column) {
            'tenant_id' => method_exists($model, 'tenant'),
            'branch_id' => method_exists($model, 'branch'),
            default => false,
        };
    }

    public static function withoutBranchScope(): Builder
    {
        return static::withoutGlobalScope('branch');
    }

    public static function forBranch(int $branchId): Builder
    {
        return static::withoutGlobalScope('branch')->where('branch_id', $branchId);
    }
}
