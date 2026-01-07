<?php

namespace Dawilly\Dawilly\DTOs;

class PreviewResponse
{
    public ?array $activeMethods;
    public ?array $sender;
    public ?array $receiver;
    public ?float $amount;
    public ?float $balance;
    public ?string $channelProvider;
    public ?float $fee;
    public bool $exchanged;
    public ?array $exchange;
    public ?array $order;
    public ?string $payoutFeeBearer;
    public ?string $message;

    public function __construct(array $data = [])
    {
        $this->activeMethods = $data['activeMethods'] ?? null;
        $this->sender = $data['sender'] ?? null;
        $this->receiver = $data['receiver'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->balance = $data['balance'] ?? null;
        $this->channelProvider = $data['channelProvider'] ?? null;
        $this->fee = $data['fee'] ?? null;
        $this->exchanged = $data['exchanged'] ?? false;
        $this->exchange = $data['exchange'] ?? null;
        $this->order = $data['order'] ?? null;
        $this->payoutFeeBearer = $data['payoutFeeBearer'] ?? null;
        $this->message = $data['message'] ?? null;
    }

    /**
     * Get available payment/payout methods
     */
    public function getAvailableMethods(): array
    {
        return $this->activeMethods ?? [];
    }

    /**
     * Check if any method is available
     */
    public function hasAvailableMethods(): bool
    {
        return !empty($this->activeMethods) && count($this->activeMethods) > 0;
    }

    /**
     * Get the first available method
     */
    public function getPreferredMethod(): ?array
    {
        return $this->activeMethods[0] ?? null;
    }

    /**
     * Calculate net amount (total - fee)
     */
    public function getNetAmount(): float
    {
        return ($this->amount ?? 0) - ($this->fee ?? 0);
    }

    /**
     * Get fee percentage
     */
    public function getFeePercentage(): float
    {
        if (!$this->amount || $this->amount == 0) {
            return 0;
        }
        return (($this->fee ?? 0) / $this->amount) * 100;
    }

    /**
     * Check if account has sufficient balance
     */
    public function hasSufficientBalance(): bool
    {
        return ($this->balance ?? 0) >= ($this->amount ?? 0);
    }

    /**
     * Get remaining balance after transaction
     */
    public function getRemainingBalance(): float
    {
        return ($this->balance ?? 0) - ($this->amount ?? 0);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'activeMethods' => $this->activeMethods,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'amount' => $this->amount,
            'balance' => $this->balance,
            'channelProvider' => $this->channelProvider,
            'fee' => $this->fee,
            'exchanged' => $this->exchanged,
            'exchange' => $this->exchange,
            'order' => $this->order,
            'payoutFeeBearer' => $this->payoutFeeBearer,
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
}
