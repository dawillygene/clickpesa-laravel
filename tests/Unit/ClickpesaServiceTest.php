<?php

namespace Dawilly\Dawilly\Tests\Unit;

use Dawilly\Dawilly\Services\ClickpesaService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class ClickpesaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock cache repository
        $cacheRepository = \Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
        $cacheRepository->shouldReceive('get')->andReturn(null);
        $cacheRepository->shouldReceive('put')->andReturn(true);
        $cacheRepository->shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });
        
        // Mock Cache facade to return the mock repository
        Cache::shouldReceive('store')->andReturn($cacheRepository);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
    }

    /** @test */
    public function it_generates_token_successfully()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'token' => 'Bearer test_token_123'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new ClickpesaService('test_key', 'test_client', 'sandbox');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $token = $service->generateToken();

        $this->assertEquals('Bearer test_token_123', $token);
    }

    /** @test */
    public function it_caches_generated_token()
    {
        config(['clickpesa.cache.enabled' => true]);
        
        // Need two responses since cache will check and possibly call twice
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'token' => 'Bearer cached_token'
            ])),
            new Response(200, [], json_encode([
                'success' => true,
                'token' => 'Bearer cached_token'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new ClickpesaService('test_key', 'test_client', 'sandbox');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        // First call should hit API
        $token1 = $service->generateToken();
        
        // Second call should use cache (no new request)
        $token2 = $service->generateToken();

        $this->assertEquals($token1, $token2);
        $this->assertEquals('Bearer cached_token', $token2);
    }

    /** @test */
    public function it_returns_null_on_token_generation_failure()
    {
        $mock = new MockHandler([
            new Response(401, [], json_encode([
                'message' => 'Unauthorized'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new ClickpesaService('invalid_key', 'invalid_client', 'sandbox');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $token = $service->generateToken();

        $this->assertNull($token);
    }

    /** @test */
    public function it_previews_ussd_push_request()
    {
        config(['clickpesa.cache.preview_enabled' => false]);
        
        $mock = new MockHandler([
            // Token generation
            new Response(200, [], json_encode([
                'success' => true,
                'token' => 'Bearer test_token'
            ])),
            // Preview request
            new Response(200, [], json_encode([
                'activeMethods' => [
                    ['name' => 'MPESA', 'status' => 'AVAILABLE']
                ]
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new ClickpesaService('test_key', 'test_client', 'sandbox');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $response = $service->previewUssdPushRequest([
            'amount' => 1000,
            'phoneNumber' => '255712345678',
            'currency' => 'TZS',
            'orderReference' => 'TEST123'
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('activeMethods', $response);
    }

    /** @test */
    public function it_initiates_ussd_push_request()
    {
        $mock = new MockHandler([
            // Token generation
            new Response(200, [], json_encode([
                'success' => true,
                'token' => 'Bearer test_token'
            ])),
            // Initiate request
            new Response(200, [], json_encode([
                'id' => 'payment_123',
                'status' => 'PENDING',
                'orderReference' => 'TEST123'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new ClickpesaService('test_key', 'test_client', 'sandbox');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $response = $service->initiateUssdPushRequest([
            'amount' => 1000,
            'phoneNumber' => '255712345678',
            'currency' => 'TZS',
            'orderReference' => 'TEST123'
        ]);

        $this->assertIsArray($response);
        $this->assertEquals('payment_123', $response['id']);
        $this->assertEquals('PENDING', $response['status']);
    }

    /** @test */
    public function it_queries_payment_status()
    {
        $mock = new MockHandler([
            // Token generation
            new Response(200, [], json_encode([
                'success' => true,
                'token' => 'Bearer test_token'
            ])),
            // Query status
            new Response(200, [], json_encode([
                'id' => 'payment_123',
                'status' => 'SUCCESS',
                'orderReference' => 'TEST123'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new ClickpesaService('test_key', 'test_client', 'sandbox');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $response = $service->queryPaymentStatus('TEST123');

        $this->assertIsArray($response);
        $this->assertEquals('SUCCESS', $response['status']);
    }

    protected function getPackageProviders($app)
    {
        return ['Dawilly\Dawilly\ClickpesaServiceProvider'];
    }
}
