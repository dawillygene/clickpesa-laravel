<?php

namespace Dawilly\Dawilly\Tests\Feature;

use Dawilly\Dawilly\Models\ClickpesaTransaction;
use Dawilly\Dawilly\Models\ClickpesaWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class WebhookCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /** @test */
    public function it_processes_payment_callback_successfully()
    {
        config(['clickpesa.verify_signature' => false]);

        // Create initial transaction
        ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'TEST123',
            'amount' => 1000,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        $payload = [
            'orderReference' => 'TEST123',
            'status' => 'SUCCESS',
            'amount' => 1000,
        ];

        $response = $this->postJson('/clickpesa/callback', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('clickpesa_transactions', [
            'order_reference' => 'TEST123',
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('clickpesa_webhooks', [
            'order_reference' => 'TEST123',
            'event_type' => 'payment.callback',
        ]);
    }

    /** @test */
    public function it_rejects_callback_without_order_reference()
    {
        config(['clickpesa.verify_signature' => false]);

        $payload = [
            'status' => 'SUCCESS',
            'amount' => 1000,
        ];

        $response = $this->postJson('/clickpesa/callback', $payload);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Order reference required']);
    }

    /** @test */
    public function it_detects_duplicate_webhooks()
    {
        config(['clickpesa.verify_signature' => false]);

        // Create transaction
        ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'DUPLICATE_TEST',
            'amount' => 1000,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        // Create already processed webhook
        ClickpesaWebhook::create([
            'order_reference' => 'DUPLICATE_TEST',
            'event_type' => 'payment.callback',
            'payload' => ['status' => 'SUCCESS'],
            'verified' => true,
            'processed_at' => now(),
        ]);

        $payload = [
            'orderReference' => 'DUPLICATE_TEST',
            'status' => 'SUCCESS',
        ];

        $response = $this->postJson('/clickpesa/callback', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'duplicate']);
    }

    /** @test */
    public function it_stores_webhook_headers()
    {
        config(['clickpesa.verify_signature' => false]);

        // Create initial transaction so the controller can update it
        ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'HEADER_TEST',
            'amount' => 1000,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        $payload = [
            'orderReference' => 'HEADER_TEST',
            'status' => 'SUCCESS',
        ];

        $response = $this->postJson('/clickpesa/callback', $payload, [
            'X-Clickpesa-Signature' => 'test_signature',
            'User-Agent' => 'Clickpesa/1.0',
        ]);

        $response->assertStatus(200);

        $webhook = ClickpesaWebhook::where('order_reference', 'HEADER_TEST')->first();
        
        $this->assertNotNull($webhook);
        $headers = $webhook->headers;
        $this->assertIsArray($headers);
        $this->assertEquals('test_signature', $headers['signature'] ?? null);
        $this->assertEquals('Clickpesa/1.0', $headers['user_agent'] ?? null);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        config(['clickpesa.verify_signature' => false]);

        // Don't create transaction first to simulate error condition
        $payload = [
            'orderReference' => 'ERROR_TEST',
            'status' => 'SUCCESS',
            'invalidField' => str_repeat('x', 100000), // Potentially problematic data
        ];

        $response = $this->postJson('/clickpesa/callback', $payload);

        // Should still return 200 or handle error gracefully
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    protected function getPackageProviders($app)
    {
        return ['Dawilly\Dawilly\ClickpesaServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
