<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\CustomerPoint;
use App\Models\Inventory;
use App\Models\LoyaltySetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ────────────────────────────────────────────────────
            // 1. ADMIN
            // ────────────────────────────────────────────────────
            $admin = User::updateOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'name'              => 'Super Admin',
                    'password'          => Hash::make('admin123'),
                    'role'              => 'admin',
                    'tenant_id'         => null,
                    'branch_id'         => null,
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]
            );

            // ────────────────────────────────────────────────────
            // 2. TENANT — this IS the shop (own store)
            // ────────────────────────────────────────────────────
            $tenant = Tenant::create([
                'name'           => 'Kapé Victorias',
                'code'           => 'KPV',
                'slug'           => 'kape-victorias',
                'email'          => 'hello@kapevictorias.test',
                'contact_number' => '+63 912 345 6789',
                'address'        => '123 Rizal Street, Victorias City',
                'business_type'  => 'sole_proprietor',
                'owner_name'     => 'Maria Santos',
                'owner_phone'    => '+63 912 345 6789',
                'status'         => 'active',   // auto-approved in seeder
                'approved_at'    => now(),
                'approved_by'    => $admin->id,
                'tagline'        => 'Handcrafted coffee from the heart of Victorias.',
                'description'    => 'A cozy neighborhood coffee shop serving freshly brewed coffee.',
            ]);

            // Owner user — belongs to the tenant's own store (branch_id = null)
            $owner = User::create([
                'name'              => 'Maria Santos',
                'email'             => 'owner@gmail.com',
                'password'          => Hash::make('owner123'),
                'role'              => 'owner',
                'tenant_id'         => $tenant->id,
                'branch_id'         => null,   // ✅ owner is at the tenant's own store
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            // ────────────────────────────────────────────────────
            // 3. EXTRA BRANCH (pending for testing approvals)
            // ────────────────────────────────────────────────────
            $downtownBranch = Branch::create([
                'tenant_id'       => $tenant->id,
                'name'            => 'Kapé Victorias — Downtown',
                'address'         => '456 Quezon Avenue, Victorias City',
                'barangay'        => 'Poblacion',
                'city'            => 'Victorias',
                'province'        => 'Negros Occidental',
                'contact_number'  => '+63 912 345 6790',
                'email'           => 'downtown@kapevictorias.test',
                'is_active'       => true,
                'approval_status' => 'pending',
                'created_by'      => $owner->id,
            ]);

            // ────────────────────────────────────────────────────
            // 4. BRANCH STAFF (all at the tenant's own store → branch_id = null)
            // ────────────────────────────────────────────────────
            $manager = User::create([
                'name'              => 'Juan Dela Cruz',
                'email'             => 'manager@gmail.com',
                'password'          => Hash::make('manager123'),
                'role'              => 'branch_manager',
                'tenant_id'         => $tenant->id,
                'branch_id'         => null,   // ✅ at the tenant's own store
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            $cashier = User::create([
                'name'              => 'Ana Reyes',
                'email'             => 'cashier@gmail.com',
                'password'          => Hash::make('cashier123'),
                'role'              => 'cashier',
                'tenant_id'         => $tenant->id,
                'branch_id'         => null,   // ✅ at the tenant's own store
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            $barista = User::create([
                'name'              => 'Carlos Mendez',
                'email'             => 'barista@gmail.com',
                'password'          => Hash::make('barista123'),
                'role'              => 'barista',
                'tenant_id'         => $tenant->id,
                'branch_id'         => null,   // ✅ at the tenant's own store
                'is_active'         => true,
                'is_on_shift'       => true,
                'shift_started_at'  => now()->subHours(2),
                'email_verified_at' => now(),
            ]);

            $customer = User::create([
                'name'              => 'Sofia Garcia',
                'email'             => 'customer@gmail.com',
                'password'          => Hash::make('customer123'),
                'role'              => 'customer',
                'tenant_id'         => $tenant->id,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            // ────────────────────────────────────────────────────
            // 5. LOYALTY + CUSTOMER POINTS
            // ────────────────────────────────────────────────────
            LoyaltySetting::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'is_enabled'           => true,
                    'points_per_peso'      => 1,
                    'points_per_order'     => 10,
                    'points_expiry_days'   => 365,
                    'min_points_to_redeem' => 100,
                    'points_value_in_peso' => 1,
                ]
            );

            CustomerPoint::create([
                'tenant_id'       => $tenant->id,
                'user_id'         => $customer->id,
                'points'          => 250,
                'lifetime_points' => 500,
                'points_used'     => 250,
            ]);

            // ────────────────────────────────────────────────────
            // 6. CATEGORIES
            // ────────────────────────────────────────────────────
            $categories = [
                'Espresso'    => Category::create(['tenant_id' => $tenant->id, 'cat_name' => 'Espresso',    'type' => 'finished']),
                'Iced Coffee' => Category::create(['tenant_id' => $tenant->id, 'cat_name' => 'Iced Coffee', 'type' => 'finished']),
                'Pastries'    => Category::create(['tenant_id' => $tenant->id, 'cat_name' => 'Pastries',    'type' => 'finished']),
            ];

            // ────────────────────────────────────────────────────
            // 7. PRODUCTS (tenant-wide)
            // ────────────────────────────────────────────────────
            $products = [
                ['name' => 'Spanish Latte',         'sku' => 'KPV-ESP-0001', 'price' => 180.00, 'cost' => 65.00, 'category_id' => $categories['Espresso']->id],
                ['name' => 'Americano',              'sku' => 'KPV-ESP-0002', 'price' => 120.00, 'cost' => 40.00, 'category_id' => $categories['Espresso']->id],
                ['name' => 'Iced Caramel Macchiato', 'sku' => 'KPV-ICE-0003', 'price' => 200.00, 'cost' => 75.00, 'category_id' => $categories['Iced Coffee']->id],
                ['name' => 'Butter Croissant',       'sku' => 'KPV-PAS-0004', 'price' => 95.00,  'cost' => 35.00, 'category_id' => $categories['Pastries']->id],
                ['name' => 'Blueberry Muffin',       'sku' => 'KPV-PAS-0005', 'price' => 110.00, 'cost' => 42.00, 'category_id' => $categories['Pastries']->id],
            ];

            $createdProducts = collect();
            foreach ($products as $p) {
                $createdProducts->push(
                    Product::create(array_merge($p, [
                        'tenant_id' => $tenant->id,
                        'type'      => 'finished',
                        'unit'      => 'pcs',
                        'is_active' => true,
                    ]))
                );
            }

            // ────────────────────────────────────────────────────
            // 8. INITIAL STOCK at tenant's own store (branch_id = null)
            // ────────────────────────────────────────────────────
            $initialStock = [50, 40, 30, 25, 20];
            foreach ($createdProducts as $i => $product) {
                Inventory::create([
                    'tenant_id'  => $tenant->id,
                    'branch_id'  => null,   // ✅ tenant's own store
                    'product_id' => $product->id,
                    'quantity'   => $initialStock[$i],
                    'min_stock'  => 5,
                    'is_active'  => true,
                ]);
            }

            // ────────────────────────────────────────────────────
            // 9. SAMPLE ORDERS at tenant's own store (last 7 days)
            // ────────────────────────────────────────────────────
            $orderCount = 0;
            for ($d = 6; $d >= 0; $d--) {
                $date = now()->subDays($d);
                $ordersToday = rand(2, 3);

                for ($o = 0; $o < $ordersToday; $o++) {
                    $orderCount++;
                    $product = $createdProducts->random();
                    $qty     = rand(1, 3);
                    $total   = $product->price * $qty;

                    $order = Order::create([
                        'tenant_id'      => $tenant->id,
                        'branch_id'      => null,   // ✅ tenant's own store
                        'user_id'        => $customer->id,
                        'order_number'   => 'ORD-' . str_pad($orderCount, 5, '0', STR_PAD_LEFT),
                        'total_amount'   => $total,
                        'status'         => 'completed',
                        'payment_method' => 'cash',
                        'payment_status' => 'paid',
                        'barista_id'     => $barista->id,
                        'claimed_at'     => $date->copy()->addMinutes(5),
                        'ready_at'       => $date->copy()->addMinutes(10),
                        'created_at'     => $date,
                        'updated_at'     => $date,
                    ]);

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'branch_id'  => null,   // ✅ tenant's own store
                        'product_id' => $product->id,
                        'item_name'  => $product->name,
                        'quantity'   => $qty,
                        'price'      => $product->price,
                        'subtotal'   => $total,
                    ]);

                    Payment::create([
                        'order_id'   => $order->id,
                        'branch_id'  => null,   // ✅ tenant's own store
                        'amount'     => $total,
                        'method'     => 'cash',
                        'paid_at'    => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }

            // ────────────────────────────────────────────────────
            // 10. SAMPLE REVIEWS
            // ────────────────────────────────────────────────────
            Review::create([
                'tenant_id'  => $tenant->id,
                'branch_id'  => null,   // ✅ tenant's own store review
                'product_id' => $createdProducts[0]->id,
                'user_id'    => $customer->id,
                'type'       => 'product',
                'rating'     => 5,
                'comment'    => 'Best Spanish Latte in town! Creamy and perfectly balanced.',
                'is_visible' => true,
            ]);

            Review::create([
                'tenant_id'  => $tenant->id,
                'branch_id'  => null,   // ✅ tenant's own store review
                'product_id' => null,
                'user_id'    => $customer->id,
                'type'       => 'branch',
                'rating'     => 4,
                'comment'    => 'Great ambiance, friendly baristas. Will come back!',
                'is_visible' => true,
            ]);

            // ────────────────────────────────────────────────────
            // SUMMARY
            // ────────────────────────────────────────────────────
            $this->command->info('');
            $this->command->info('═══════════════════════════════════════════════');
            $this->command->info('✅ Seeder complete!');
            $this->command->info('═══════════════════════════════════════════════');
            $this->command->info('');
            $this->command->info('Login credentials:');
            $this->command->info('  Admin:    admin@gmail.com    / admin123');
            $this->command->info('  Owner:    owner@gmail.com    / owner123');
            $this->command->info('  Manager:  manager@gmail.com  / manager123');
            $this->command->info('  Cashier:  cashier@gmail.com  / cashier123');
            $this->command->info('  Barista:  barista@gmail.com  / barista123');
            $this->command->info('  Customer: customer@gmail.com / customer123');
            $this->command->info('');
            $this->command->info('Tenant:         Kapé Victorias (active — IS the own store)');
            $this->command->info('Owner:          at the tenant\'s own store (branch_id = null)');
            $this->command->info('Staff:          manager, cashier, barista at the own store');
            $this->command->info('Extra branch:   Downtown (pending admin approval)');
            $this->command->info('Products:       ' . $createdProducts->count() . ' with stock at the own store');
            $this->command->info('Orders:         ' . $orderCount . ' completed (last 7 days)');
            $this->command->info('');
        });
    }
}