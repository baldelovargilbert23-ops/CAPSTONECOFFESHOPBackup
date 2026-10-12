<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render_for_each_seeded_role_without_server_errors(): void
    {
        $this->seed();
        $this->withoutExceptionHandling();

        $pathsByRole = [
            'admin' => [
                '/admin', '/admin/analytics', '/admin/tenants', '/admin/manage-tenant',
                '/admin/manage-customer', '/admin/theme-settings', '/admin/announcements',
                '/admin/tenant-approvals', '/admin/branch-approvals',
            ],
            'owner' => [
                '/owner', '/owner/products', '/owner/inventory', '/owner/payments',
                '/owner/ratings', '/owner/analytics', '/owner/staff', '/owner/branches',
                '/owner/branch-dashboard', '/owner/branch-staff', '/owner/branch-manage',
                '/owner/branch-analytics', '/owner/settings', '/owner/loyalty',
            ],
            'branch_manager' => [
                '/branch/dashboard', '/branch/operations', '/branch/product', '/branch/inventory',
                '/branch/staff', '/branch/ratings', '/branch/analytics', '/branch/settings',
            ],
            'cashier' => ['/branch/cashier', '/cashier'],
            'barista' => ['/branch/barista'],
            'customer' => [
                '/customer/dashboard', '/customer/orders', '/customer/ratings', '/customer/rewards',
                '/notifications', '/shop-list', '/customer/shop/1', '/customer/shop/menu/1/0',
            ],
        ];

        foreach ($pathsByRole as $role => $paths) {
            $user = User::query()->where('role', $role)->where('is_active', true)->firstOrFail();

            foreach ($paths as $path) {
                $response = $this->actingAs($user)->get($path);
                if ($response->status() !== 200) {
                    $plainBody = html_entity_decode(strip_tags($response->getContent()));
                    preg_match('/(SQLSTATE\[[^\]]+\][^\r\n]{0,180}|Call to undefined[^.\r\n]{0,180}|View \[[^\]]+\] not found|Undefined variable \$[A-Za-z_][A-Za-z0-9_]*)/', $plainBody, $diagnostic);
                    $this->fail("{$role} page {$path} returned HTTP {$response->status()}: " . ($diagnostic[0] ?? 'render failed'));
                }
            }
        }
    }
}
