<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentReturnSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('x', 32)));

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->string('payment_status');
        });

        DB::table('orders')->insert([
            'id' => 1, 'user_id' => 10, 'status' => 'pending', 'payment_status' => 'pending',
        ]);
    }

    public function test_cancel_return_is_read_only_even_when_repeated(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 10, 'role' => 'customer']));
        DB::enableQueryLog();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->get('/payment/cancel/1')->assertRedirect(route('customer.orders'))
                ->assertSessionHas('info');
        }

        foreach (DB::getQueryLog() as $query) {
            $this->assertMatchesRegularExpression('/^select\b/i', $query['query']);
        }

        $this->assertDatabaseHas('orders', ['id' => 1, 'status' => 'pending', 'payment_status' => 'pending']);
    }

    public function test_cancel_return_cannot_access_another_customers_order(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 20, 'role' => 'customer']))
            ->get('/payment/cancel/1')->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => 1, 'status' => 'pending']);
    }

    public function test_cancel_return_requires_authentication(): void
    {
        $this->get('/payment/cancel/1')->assertRedirect(route('login'));
    }

    public function test_paid_order_returns_to_success_without_cancellation(): void
    {
        DB::table('orders')->where('id', 1)->update(['payment_status' => 'paid']);

        $this->actingAs((new User)->forceFill(['id' => 10, 'role' => 'customer']))
            ->get('/payment/cancel/1')->assertRedirect(route('payment.success', 1));

        $this->assertDatabaseHas('orders', ['id' => 1, 'status' => 'pending', 'payment_status' => 'paid']);
    }

    public function test_paid_cancelled_order_does_not_loop_between_return_urls(): void
    {
        DB::table('orders')->where('id', 1)->update(['status' => 'cancelled', 'payment_status' => 'paid']);

        $this->actingAs((new User)->forceFill(['id' => 10, 'role' => 'customer']))
            ->get('/payment/success/1')->assertRedirect(route('payment.cancel', 1));
        $this->get('/payment/cancel/1')->assertRedirect(route('customer.orders'));
    }
}
