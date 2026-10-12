<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CustomerPoint;
use App\Models\LoyaltySetting;
use App\Models\Order;
use App\Models\RewardItem;
use App\Models\RewardRedemption;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoyaltyService
{
    /* ================================================================
     * EARN
     * ================================================================ */

    /**
     * Award loyalty points for a completed order.
     *
     * Idempotent — safe to call multiple times on the same order.
     * Returns points awarded (0 if skipped for any reason).
     */
    public function awardForOrder(Order $order): int
    {
        return DB::transaction(function () use ($order): int {
            $lockedOrder = Order::withoutGlobalScopes()
                ->where('tenant_id', $order->tenant_id)
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder
                || ! $lockedOrder->user_id
                || $lockedOrder->status !== 'completed'
                || $lockedOrder->loyalty_awarded_at !== null
                || $lockedOrder->is_reward_order) {
                return 0;
            }

            $tenantSetting = LoyaltySetting::where('tenant_id', $lockedOrder->tenant_id)->first();
            if (! $tenantSetting?->is_enabled) {
                return 0;
            }

            if ($lockedOrder->branch_id) {
                $branch = Branch::where('tenant_id', $lockedOrder->tenant_id)->find($lockedOrder->branch_id);
                if (! $branch?->isLoyaltyEnabled()) {
                    return 0;
                }
            }

            $points = (int) floor((float) $lockedOrder->total_amount * (int) $tenantSetting->points_per_peso)
                + (int) $tenantSetting->points_per_order;

            if ($points <= 0) {
                return 0;
            }

            CustomerPoint::firstOrCreate(
                ['tenant_id' => $lockedOrder->tenant_id, 'user_id' => $lockedOrder->user_id],
                ['points' => 0, 'lifetime_points' => 0, 'points_used' => 0]
            );

            $customerPoints = CustomerPoint::where('tenant_id', $lockedOrder->tenant_id)
                ->where('user_id', $lockedOrder->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $customerPoints->points = (int) $customerPoints->points + $points;
            $customerPoints->lifetime_points = (int) $customerPoints->lifetime_points + $points;
            $customerPoints->points_expiry = now()
                ->addDays((int) $tenantSetting->points_expiry_days)
                ->toDateString();
            $customerPoints->save();

            $lockedOrder->forceFill([
                'loyalty_awarded_at'     => now(),
                'loyalty_points_awarded' => $points,
            ])->saveQuietly();

            return $points;
        });
    }

    /* ================================================================
     * REVERSE (undo)
     * ================================================================ */

    /**
     * Reverse loyalty points awarded for an order.
     * Called when a completed order is undone.
     *
     * Uses loyalty_points_awarded to subtract the exact amount.
     * Clears the audit flags so the order can be re-completed later.
     */
    public function reverseForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::withoutGlobalScopes()
                ->where('tenant_id', $order->tenant_id)
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder || $lockedOrder->loyalty_awarded_at === null) {
                return;
            }

            $pointsToReverse = (int) ($lockedOrder->loyalty_points_awarded ?? 0);

            if ($pointsToReverse > 0 && $lockedOrder->user_id) {
                $customerPoints = CustomerPoint::where('tenant_id', $lockedOrder->tenant_id)
                    ->where('user_id', $lockedOrder->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($customerPoints) {
                    $customerPoints->points = max(0, (int) $customerPoints->points - $pointsToReverse);
                    $customerPoints->lifetime_points = max(0, (int) $customerPoints->lifetime_points - $pointsToReverse);
                    $customerPoints->save();
                }
            }

            $lockedOrder->forceFill([
                'loyalty_awarded_at'     => null,
                'loyalty_points_awarded' => null,
            ])->saveQuietly();
        });
    }

    /* ================================================================
     * REDEEM
     * ================================================================ */

    /**
     * Redeem a reward for a customer.
     *
     * @throws RuntimeException
     */
    public function redeem(int $userId, int $rewardId, ?int $branchId, int $tenantId): RewardRedemption
    {
        $tenantSetting = LoyaltySetting::where('tenant_id', $tenantId)->first();

        if (! $tenantSetting?->is_enabled) {
            throw new RuntimeException('Loyalty program is disabled for this tenant.');
        }

        if ($branchId) {
            $branch = Branch::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->find($branchId);
            if (! $branch || ! $branch->isLoyaltyEnabled()) {
                throw new RuntimeException('Loyalty program is disabled at this branch.');
            }
        }

        return DB::transaction(function () use ($rewardId, $branchId, $tenantId, $userId, $tenantSetting) {
            $reward = RewardItem::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($rewardId);

            if ($reward->stock <= 0) {
                throw new RuntimeException('This reward is out of stock.');
            }

            $cp = CustomerPoint::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cp->points < $reward->points_required) {
                throw new RuntimeException('Not enough points.');
            }

            if ($cp->points < $tenantSetting->min_points_to_redeem) {
                throw new RuntimeException(
                    'You need at least ' . $tenantSetting->min_points_to_redeem . ' points to redeem.'
                );
            }

            $cp->points = (int) $cp->points - (int) $reward->points_required;
            $cp->points_used = (int) $cp->points_used + (int) $reward->points_required;
            $cp->save();

            $reward->stock = (int) $reward->stock - 1;
            $reward->save();

            return RewardRedemption::create([
                'tenant_id'      => $tenantId,
                'branch_id'      => $branchId,
                'user_id'        => $userId,
                'reward_item_id' => $reward->id,
                'points_used'    => $reward->points_required,
                'status'         => 'pending',
            ]);
        });
    }
}
