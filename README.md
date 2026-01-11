# Laravel Clickpesa Payment Integration

A comprehensive Laravel package for integrating Clickpesa payment gateway and disbursement services. Support for USSD Push payments, card payments, mobile money payouts, and bank transfers.

## Features

- ✅ **Payments**: USSD Push, Card Payments
- ✅ **Disbursements**: Mobile Money Payouts, Bank Transfers (ACH/RTGS)
- ✅ **Authentication**: JWT token management with auto-refresh
- ✅ **Payment Status**: Query and track payment status
- ✅ **Payout Status**: Track disbursement transactions
- ✅ **Sandbox & Live**: Full support for both environments
- ✅ **Error Handling**: Comprehensive error documentation
- ✅ **Facades**: Clean, Laravel-style API

## Installation

```bash
composer require dawilly/laravel-clickpesa
```

## Configuration

### Publishing Package Assets

The package provides the following publishable assets:

#### 1. Publish Configuration File
```bash
php artisan vendor:publish --tag=clickpesa-config
```
Publishes `config/clickpesa.php` to your application's config directory.

#### 2. Publish Database Migrations
```bash
php artisan vendor:publish --tag=clickpesa-migrations
```
Publishes database migrations to your `database/migrations/` directory.

#### 3. Publish All Assets
```bash
php artisan vendor:publish --provider="Dawilly\\Dawilly\\ClickpesaServiceProvider"
```
Publishes all package assets (config + migrations) at once.

### Running Migrations

After publishing the migrations, run:
```bash
php artisan migrate
```

This creates the `clickpesa_transactions` and `clickpesa_webhooks` tables in your database.

### Environment Configuration

Add to your `.env`:
```env
CLICKPESA_API_KEY=your_api_key
CLICKPESA_CLIENT_ID=your_client_id
CLICKPESA_ENVIRONMENT=sandbox
CLICKPESA_CALLBACK_URL=https://yourdomain.com/clickpesa/callback
CLICKPESA_CURRENCY=TZS
```

## Usage

### 1. Payment Operations

#### USSD Push Payments

```php
use Dawilly\Dawilly\Facades\Clickpesa;

// Preview available payment methods
$preview = Clickpesa::previewUssdPushRequest([
    'amount' => '10000',
    'currency' => 'TZS',
    'orderReference' => 'ORDER-123',
    'phoneNumber' => '255712345678',
    'fetchSenderDetails' => true
]);

// Response:
// {
//   "activeMethods": [
//     {
//       "name": "TIGO-PESA",
//       "status": "AVAILABLE",
//       "fee": 500,
//       "message": "Service available"
//     }
//   ],
//   "sender": {
//     "accountName": "John Doe",
//     "accountNumber": "255712345678",
//     "accountProvider": "TIGO-PESA"
//   }
// }

// Initiate the payment
$payment = Clickpesa::initiateUssdPushRequest([
    'amount' => '10000',
    'currency' => 'TZS',
    'orderReference' => 'ORDER-123',
    'phoneNumber' => '255712345678'
]);

// Response:
// {
//   "id": "txn_abc123xyz",
//   "status": "PROCESSING",
//   "channel": "TIGO-PESA",
//   "orderReference": "ORDER-123",
//   "collectedAmount": "10000",
//   "collectedCurrency": "TZS",
//   "createdAt": "2023-11-07T05:31:56Z",
//   "clientId": "your-client-id"
// }
```

#### Card Payments

```php
// Preview card payment availability
$preview = Clickpesa::previewCardPayment([
    'amount' => '100',
    'currency' => 'USD',
    'orderReference' => 'CARD-001'
]);

// Initiate card payment (returns payment link)
$cardPayment = Clickpesa::initiateCardPayment([
    'amount' => '100',
    'currency' => 'USD',
    'orderReference' => 'CARD-001',
    'customer' => [
        'id' => 'existing-customer-id'
        // OR for new customers:
        // 'firstName' => 'John',
        // 'lastName' => 'Doe',
        // 'email' => 'john@example.com',
        // 'phoneNumber' => '255712345678'
    ]
]);

// Response:
// {
//   "cardPaymentLink": "https://pay.clickpesa.com/card/abc123xyz",
//   "clientId": "your-client-id"
// }

// Redirect customer to the payment link
// Redirect::away($cardPayment['cardPaymentLink']);
```

