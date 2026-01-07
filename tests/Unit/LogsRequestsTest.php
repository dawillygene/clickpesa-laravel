<?php

namespace Dawilly\Dawilly\Tests\Unit;

use Dawilly\Dawilly\Traits\LogsRequests;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class LogsRequestsTest extends TestCase
{
    use LogsRequests;

    /** @test */
    public function it_sanitizes_sensitive_data()
    {
        $data = [
            'api_key' => 'secret_key_123',
            'client_id' => 'client_456',
            'token' => 'bearer_token_789',
            'amount' => 1000,
            'phone' => '255712345678',
        ];

        $sanitized = $this->sanitizeData($data);

        $this->assertEquals('***REDACTED***', $sanitized['api_key']);
        $this->assertEquals('***REDACTED***', $sanitized['client_id']);
        $this->assertEquals('***REDACTED***', $sanitized['token']);
        $this->assertEquals(1000, $sanitized['amount']);
        $this->assertEquals('255712345678', $sanitized['phone']);
    }

    /** @test */
    public function it_sanitizes_nested_arrays()
    {
        $data = [
            'request' => [
                'headers' => [
                    'Authorization' => 'Bearer secret_token',
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    'amount' => 1000,
                ],
            ],
        ];

        $sanitized = $this->sanitizeData($data);

        $this->assertEquals('***REDACTED***', $sanitized['request']['headers']['Authorization']);
        $this->assertEquals('application/json', $sanitized['request']['headers']['Content-Type']);
        $this->assertEquals(1000, $sanitized['request']['body']['amount']);
    }

    /** @test */
    public function it_identifies_sensitive_keys()
    {
        $this->assertTrue($this->isSensitiveKey('api_key', ['api_key', 'token']));
        $this->assertTrue($this->isSensitiveKey('API-KEY', ['api_key', 'token']));
        $this->assertTrue($this->isSensitiveKey('client_id', ['client_id']));
        $this->assertTrue($this->isSensitiveKey('Authorization', ['authorization']));
        $this->assertFalse($this->isSensitiveKey('amount', ['api_key', 'token']));
    }

    /** @test */
    public function it_logs_request_when_enabled()
    {
        config(['clickpesa.logging.enabled' => true]);
        
        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->once()
            ->with('Clickpesa API Request', \Mockery::type('array'));

        $this->logRequest('POST', '/test-endpoint', ['amount' => 1000]);
    }

    /** @test */
    public function it_does_not_log_when_disabled()
    {
        config(['clickpesa.logging.enabled' => false]);
        
        Log::shouldReceive('channel')->never();
        Log::shouldReceive('info')->never();

        $this->logRequest('POST', '/test-endpoint', ['amount' => 1000]);
    }

    protected function getPackageProviders($app)
    {
        return ['Dawilly\Dawilly\ClickpesaServiceProvider'];
    }
}
