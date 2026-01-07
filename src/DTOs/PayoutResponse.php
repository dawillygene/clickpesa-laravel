<?php

namespace Dawilly\Dawilly\DTOs;

class PayoutResponse
{
    public ?string $id;
    public ?string $status;
    public ?string $orderReference;
    public ?string $amount;
    public ?string $currency;
    public ?string $fee;
    public ?string $channel;
    public ?string $channelProvider;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?array $beneficiary;
    public ?array $exchange;
    public bool $exchanged;
    public ?string $transferType;
    public ?string $clientId;
    public ?string $message;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->orderReference = $data['orderReference'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->currency = $data['currency'] ?? null;
        $this->fee = $data['fee'] ?? null;
        $this->channel = $data['channel'] ?? null;
        $this->channelProvider = $data['channelProvider'] ?? null;
        $this->createdAt = $data['createdAt'] ?? null;
        $this->updatedAt = $data['updatedAt'] ?? null;
        $this->beneficiary = $data['beneficiary'] ?? null;
        $this->exchange = $data['exchange'] ?? null;
        $this->exchanged = $data['exchanged'] ?? false;
        $this->transferType = $data['transferType'] ?? null;
        $this->clientId = $data['clientId'] ?? null;
        $this->message = $data['message'] ?? null;
    }

    /**
     * Check if payout is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'SUCCESS';
    }

    /**
     * Check if payout is authorized but not yet completed
     */
    public function isAuthorized(): bool
    {
        return $this->status === 'AUTHORIZED';
    }

    /**
     * Check if payout was reversed
     */
    public function isReversed(): bool
    {
        return $this->status === 'REVERSED';
    }

    /**
     * Get total cost including fee
     */
    public function getTotalCost(): float
    {
        return (float)$this->amount;
    }

    /**
     * Get fee amount
     */
    public function getFeeAmount(): float
    {
        return (float)$this->fee;
    }

    /**
     * Get payout amount (total - fee)
     */
    public function getPayoutAmount(): float
    {
        return $this->getTotalCost() - $this->getFeeAmount();
    }

    /**
     * Check if currency exchange was applied
     */
    public function hasExchange(): bool
    {
        return $this->exchanged && !empty($this->exchange);
    }

    /**
     * Get exchange rate if applicable
     */
    public function getExchangeRate(): ?float
    {
        return $this->exchange['rate'] ?? null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'orderReference' => $this->orderReference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'fee' => $this->fee,
            'channel' => $this->channel,
            'channelProvider' => $this->channelProvider,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'beneficiary' => $this->beneficiary,
            'exchange' => $this->exchange,
            'exchanged' => $this->exchanged,
            'transferType' => $this->transferType,
            'clientId' => $this->clientId,
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
