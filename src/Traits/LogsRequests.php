<?php

namespace Dawilly\Dawilly\Traits;

use Illuminate\Support\Facades\Log;

trait LogsRequests
{
    /**
     * Log API request
     */
    protected function logRequest(string $method, string $endpoint, array $data = []): void
    {
        if (!config('clickpesa.logging.enabled', true)) {
            return;
        }

        Log::channel(config('clickpesa.logging.channel', 'stack'))->info('Clickpesa API Request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $this->sanitizeData($data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log API response
     */
    protected function logResponse(string $method, string $endpoint, array $response, bool $success = true): void
    {
        if (!config('clickpesa.logging.enabled', true)) {
            return;
        }

        $level = $success ? 'info' : 'warning';
        
        Log::channel(config('clickpesa.logging.channel', 'stack'))->$level('Clickpesa API Response', [
            'method' => $method,
            'endpoint' => $endpoint,
            'response' => $this->sanitizeResponse($response),
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log error
     */
    protected function logError(string $message, array $context = []): void
    {
        if (!config('clickpesa.logging.enabled', true)) {
            return;
        }

        Log::channel(config('clickpesa.logging.channel', 'stack'))->error('Clickpesa Error: ' . $message, $context);
    }

    /**
     * Sanitize sensitive data before logging
     */
    protected function sanitizeData(array $data): array
    {
        $sensitive_keys = ['api_key', 'client_id', 'token', 'Authorization', 'password'];
        
        return array_map(function ($value) use ($sensitive_keys) {
            if (is_array($value)) {
                return $this->sanitizeData($value);
            }
            
            foreach ($sensitive_keys as $key) {
                if (strpos(strtolower($key), strtolower($key)) !== false) {
                    return '***REDACTED***';
                }
            }
            
            return $value;
        }, $data);
    }

    /**
     * Sanitize response data before logging
     */
    protected function sanitizeResponse(array $response): array
    {
        return $this->sanitizeData($response);
    }
}
