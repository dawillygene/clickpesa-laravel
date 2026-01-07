<?php

namespace Dawilly\Dawilly\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;

class PaymentReceived
{
    use Dispatchable, InteractsWithSockets;

    public $paymentData;

    public function __construct($paymentData)
    {
        $this->paymentData = $paymentData;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('payments');
    }
}
