<?php

namespace Dawilly\Dawilly\Tests\Unit;

use Dawilly\Dawilly\Middleware\VerifyClickpesaSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class VerifyClickpesaSignatureTest extends TestCase
{
    /** @test */
    public function it_allows_request_when_signature_verification_disabled()
    {
        config(['clickpesa.verify_signature' => false]);

        $middleware = new VerifyClickpesaSignature();
        $request = Request::create('/clickpesa/callback', 'POST', ['test' => 'data']);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_request_without_signature()
    {
        config(['clickpesa.verify_signature' => true]);
        config(['clickpesa.api_key' => 'test_api_key']);

        $middleware = new VerifyClickpesaSignature();
        $request = Request::create('/clickpesa/callback', 'POST', ['test' => 'data']);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Signature required', $data['error']);
    }

    /** @test */
    public function it_rejects_request_with_invalid_signature()
    {
        config(['clickpesa.verify_signature' => true]);
        config(['clickpesa.api_key' => 'test_api_key']);

        $middleware = new VerifyClickpesaSignature();
        
        $payload = json_encode(['test' => 'data']);
        $request = Request::create('/clickpesa/callback', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Clickpesa-Signature', 'invalid_signature');

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid signature', $data['error']);
    }

    /** @test */
    public function it_accepts_request_with_valid_signature()
    {
        config(['clickpesa.verify_signature' => true]);
        config(['clickpesa.api_key' => 'test_api_key']);

        $middleware = new VerifyClickpesaSignature();
        
        $payload = json_encode(['test' => 'data']);
        $validSignature = hash_hmac('sha256', $payload, 'test_api_key', false);
        
        $request = Request::create('/clickpesa/callback', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Clickpesa-Signature', $validSignature);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
    }

    protected function getPackageProviders($app)
    {
        return ['Dawilly\Dawilly\ClickpesaServiceProvider'];
    }
}
