# Laravel Clickpesa Payment Integration

A Laravel package for integrating Clickpesa payment gateway.

## Installation

```bash
composer require dawilly/laravel-clickpesa
```

## Configuration

Publish the config file:
```bash
php artisan vendor:publish --tag=clickpesa-config
```

Add to your `.env`:
```
CLICKPESA_API_KEY=your_api_key
CLICKPESA_ENVIRONMENT=sandbox
CLICKPESA_CALLBACK_URL=https://yourdomain.com/clickpesa/callback
CLICKPESA_CURRENCY=TZS
```

## Usage

```php
use Dawilly\LaravelClickpesa\Facades\Clickpesa;

// Initiate payment
$payment = Clickpesa::initiatePayment([
    'amount' => 10000,
    'currency' => 'TZS',
    'reference' => 'ORDER-123',
    'customer_email' => 'customer@example.com'
]);

// Verify payment
$status = Clickpesa::verifyPayment('transaction_id');

// Get payment status
$status = Clickpesa::getPaymentStatus('transaction_id');
```

## Payment Callback

The package automatically provides a callback endpoint at `/clickpesa/callback`. Configure this URL in your Clickpesa dashboard settings.

The callback will dispatch a `PaymentReceived` event that you can listen to:

```php
use Dawilly\LaravelClickpesa\Events\PaymentReceived;

Event::listen(PaymentReceived::class, function (PaymentReceived $event) {
    // Handle payment received
    $paymentData = $event->paymentData;
});
```

## Testing

Run tests with:
```bash
php artisan test --testsuite="Package Test Suite"
```

Or using PHPUnit directly:
```bash
./vendor/bin/phpunit
```

## Features

- ✅ Simple payment initiation
- ✅ Payment verification
- ✅ Payment status checking
- ✅ Callback handling with events
- ✅ Sandbox and live environment support
- ✅ Configurable via environment variables
- ✅ Facade for easy access

## Requirements

- PHP ^8.0
- Laravel ^9.0

## License

MIT License. See LICENSE file for details.

## Author

Dawilly
