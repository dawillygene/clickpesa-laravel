<?php

namespace Dawilly\Dawilly\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClickpesaService
{
    protected $apiKey;
    protected $clientId;
    protected $environment;
    protected $baseUrl;
    protected $client;
    protected $token;

    public function __construct($apiKey, $clientId, $environment = 'sandbox')
    {
        $this->apiKey = $apiKey;
        $this->clientId = $clientId;
        $this->environment = $environment;
        $this->baseUrl = $environment === 'live' 
            ? 'https://api.clickpesa.com' 
            : 'https://sandbox.clickpesa.com';
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    /**
     * Generate Authorization Token
     * 
     * Generates JWT Authorization token required for accessing ClickPesa APIs.
     * Token is valid for 1 hour from the time of issuance.
     * 
     * @return string|null Returns bearer token string on success, null on failure
     * 
     * @example Success Response:
     * {
     *   "success": true,
     *   "token": "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     * }
     * 
     * @example Error Responses:
     * // Unauthorized (missing or invalid credentials)
     * {
     *   "message": "Unauthorized"
     * }
     * 
     * // Invalid or Expired API-Key
     * {
     *   "message": "Invalid or Expired API-Key"
     * }
     */
    public function generateToken()
    {
        try {
            $response = $this->client->post('/third-parties/generate-token', [
                'headers' => [
                    'api-key' => $this->apiKey,
                    'client-id' => $this->clientId
                ]
            ]);

            $body = json_decode($response->getBody(), true);
            
            if (isset($body['success']) && $body['success'] === true) {
                $this->token = $body['token']; // Token includes "Bearer " prefix
                return $this->token;
            }

            return null;
        } catch (RequestException $e) {
            return null;
        }
    }

    protected function ensureToken()
    {
        if (!$this->token) {
            $this->generateToken();
        }
    }

    /**
     * Get the current authentication token
     * 
     * @return string|null
     */
    public function getToken()
    {
        $this->ensureToken();
        return $this->token;
    }

    protected function makeRequest($method, $endpoint, $data = [])
    {
        $this->ensureToken();
        
        if (!$this->token) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with Clickpesa'
            ];
        }

        try {
            $options = [
                'headers' => [
                    'Authorization' => $this->token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ];

            if (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null
            ];
        }
    }

    /**
     * Preview USSD-PUSH request
     * 
     * Validates push details like phone number, amount, order-reference and verifies
     * payment channels availability before initiating the actual payment.
     * 
     * @param array $data Request payload
     *   - amount (string, required): Your payment amount
     *   - currency (string, required): Currency code (e.g., 'TZS')
     *   - orderReference (string, required): Your unique order reference (alphanumeric only, cannot be blank)
     *   - phoneNumber (string, optional): Mobile number starting with country code (e.g., '255712345678')
     *   - fetchSenderDetails (bool, optional): If true, fetch sender details. Defaults to false
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "activeMethods": [
     *     {
     *       "name": "TIGO-PESA",
     *       "status": "AVAILABLE",
     *       "fee": 500,
     *       "message": "Service available"
     *     }
     *   ],
     *   "sender": {
     *     "accountName": "Mathayo John",
     *     "accountNumber": "255712345678",
     *     "accountProvider": "TIGO-PESA"
     *   }
     * }
     * 
     * @example Error Responses:
     * // Invalid order reference format
     * {
     *   "message": "Invalid Order Reference, should only contain alphanumeric characters and cannot be blank"
     * }
     * 
     * // Unauthorized - invalid or expired token
     * {
     *   "message": "Unauthorized"
     * }
     * 
     * // No payment methods configured
     * {
     *   "message": "Account has no payment collection methods"
     * }
     * 
     * // Order reference already used
     * {
     *   "message": "Order reference {Your reference} already used: Create a different reference"
     * }
     */
    public function previewUssdPushRequest(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payments/preview-ussd-push-request', $data);
    }

