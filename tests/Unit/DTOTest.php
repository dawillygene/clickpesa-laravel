<?php

namespace Dawilly\Dawilly\Tests\Unit;

use Dawilly\Dawilly\DTOs\PaymentResponse;
use Dawilly\Dawilly\DTOs\PayoutResponse;
use Dawilly\Dawilly\DTOs\PreviewResponse;
use Orchestra\Testbench\TestCase;

class DTOTest extends TestCase
{
    /** @test */
    public function payment_response_checks_status_correctly()
    {
        $successResponse = PaymentResponse::fromResponse([
            'id' => 'pay_123',
            'status' => 'SUCCESS',
            'orderReference' => 'TEST123',
        ]);

        $this->assertTrue($successResponse->isSuccessful());
        $this->assertFalse($successResponse->isProcessing());
        $this->assertFalse($successResponse->isFailed());

        $processingResponse = PaymentResponse::fromResponse([
            'id' => 'pay_124',
            'status' => 'PENDING',
            'orderReference' => 'TEST124',
        ]);

        $this->assertFalse($processingResponse->isSuccessful());
        $this->assertTrue($processingResponse->isProcessing());
    }

    /** @test */
    public function payout_response_calculates_amounts_correctly()
    {
        $response = PayoutResponse::fromResponse([
            'id' => 'payout_123',
            'amount' => '1050.00',
            'fee' => '50.00',
            'order' => ['amount' => '1000.00'],
            'exchanged' => true,
            'exchange' => [
                'rate' => 2500,
                'sourceCurrency' => 'USD',
                'targetCurrency' => 'TZS',
            ],
        ]);

        $this->assertEquals(1050.00, $response->getTotalCost());
        $this->assertEquals(50.00, $response->getFeeAmount());
        $this->assertEquals(1000.00, $response->getPayoutAmount());
        $this->assertTrue($response->hasExchange());
        $this->assertEquals(2500, $response->getExchangeRate());
    }

    /** @test */
    public function preview_response_checks_availability()
    {
        $response = PreviewResponse::fromResponse([
            'activeMethods' => [
                ['name' => 'MPESA', 'status' => 'AVAILABLE'],
                ['name' => 'TIGO PESA', 'status' => 'AVAILABLE'],
            ],
            'balance' => 5000,
            'amount' => 1000,
        ]);

        $this->assertTrue($response->hasAvailableMethods());
        $this->assertCount(2, $response->getAvailableMethods());
        $this->assertEquals('MPESA', $response->getPreferredMethod());
        $this->assertTrue($response->hasSufficientBalance());
        $this->assertEquals(4000, $response->getRemainingBalance());
    }

    /** @test */
    public function preview_response_detects_insufficient_balance()
    {
        $response = PreviewResponse::fromResponse([
            'activeMethods' => [
                ['name' => 'MPESA', 'status' => 'AVAILABLE'],
            ],
            'balance' => 500,
            'amount' => 1000,
        ]);

        $this->assertFalse($response->hasSufficientBalance());
        $this->assertEquals(-500, $response->getRemainingBalance());
    }

    /** @test */
    public function dtos_convert_to_array()
    {
        $paymentResponse = PaymentResponse::fromResponse([
            'id' => 'pay_123',
            'status' => 'SUCCESS',
        ]);

        $array = $paymentResponse->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('pay_123', $array['id']);
        $this->assertEquals('SUCCESS', $array['status']);
    }

    /** @test */
    public function dtos_create_collections()
    {
        $data = [
            ['id' => 'pay_1', 'status' => 'SUCCESS'],
            ['id' => 'pay_2', 'status' => 'PENDING'],
        ];

        $collection = PaymentResponse::collection($data);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(PaymentResponse::class, $collection[0]);
        $this->assertTrue($collection[0]->isSuccessful());
        $this->assertTrue($collection[1]->isProcessing());
    }
}