#### Query Payment Status

```php
// Check payment status by order reference
$status = Clickpesa::queryPaymentStatus('ORDER-123');

// Response:
// [
//   {
//     "id": "txn_abc123xyz",
//     "status": "SUCCESS",
//     "paymentReference": "PAY-XYZ-789",
//     "orderReference": "ORDER-123",
//     "collectedAmount": 10000,
//     "collectedCurrency": "TZS",
//     "message": "Payment completed successfully",
//     "updatedAt": "2023-11-07T05:31:56Z",
//     "createdAt": "2023-11-07T05:31:56Z",
//     "customer": {
//       "customerName": "John Doe",
//       "customerPhoneNumber": "255712345678",
//       "customerEmail": "john@example.com"
//     },
//     "clientId": "your-client-id"
//   }
// ]
```

### 2. Disbursement Operations

#### Mobile Money Payouts

```php
use Dawilly\Dawilly\Facades\Disbursement;
use Dawilly\Dawilly\Facades\Clickpesa;

// Ensure authentication token is generated
Clickpesa::generateToken();

// Preview mobile money payout
$preview = Disbursement::previewMobileMoneyPayout([
    'amount' => 10000,
    'phoneNumber' => '255712345678',
    'currency' => 'TZS',
    'orderReference' => 'PAYOUT-001'
]);

// Response:
// {
//   "amount": 10500.00,
//   "balance": 50000,
//   "channelProvider": "TIGO PESA",
//   "fee": 500,
//   "exchanged": false,
//   "order": {
//     "amount": 10000,
//     "currency": "TZS",
//     "id": "PAYOUT-001"
//   },
//   "payoutFeeBearer": "merchant",
//   "receiver": {
//     "accountName": "Jane Doe",
//     "accountNumber": "255712345678",
//     "accountCurrency": "TZS",
//     "amount": 10000
//   }
// }

// Execute mobile money payout
$payout = Disbursement::createMobileMoneyPayout([
    'amount' => 10000,
    'phoneNumber' => '255712345678',
    'currency' => 'TZS',
    'orderReference' => 'PAYOUT-001'
]);

// Response:
// {
//   "id": "payout_abc123xyz",
//   "createdAt": "2023-11-07T05:31:56Z",
//   "updatedAt": "2023-11-07T05:31:56Z",
//   "orderReference": "PAYOUT-001",
//   "amount": "10500.00",
//   "currency": "TZS",
//   "fee": "500.00",
//   "exchanged": false,
//   "status": "AUTHORIZED",
//   "channel": "MOBILE MONEY",
//   "channelProvider": "TIGO PESA",
//   "beneficiary": {
//     "accountNumber": "255712345678",
//     "accountName": "Jane Doe",
//     "amount": "10000.00"
//   },
//   "clientId": "your-client-id"
// }
```

#### Bank Payouts

