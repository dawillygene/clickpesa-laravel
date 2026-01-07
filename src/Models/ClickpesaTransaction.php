<?php

namespace Dawilly\Dawilly\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClickpesaTransaction extends Model
{
    protected $fillable = [
        'type',
        'channel',
        'order_reference',
        'amount',
        'currency',
        'status',
        'reference',
        'description',
        'account_details',
        'metadata',
        'fee',
        'fee_bearer',
        'exchanged',
        'exchange_details',
        'channel_provider',
        'response_code',
        'response_message',
        'request_payload',
        'response_payload',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'exchanged' => 'boolean',
        'account_details' => 'array',
        'metadata' => 'array',
        'exchange_details' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get webhooks associated with this transaction
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(ClickpesaWebhook::class, 'order_reference', 'order_reference');
    }

    /**
     * Scope for payment transactions
     */
    public function scopePayments($query)
    {
        return $query->where('type', 'payment');
    }

    /**
     * Scope for payout transactions
     */
    public function scopePayouts($query)
    {
        return $query->where('type', 'payout');
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['successful', 'success']);
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing', 'authorized']);
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'reversed']);
    }

    /**
     * Check if transaction is a payment (incoming)
     */
    public function isPayment(): bool
    {
        return $this->type === 'payment';
    }

    /**
     * Check if transaction is a payout (outgoing)
     */
    public function isPayout(): bool
    {
        return $this->type === 'payout';
    }

    /**
     * Get total amount including fee
     */
    public function getTotalAmount(): float
    {
        return $this->amount + ($this->fee ?? 0);
    }

    /**
     * Get exchange rate if applicable
     */
    public function getExchangeRate(): ?float
    {
        if (!$this->exchanged || !$this->exchange_details) {
            return null;
        }

        return $this->exchange_details['rate'] ?? null;
    }
}
