<?php

namespace Dawilly\Dawilly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dawilly\Dawilly\Events\PaymentReceived;

class ClickpesaController extends Controller
{
    public function callback(Request $request)
    {
        // Handle Clickpesa callback
        $data = $request->all();
        
        // Log the callback
        \Log::info('Clickpesa Callback', $data);
        
        // Dispatch event or process payment
        event(new PaymentReceived($data));
        
        return response()->json(['status' => 'success']);
    }
}
