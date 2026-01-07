# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability, please email security@dawillygene.com. Do not create a public GitHub issue.

## Security Best Practices

### 1. **API Credentials Protection**
- Never commit your `.env` file with real credentials
- Use different API keys for sandbox and production
- Rotate API keys regularly (every 90 days recommended)
- Store credentials in secure secret management systems in production

### 2. **Webhook Signature Verification**
**CRITICAL**: Always enable signature verification in production:

```php
// config/clickpesa.php or .env
CLICKPESA_VERIFY_SIGNATURE=true
```

This prevents attackers from sending fake payment callbacks to your application.

### 3. **HTTPS Only**
- Always use HTTPS for webhook callbacks in production
- Configure your `CLICKPESA_CALLBACK_URL` with `https://`
- Clickpesa may reject non-HTTPS URLs in live mode

### 4. **Rate Limiting**
Add rate limiting to prevent abuse:

```php
// In app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:60,1', // 60 requests per minute
        // ...
    ],
];
```

For webhook endpoint specifically:
```php
Route::post('callback', [ClickpesaController::class, 'callback'])
    ->middleware('throttle:100,1'); // 100 callbacks per minute max
```

### 5. **Database Security**
- Never store raw credit card data (PCI-DSS violation)
- The package only stores transaction references and metadata
- Sensitive fields in logs are automatically redacted
- Use encrypted database connections in production

### 6. **Input Validation**
The package validates:
- Order references (alphanumeric only)
- Amount formats (decimal precision)
- Currency codes (3-letter ISO codes)

Always validate data before passing to the package:

```php
$validated = $request->validate([
    'amount' => 'required|numeric|min:1',
    'phone' => 'required|regex:/^255[0-9]{9}$/',
    'order_reference' => 'required|alpha_num|unique:clickpesa_transactions',
]);
```

### 7. **Replay Attack Protection**
The package automatically detects duplicate webhooks within 5 minutes.

Additional protection:
```php
// Before initiating payment
$exists = ClickpesaTransaction::where('order_reference', $ref)
    ->where('created_at', '>=', now()->subHours(24))
    ->exists();

if ($exists) {
    throw new \Exception('Duplicate order reference');
}
```

### 8. **Cache Security**
- Cache keys include environment (sandbox/live) to prevent cross-environment leaks
- Tokens are cached with proper TTL (1 hour)
- Use Redis with authentication in production:

```env
CACHE_DRIVER=redis
REDIS_PASSWORD=your-secure-password
```

### 9. **Logging Security**
Sensitive data is automatically redacted from logs:
- `api_key`
- `client_id`
- `token`
- `authorization` headers
- `password` fields

Still, review logs periodically for data leakage.

### 10. **Error Handling**
Never expose internal errors to clients:

```php
try {
    $response = Clickpesa::initiateUssdPushRequest($data);
} catch (\Exception $e) {
    // Log the real error
    Log::error('Payment failed', ['error' => $e->getMessage()]);
    
    // Return generic message to user
    return response()->json(['error' => 'Payment processing failed'], 500);
}
```

## Known Limitations

1. **Token Caching**: Cached tokens are stored in plain text in your cache. Use encrypted cache drivers for sensitive environments.

2. **Webhook Idempotency**: Webhooks are deduplicated within 5 minutes only. For longer periods, implement custom idempotency logic.

3. **No Built-in Encryption**: Transaction metadata is stored unencrypted. Encrypt sensitive metadata before storing:
```php
'metadata' => [
    'user_id' => encrypt($userId), // Laravel's encrypt helper
]
```

## Security Checklist for Production

- [ ] Enable signature verification (`CLICKPESA_VERIFY_SIGNATURE=true`)
- [ ] Use HTTPS for callback URL
- [ ] Rotate API keys every 90 days
- [ ] Enable rate limiting on webhook endpoint
- [ ] Use secure cache driver (Redis with password)
- [ ] Review and restrict database permissions
- [ ] Enable Laravel's encryption (`APP_KEY` set)
- [ ] Use queue workers for webhook processing (prevents timeout attacks)
- [ ] Monitor failed webhook attempts
- [ ] Set up alerts for suspicious activity
- [ ] Regular security audits of transaction logs
- [ ] Backup database with encryption

## Compliance

- **PCI-DSS**: Package does not store card data; Clickpesa handles card processing
- **GDPR**: Logs include IP addresses; ensure proper data retention policies
- **Data Retention**: Implement automatic cleanup of old webhook logs:

```php
// In a scheduled command
ClickpesaWebhook::where('created_at', '<', now()->subMonths(6))->delete();
```

## Updates

- Subscribe to security advisories: watch this repo for security releases
- Update package regularly: `composer update dawilly/laravel-clickpesa`
- Review CHANGELOG.md for security-related changes
