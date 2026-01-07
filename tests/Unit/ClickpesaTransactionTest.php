<?php

namespace Dawilly\Dawilly\Tests\Unit;

use Dawilly\Dawilly\Models\ClickpesaTransaction;
use Dawilly\Dawilly\Models\ClickpesaWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class ClickpesaTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /** @test */
    public function it_can_create_payment_transaction()
    {
        $transaction = ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'TEST123',
            'amount' => 1000.00,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('clickpesa_transactions', [
            'order_reference' => 'TEST123',
            'type' => 'payment',
        ]);
    }

    /** @test */
    public function it_can_create_payout_transaction()
    {
        $transaction = ClickpesaTransaction::create([
            'type' => 'payout',
            'channel' => 'mobile_money',
            'order_reference' => 'PAYOUT123',
            'amount' => 500.00,
            'currency' => 'TZS',
            'status' => 'authorized',
        ]);

        $this->assertTrue($transaction->isPayout());
        $this->assertFalse($transaction->isPayment());
    }

    /** @test */
    public function it_scopes_payments_correctly()
    {
        ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'PAY1',
            'amount' => 1000,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        ClickpesaTransaction::create([
            'type' => 'payout',
            'channel' => 'mobile_money',
            'order_reference' => 'PAYOUT1',
            'amount' => 500,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        $payments = ClickpesaTransaction::payments()->get();

        $this->assertCount(1, $payments);
        $this->assertEquals('payment', $payments->first()->type);
    }

    /** @test */
    public function it_checks_transaction_status_correctly()
    {
        $successfulTxn = ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'card',
            'order_reference' => 'SUCCESS1',
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'successful',
        ]);

        $pendingTxn = ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'PENDING1',
            'amount' => 1000,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        $this->assertTrue($successfulTxn->isSuccessful());
        $this->assertFalse($successfulTxn->isPending());
        
        $this->assertTrue($pendingTxn->isPending());
        $this->assertFalse($pendingTxn->isSuccessful());
    }

    /** @test */
    public function it_calculates_total_amount_with_fee()
    {
        $transaction = ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'FEE_TEST',
            'amount' => 1000.00,
            'currency' => 'TZS',
            'status' => 'successful',
            'fee' => 50.00,
        ]);

        $this->assertEquals(1050.00, $transaction->getTotalAmount());
    }

    /** @test */
    public function it_has_webhooks_relationship()
    {
        $transaction = ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'WEBHOOK_TEST',
            'amount' => 1000,
            'currency' => 'TZS',
            'status' => 'pending',
        ]);

        $webhook = ClickpesaWebhook::create([
            'order_reference' => 'WEBHOOK_TEST',
            'event_type' => 'payment.success',
            'payload' => ['status' => 'SUCCESS'],
            'verified' => true,
        ]);

        $this->assertCount(1, $transaction->webhooks);
        $this->assertEquals('payment.success', $transaction->webhooks->first()->event_type);
    }

    /** @test */
    public function it_stores_json_fields_correctly()
    {
        $transaction = ClickpesaTransaction::create([
            'type' => 'payment',
            'channel' => 'ussd_push',
            'order_reference' => 'JSON_TEST',
            'amount' => 1000,
            'currency' => 'TZS',
            'status' => 'pending',
            'metadata' => ['user_id' => 123, 'invoice_id' => 'INV-001'],
            'account_details' => ['phone' => '255712345678'],
        ]);

        $this->assertIsArray($transaction->metadata);
        $this->assertEquals(123, $transaction->metadata['user_id']);
        $this->assertEquals('255712345678', $transaction->account_details['phone']);
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
