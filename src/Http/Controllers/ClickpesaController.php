<?php

namespace Dawilly\Dawilly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Dawilly\Dawilly\Events\PaymentReceived;
use Dawilly\Dawilly\Models\ClickpesaWebhook;
use Dawilly\Dawilly\Models\ClickpesaTransaction;

class ClickpesaController extends Controller
{
    public function callback(Request $request)
    {
        $data = $request->all();
        $orderReference = $data['orderReference'] ?? $data['order_reference'] ?? null;
        
        // Validate required data
        if (!$orderReference) {
            Log::warning('Clickpesa callback missing order reference', ['data' => $data]);
            return response()->json(['error' => 'Order reference required'], 400);
        }

        try {
            // Store webhook for audit trail
            $webhook = ClickpesaWebhook::create([
                'order_reference' => $orderReference,
                'event_type' => $data['event'] ?? 'payment.callback',
                'payload' => $data,
                'headers' => [
                    'signature' => $request->header('X-Clickpesa-Signature'),
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip(),
                ],
                'verified' => config('clickpesa.verify_signature', false),
            ]);

            // Check for replay attack (duplicate webhook within 5 minutes)
            $recentWebhook = ClickpesaWebhook::where('order_reference', $orderReference)
                ->where('id', '!=', $webhook->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->whereNotNull('processed_at')
                ->exists();

            if ($recentWebhook) {
                Log::warning('Potential replay attack detected', ['order_reference' => $orderReference]);
                return response()->json(['status' => 'duplicate'], 200);
            }

            // Update or create transaction record
            $transaction = ClickpesaTransaction::updateOrCreate(
                ['order_reference' => $orderReference],
                [
                    'status' => strtolower($data['status'] ?? 'pending'),
                    'response_payload' => $data,
                    'processed_at' => now(),
                ]
            );

            // Mark webhook as processed
            $webhook->markAsProcessed();
            
            // Dispatch event for further processing
            event(new PaymentReceived($data));
            
            Log::info('Clickpesa callback processed', ['order_reference' => $orderReference]);
            
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Clickpesa callback processing failed', [
                'error' => $e->getMessage(),
                'order_reference' => $orderReference,
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
