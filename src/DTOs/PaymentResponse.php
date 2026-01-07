<?php

namespace Dawilly\Dawilly\DTOs;

class PaymentResponse
{
    public ?string $id;
    public ?string $status;
    public ?string $channel;
    public ?string $orderReference;
    public ?string $collectedAmount;
    public ?string $collectedCurrency;
    public ?string $createdAt;
    public ?string $clientId;
    public ?string $paymentReference;
    public ?array $customer;
    public ?string $message;
    public ?string $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->channel = $data['channel'] ?? null;
        $this->orderReference = $data['orderReference'] ?? null;
        $this->collectedAmount = $data['collectedAmount'] ?? null;
        $this->collectedCurrency = $data['collectedCurrency'] ?? null;
        $this->createdAt = $data['createdAt'] ?? null;
        $this->clientId = $data['clientId'] ?? null;
        $this->paymentReference = $data['paymentReference'] ?? null;
        $this->customer = $data['customer'] ?? null;
        $this->message = $data['message'] ?? null;
        $this->updatedAt = $data['updatedAt'] ?? null;
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['SUCCESS', 'SETTLED']);
    }

    /**
     * Check if payment is still processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'PROCESSING' || $this->status === 'PENDING';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'FAILED';
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'channel' => $this->channel,
            'orderReference' => $this->orderReference,
            'collectedAmount' => $this->collectedAmount,
            'collectedCurrency' => $this->collectedCurrency,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'clientId' => $this->clientId,
            'paymentReference' => $this->paymentReference,
            'customer' => $this->customer,
            'message' => $this->message,
        ];
    }

    /**
     * Create from API response
     */
    public static function fromResponse(array $response): self
    {
        return new self($response);
    }

    /**
     * Create collection from array of responses
     */
    public static function collection(array $responses): array
    {
        return array_map(fn($response) => new self($response), $responses);
    }
}
