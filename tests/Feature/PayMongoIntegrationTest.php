<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\PayMongoCheckoutService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use MongoSprout\PayMongo\Testing\InteractsWithPayMongo;
use MongoSprout\PayMongo\Webhooks\WebhookEvent;
use Tests\TestCase;

class PayMongoIntegrationTest extends TestCase
{
    use InteractsWithPayMongo;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('paymongo_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type', 64);
            $table->boolean('livemode')->default(false);
            $table->unsignedInteger('signature_timestamp')->nullable();
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('order_number');
            $table->decimal('total_amount', 10, 2);
            $table->string('status');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('payment_session_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('method');
            $table->string('reference_no')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('paymongo_webhook_events');

        parent::tearDown();
    }

    public function test_signed_test_webhook_is_accepted_on_the_api_route(): void
    {
        $this->fakePayMongo();
        config()->set('paymongo.webhook.path', 'api/paymongo/webhook');

        $this->paymongoWebhook('payment.paid')
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertSame(WebhookEvent::STATUS_PROCESSED, WebhookEvent::sole()->status);
    }

    public function test_checkout_session_paid_webhook_marks_order_and_payment_paid(): void
    {
        $this->fakePayMongo();
        config()->set('paymongo.webhook.path', 'api/paymongo/webhook');

        $order = Order::withoutEvents(fn (): Order => Order::create([
            'tenant_id' => 9,
            'branch_id' => null,
            'user_id' => 2,
            'order_number' => 'ORD-TEST-PAID',
            'total_amount' => '200.00',
            'status' => 'pending',
            'payment_method' => 'paymongo',
            'payment_status' => 'pending',
            'payment_session_id' => 'cs_test_paid',
        ]));

        $this->paymongoWebhook(
            'checkout_session.payment.paid',
            [
                'id' => 'cs_test_paid',
                'type' => 'checkout_session',
                'attributes' => ['amount' => 20000, 'currency' => 'PHP'],
            ],
            metadata: ['order_id' => (string) $order->id, 'tenant_id' => '9'],
        )->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'reference_no' => 'cs_test_paid',
            'amount' => '200.00',
        ]);
    }

    public function test_checkout_uses_configured_supported_payment_channels(): void
    {
        $this->fakePayMongo();
        config()->set('services.paymongo.payment_methods', ['gcash', 'paymaya']);

        $order = new Order([
            'order_number' => 'ORD-TEST-001',
            'total_amount' => '150.25',
            'tenant_id' => 1,
            'user_id' => 2,
            'payment_method' => 'paymongo',
        ]);
        $order->setAttribute('id', 7);

        $session = app(PayMongoCheckoutService::class)->createSession($order);

        $this->assertSame('cs_test_1', $session->id());
        Http::assertSent(function (Request $request): bool {
            $attributes = $request->data()['data']['attributes'] ?? [];

            return ($attributes['payment_method_types'] ?? null) === ['gcash', 'paymaya']
                && ($attributes['line_items'][0]['amount'] ?? null) === 15025;
        });
    }

    public function test_checkout_rejects_unsupported_payment_channels(): void
    {
        $this->fakePayMongo();
        config()->set('services.paymongo.payment_methods', ['cash']);

        $order = new Order(['order_number' => 'ORD-TEST-002', 'total_amount' => '10.00']);

        $this->expectException(InvalidArgumentException::class);

        app(PayMongoCheckoutService::class)->createSession($order);
    }
}
