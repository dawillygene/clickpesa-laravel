<?php

namespace Dawilly\Dawilly\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClickpesaWebhook extends Model
{
    protected $fillable = [
        'order_reference',
        'event_type',
        'payload',
        'headers',
        'verified',
        'processed_at',
        'processing_error',
        'retry_count',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'verified' => 'boolean',
        'processed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Get the transaction associated with this webhook
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ClickpesaTransaction::class, 'order_reference', 'order_reference');
    }

    /**
     * Scope for verified webhooks
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope for unverified webhooks
     */
    public function scopeUnverified($query)
    {
        return $query->where('verified', false);
    }

    /**
     * Scope for processed webhooks
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    /**
     * Scope for unprocessed webhooks
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Mark webhook as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark webhook as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verified' => true,
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(string $error = null): void
    {
        $this->increment('retry_count');
        
        if ($error) {
            $this->update(['processing_error' => $error]);
        }
    }

    /**
     * Check if webhook has been processed
     */
    public function isProcessed(): bool
    {
        return !is_null($this->processed_at);
    }

    /**
     * Check if webhook is verified
     */
    public function isVerified(): bool
    {
        return $this->verified === true;
    }
}