```php
// Preview bank payout
$preview = Disbursement::previewBankPayout([
    'amount' => 20000,
    'accountNumber' => '123456789',
    'currency' => 'TZS',
    'orderReference' => 'BANK-001',
    'bic' => 'EQTZTZTZ',
    'transferType' => 'ACH'
]);

// Response:
// {
//   "amount": 22360.00,
//   "balance": 50000,
//   "channelProvider": "Equity Bank Tanzania Limited",
//   "fee": 2360,
//   "exchanged": false,
//   "order": {
//     "amount": 20000,
//     "currency": "TZS",
//     "id": "BANK-001"
//   },
//   "payoutFeeBearer": "merchant",
//   "receiver": {
//     "accountNumber": "123456789",
//     "accountCurrency": "TZS",
//     "amount": 20000
//   },
//   "transferType": "ACH"
// }

// Execute bank payout
$bankPayout = Disbursement::createBankPayout([
    'amount' => 20000,
    'accountNumber' => '123456789',
    'accountName' => 'John Doe',
    'currency' => 'TZS',
    'orderReference' => 'BANK-001',
    'bic' => 'EQTZTZTZ',
    'transferType' => 'ACH'  // or 'RTGS'
]);

// Response:
// {
//   "id": "payout_xyz789abc",
//   "createdAt": "2023-11-07T05:31:56Z",
//   "updatedAt": "2023-11-07T05:31:56Z",
//   "orderReference": "BANK-001",
//   "amount": "22360.00",
//   "currency": "TZS",
//   "fee": "2360.00",
//   "exchanged": false,
//   "status": "AUTHORIZED",
//   "channel": "BANK TRANSFER",
//   "channelProvider": "Equity Bank Tanzania Limited",
//   "transferType": "ACH",
//   "beneficiary": {
//     "accountNumber": "123456789",
//     "accountName": "John Doe",
//     "amount": "20000.00"
//   },
//   "clientId": "your-client-id"
// }
```

#### Query Payout Status

```php
// Check payout status by order reference
$payoutStatus = Disbursement::queryPayoutStatus('PAYOUT-001');

// Response:
// [
//   {
//     "id": "payout_abc123xyz",
//     "status": "SUCCESS",
//     "orderReference": "PAYOUT-001",
//     "amount": "10500.00",
//     "currency": "TZS",
//     "fee": "500.00",
//     "channel": "MOBILE MONEY",
//     "channelProvider": "TIGO PESA",
//     "createdAt": "2023-11-07T05:31:56Z",
//     "updatedAt": "2023-11-07T05:31:56Z",
//     "beneficiary": {
//       "accountNumber": "255712345678",
//       "accountName": "Jane Doe"
//     },
//     "clientId": "your-client-id"
//   }
// ]
```

## Payment Callback

The package provides a callback endpoint at `/clickpesa/callback`. Configure this URL in your Clickpesa dashboard.

The callback dispatches a `PaymentReceived` event:

```php
use Dawilly\Dawilly\Events\PaymentReceived;
use Illuminate\Support\Facades\Event;

Event::listen(PaymentReceived::class, function (PaymentReceived $event) {
    $paymentData = $event->paymentData;
    
    // Update your order status
    // Send confirmation email
    // Log transaction
});
```

## Status Values

### Payment Status
- `PROCESSING` - Payment initiated, awaiting customer confirmation
- `SUCCESS` - Payment completed successfully
- `FAILED` - Payment failed
- `SETTLED` - Payment settled

### Payout Status
- `AUTHORIZED` - Payout authorized, processing
- `SUCCESS` - Payout completed successfully
- `REVERSED` - Payout was reversed

## Error Handling

All methods return consistent error responses:

```php
$response = Clickpesa::initiateUssdPushRequest([...]);

if (isset($response['success']) && $response['success'] === false) {
    // Handle error
    $message = $response['message'];
    $details = $response['response'] ?? null;
    
    // Log error
    Log::error('Clickpesa Error', $response);
}
```

### Common Error Messages
- `Unauthorized` - Invalid or expired token
- `Invalid request parameters` - Missing or invalid parameters
- `Order reference already used` - Order reference must be unique
- `Invalid Order Reference, should only contain alphanumeric characters` - Order ref format invalid
- `Account has no payment collection methods` - No payment methods configured
- `Invalid bank BIC code` - Invalid bank BIC provided

## Testing

```bash
./vendor/bin/phpunit
```

Or with Laravel:
```bash
php artisan test
```

## Requirements

- PHP ^8.0
- Laravel ^9.0|^10.0|^11.0
- GuzzleHTTP ^7.0
- Orchestra Testbench ^7.0|^8.0|^9.0 (for development)

## License

MIT License. See LICENSE file for details.

## Author

Dawilly

## Support

For issues or feature requests, please visit the [GitHub repository](https://github.com/dawillygene/clickpesa-laravel).

Refer to [Clickpesa API Documentation](https://docs.clickpesa.com) for more details on the API specifications.