    /**
     * Initiate USSD-PUSH request
     * 
     * Sends the USSD-PUSH request to customer's mobile device for payment authorization.
     * The customer will receive a USSD prompt on their phone to complete the payment.
     * 
     * @param array $data Request payload
     *   - amount (string, required): Your payment amount
     *   - currency (string, required): Currency code (e.g., 'TZS')
     *   - orderReference (string, required): Your unique order reference (alphanumeric only)
     *   - phoneNumber (string, required): Mobile number starting with country code (e.g., '255712345678')
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "id": "txn_abc123xyz",
     *   "status": "PROCESSING",
     *   "channel": "TIGO-PESA",
     *   "orderReference": "ORDER-123",
     *   "collectedAmount": "10000",
     *   "collectedCurrency": "TZS",
     *   "createdAt": "2023-11-07T05:31:56Z",
     *   "clientId": "your-client-id"
     * }
     * 
     * @example Error Responses:
     * // Bad Request - Missing or invalid parameters
     * {"message": "Invalid request parameters"}
     * 
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Not Found - Invalid order reference format
     * {"message": "Order not found"}
     * 
     * // Conflict - Order reference already used
     * {"message": "Order reference {reference} already used: Create a different reference"}
     * 
     * // Server Error
     * {"message": "Internal server error"}
     * 
     * Status values: PROCESSING, SUCCESS, FAILED, SETTLED
     */
    public function initiateUssdPushRequest(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payments/initiate-ussd-push-request', $data);
    }

    /**
     * Query Payment Status by Order Reference
     * 
     * Queries for the payment status using payment's Order Reference.
     * Returns an array of transactions associated with the order reference.
     * 
     * @param string $orderReference Your unique order reference used during payment initiation
     * 
     * @return array Response structure on success (array of transactions):
     * [
     *   {
     *     "id": "txn_abc123xyz",
     *     "status": "SUCCESS",
     *     "paymentReference": "PAY-XYZ-789",
     *     "orderReference": "ORDER-123",
     *     "collectedAmount": 10000,
     *     "collectedCurrency": "TZS",
     *     "message": "Payment completed successfully",
     *     "updatedAt": "2023-11-07T05:31:56Z",
     *     "createdAt": "2023-11-07T05:31:56Z",
     *     "customer": {
     *       "customerName": "John Doe",
     *       "customerPhoneNumber": "255712345678",
     *       "customerEmail": "john@example.com"
     *     },
     *     "clientId": "your-client-id"
     *   }
     * ]
     * 
     * @example Error Responses:
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Not Found - Order reference does not exist
     * {"message": "Payment not found"}
     * 
     * Status values: SUCCESS, SETTLED, PROCESSING, PENDING, FAILED
     */
    public function queryPaymentStatus($orderReference)
    {
        return $this->makeRequest('GET', "/third-parties/payments/{$orderReference}");
    }

    /**
     * Preview Card Payment
     * 
     * Validates card payment details like Amount, Order Reference and verifies
     * card payment method availability.
     * 
     * @param array $data Request payload
     *   - amount (string, required): Your payment amount
     *   - currency (string, required): Currency code - must be 'USD' for card payments
     *   - orderReference (string, required): Your unique order reference (alphanumeric only)
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "activeMethods": [
     *     {
     *       "name": "CARD",
     *       "status": "AVAILABLE",
     *       "fee": 300,
     *       "message": "Card payment available"
     *     }
     *   ]
     * }
     * 
     * @example Error Responses:
     * // Bad Request - Invalid parameters or currency not USD
     * {"message": "Invalid request parameters"}
     * 
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Not Found - Currency not supported for card payments
     * {"message": "Card payments not available for currency"}
     * 
     * // Conflict - Order reference already used
     * {"message": "Order reference already used: Create a different reference"}
     */
    public function previewCardPayment(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payments/preview-card-payment', $data);
    }

    /**
     * Initiate Card Payment
     * 
     * Initiates a card payment and returns a payment link for the customer to complete
     * the transaction. The customer will be redirected to a secure payment page.
     * 
     * @param array $data Request payload
     *   - amount (string, required): Your payment amount
     *   - currency (string, required): Currency code - must be 'USD' for card payments
     *   - orderReference (string, required): Your unique order reference (alphanumeric only)
     *   - customer (array, required): Customer information
     *     - id (string): Existing customer ID, OR
     *     - firstName (string): Customer first name (if creating new)
     *     - lastName (string): Customer last name (if creating new)
     *     - email (string): Customer email (if creating new)
     *     - phoneNumber (string): Customer phone number (if creating new)
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "cardPaymentLink": "https://pay.clickpesa.com/card/abc123xyz",
     *   "clientId": "your-client-id"
     * }
     * 
     * @example Error Responses:
     * // Bad Request - Missing required fields or invalid data
     * {"message": "Missing required customer information"}
     * 
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Not Found - Customer not found (if using existing customer ID)
     * {"message": "Customer not found"}
     * 
     * // Conflict - Order reference already used
     * {"message": "Order reference already used: Create a different reference"}
     * 
     * // Server Error
     * {"message": "Failed to generate payment link"}
     */
    public function initiateCardPayment(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payments/initiate-card-payment', $data);
    }
}
