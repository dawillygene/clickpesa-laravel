<?php

namespace Dawilly\Dawilly\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyClickpesaSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Verify that the callback is from Clickpesa
        $signature = $request->header('X-Clickpesa-Signature');
        $payload = $request->getContent();

        if ($signature && !$this->verifySignature($payload, $signature)) {
            Log::warning('Invalid Clickpesa callback signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    /**
     * Verify the callback signature
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    protected function verifySignature(string $payload, string $signature): bool
    {
        // If checksum verification is enabled in config
        if (!config('clickpesa.verify_signature', false)) {
            return true;
        }

        // Generate expected signature using API key
        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            config('clickpesa.api_key'),
            false
        );

        return hash_equals($expectedSignature, $signature);
    }
}
