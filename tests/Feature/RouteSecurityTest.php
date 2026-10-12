<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('x', 32)));
    }

    public function test_login_limits_an_account_across_addresses_and_case_variants(): void
    {
        $this->mock(LoginController::class)->shouldReceive('login')->times(5)
            ->andReturn(response()->json(['message' => 'Invalid credentials'], 422));

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$attempt])
                ->postJson('/login', ['email' => 'person@example.test', 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.100'])
            ->postJson('/login', ['email' => ' PERSON@EXAMPLE.TEST ', 'password' => 'wrong'])
            ->assertTooManyRequests()->assertHeader('Retry-After');
    }

    public function test_login_limits_password_spraying_from_one_address(): void
    {
        $this->mock(LoginController::class)->shouldReceive('login')->times(30)
            ->andReturn(response()->json(['message' => 'Invalid credentials'], 422));

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->postJson('/login', ['email' => "person{$attempt}@example.test", 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->postJson('/login', ['email' => 'another@example.test', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_email_lookup_rejects_invalid_inputs_before_querying(): void
    {
        // No tables are needed: invalid input must never reach either query.
        foreach ([null, [], 123, 'invalid', str_repeat('a', 245).'@example.test', "a\u{200B}@example.test", "a\0@example.test", '<b>a@example.test</b>'] as $email) {
            $this->postJson('/register/check-email', ['email' => $email])
                ->assertUnprocessable()->assertJsonValidationErrors('email');
        }
    }

    public function test_email_lookup_preserves_tenant_and_user_uniqueness_and_trims_whitespace(): void
    {
        foreach (['tenants', 'users'] as $table) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('email');
            });
        }

        DB::table('tenants')->insert(['email' => 'shop@example.test']);
        DB::table('users')->insert(['email' => 'customer@example.test']);

        foreach (['shop@example.test', 'customer@example.test'] as $email) {
            $this->postJson('/register/check-email', ['email' => '  '.$email.'  '])
                ->assertOk()->assertExactJson(['available' => false]);
        }

        $this->postJson('/register/check-email', ['email' => 'new@example.test'])
            ->assertOk()->assertExactJson(['available' => true]);
    }

    public function test_email_lookup_throttle_expires_and_does_not_block_another_address(): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->postJson('/register/check-email', ['email' => 'invalid'])->assertUnprocessable();
        }

        $this->postJson('/register/check-email', ['email' => 'invalid'])
            ->assertTooManyRequests()->assertHeader('Retry-After');

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->postJson('/register/check-email', ['email' => 'invalid'])->assertUnprocessable();

        $this->travel(61)->seconds();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/register/check-email', ['email' => 'invalid'])->assertUnprocessable();
    }
}
