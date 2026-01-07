<?php

namespace Dawilly\Dawilly\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DisbursementService
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
     * Set authentication token
     * 
     * @param string $token Bearer token from ClickpesaService
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    protected function makeRequest($method, $endpoint, $data = [])
    {
        if (!$this->token) {
            return [
                'success' => false,
                'message' => 'Authentication required. Please set token first.'
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

    // ==================== MOBILE MONEY PAYOUT METHODS ====================

    /**
     * Preview Mobile Money Payout
     * 
     * Validates mobile money payout details like phone number, amount, order-reference, fee.
     * Provides exchange rate information if currency conversion is applied.
     * 
     * @param array $data Request payload
     *   - amount (number, required): Your payout amount
     *   - phoneNumber (string, required): Mobile number starting with country code (e.g., '255712345678')
     *   - currency (string, required): Account currency to pay out from (TZS or USD)
     *   - orderReference (string, required): Your unique order reference (alphanumeric only)
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "amount": 1047.1,
     *   "balance": 2000,
     *   "channelProvider": "TIGO PESA",
     *   "fee": 47.1,
     *   "exchanged": true,
     *   "exchange": {
     *     "sourceCurrency": "USD",
     *     "targetCurrency": "TZS",
     *     "sourceAmount": 1000,
     *     "rate": 2500
     *   },
     *   "order": {
     *     "amount": 1000,
     *     "currency": "TZS",
     *     "id": "ORDER-REF-123"
     *   },
     *   "payoutFeeBearer": "merchant",
     *   "receiver": {
     *     "accountName": "John Doe",
     *     "accountNumber": "255650000000",
     *     "accountCurrency": "TZS",
     *     "amount": 1000
     *   }
     * }
     * 
     * @example Error Responses:
     * // Bad Request - Invalid parameters
     * {"message": "Invalid request parameters"}
     * 
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Conflict - Order reference already used
     * {"message": "Order reference already used: Create a different reference"}
     * 
     * // Server Error
     * {"message": "Internal server error"}
     */
    public function previewMobileMoneyPayout(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payouts/preview-mobile-money-payout', $data);
    }

    /**
     * Create Mobile Money Payout
     * 
     * Initiate a mobile money payout. The specified amount will be transferred to 
     * the recipient's mobile wallet. The recipient will receive funds in TZS.
     * 
     * @param array $data Request payload
     *   - amount (number, required): Your payout amount
     *   - phoneNumber (string, required): Mobile number starting with country code (e.g., '255712345678')
     *   - currency (string, required): Account currency to pay out from (TZS or USD)
     *   - orderReference (string, required): Your unique order reference (alphanumeric only)
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "id": "payout_abc123xyz",
     *   "createdAt": "2023-11-07T05:31:56Z",
     *   "updatedAt": "2023-11-07T05:31:56Z",
     *   "orderReference": "ORDER-123",
     *   "amount": "1047.10",
     *   "currency": "TZS",
     *   "fee": "47.10",
     *   "exchanged": true,
     *   "exchange": {
     *     "sourceCurrency": "USD",
     *     "targetCurrency": "TZS",
     *     "sourceAmount": 1000,
     *     "rate": 2500
     *   },
     *   "status": "AUTHORIZED",
     *   "channel": "MOBILE MONEY",
     *   "channelProvider": "MPESA TANZANIA",
     *   "order": {
     *     "amount": "1000.00",
     *     "currency": "TZS"
     *   },
     *   "beneficiary": {
     *     "accountNumber": "255650000000",
     *     "accountName": "John Doe",
     *     "amount": "1000.00"
     *   },
     *   "clientId": "your-client-id"
     * }
     * 
     * @example Error Responses:
     * // Bad Request - Invalid parameters
     * {"message": "Invalid request parameters"}
     * 
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Conflict - Order reference already used
     * {"message": "Order reference already used: Create a different reference"}
     * 
     * // Server Error
     * {"message": "Internal server error"}
     * 
     * Status values: AUTHORIZED, SUCCESS, REVERSED
     */
    public function createMobileMoneyPayout(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payouts/create-mobile-money-payout', $data);
    }

    // ==================== BANK PAYOUT METHODS ====================

    /**
     * Preview Bank Payout
     * 
     * Validates bank payout details like amount, order-reference and verifies 
     * payout channels availability. Provides fee and exchange information.
     * 
     * @param array $data Request payload
     *   - amount (number, required): Your payout amount
     *   - accountNumber (string, required): Beneficiary account number
     *   - currency (string, required): Account currency to pay out from (TZS or USD)
     *   - orderReference (string, required): Your unique order reference (alphanumeric only)
     *   - bic (string, required): Beneficiary bank BIC code
     *   - transferType (string, required): Transfer type - ACH or RTGS
     *   - accountCurrency (string, optional): Receiving currency (default: TZS)
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "amount": 1047.1,
     *   "balance": 2000,
     *   "channelProvider": "AMANA BANK LIMITED",
     *   "fee": 47.1,
     *   "exchanged": true,
     *   "exchange": {
     *     "sourceCurrency": "USD",
     *     "targetCurrency": "TZS",
     *     "sourceAmount": 1000,
     *     "rate": 2500
     *   },
     *   "order": {
     *     "amount": 1000,
     *     "currency": "TZS",
     *     "id": "ORDER-REF-123"
     *   },
     *   "payoutFeeBearer": "merchant",
     *   "receiver": {
     *     "accountNumber": "0112345400847jhs",
     *     "accountCurrency": "TZS",
     *     "amount": 1000
     *   },
     *   "transferType": "ACH"
     * }
     * 
     * @example Error Responses:
     * // Bad Request - Invalid parameters or invalid BIC
     * {"message": "Invalid bank BIC code"}
     * 
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Conflict - Order reference already used
     * {"message": "Order reference already used: Create a different reference"}
     * 
     * // Server Error
     * {"message": "Internal server error"}
     */
    public function previewBankPayout(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payouts/preview-bank-payout', $data);
    }

    /**
     * Create Bank Payout
     * 
     * Initiate a bank payout. The specified amount will be transferred to the 
     * beneficiary's bank account using the specified transfer type (ACH or RTGS).
     * 
     * @param array $data Request payload
     *   - amount (number, required): Your payout amount
     *   - accountNumber (string, required): Beneficiary account number
     *   - accountName (string, required): Beneficiary account name
     *   - currency (string, required): Account currency to pay out from (TZS or USD)
     *   - orderReference (string, required): Your unique order reference (alphanumeric only)
     *   - bic (string, required): Beneficiary bank BIC code
     *   - transferType (string, required): Transfer type - ACH or RTGS
     *   - accountCurrency (string, optional): Receiving currency (default: TZS)
     *   - checksum (string, optional): Generated checksum of the payload
     * 
     * @return array Response structure on success:
     * {
     *   "id": "payout_xyz789abc",
     *   "createdAt": "2023-11-07T05:31:56Z",
     *   "updatedAt": "2023-11-07T05:31:56Z",
     *   "orderReference": "ORDER-456",
     *   "amount": "202360.00",
     *   "currency": "TZS",
     *   "fee": "2360.00",
     *   "exchanged": true,
     *   "exchange": {
     *     "sourceCurrency": "USD",
     *     "targetCurrency": "TZS",
     *     "sourceAmount": 1000,
     *     "rate": 2500
     *   },
     *   "status": "AUTHORIZED",
     *   "channel": "BANK TRANSFER",
     *   "channelProvider": "Equity Bank Tanzania Limited",
     *   "transferType": "ACH",
     *   "order": {
     *     "amount": "20000.00",
     *     "currency": "TZS"
     *   },
     *   "beneficiary": {
     *     "accountNumber": "123456789",
     *     "accountName": "John Doe",
     *     "amount": "20000.00"
     *   },
     *   "clientId": "your-client-id"
     * }
     * 
     * @example Error Responses:
     * // Bad Request - Invalid parameters or invalid BIC
     * {"message": "Invalid bank BIC code"}
     * 
     * // Unauthorized - Invalid or expired token
     * {"message": "Unauthorized"}
     * 
     * // Conflict - Order reference already used
     * {"message": "Order reference already used: Create a different reference"}
     * 
     * // Server Error
     * {"message": "Internal server error"}
     * 
     * Status values: AUTHORIZED, SUCCESS, REVERSED
     * Transfer Types: ACH, RTGS
     */
    public function createBankPayout(array $data)
    {
        return $this->makeRequest('POST', '/third-parties/payouts/create-bank-payout', $data);
    }

    /**
     * Query Payout Status by Order Reference
     * 
     * Get the current status of a payout transaction using the order reference.
     * 
     * @param string $orderReference Your unique order reference used during payout initiation
     * 
     * @return array Response structure on success (array of payouts):
     * [
     *   {
     *     "id": "payout_abc123xyz",
     *     "status": "SUCCESS",
     *     "orderReference": "ORDER-123",
     *     "amount": "1047.10",
     *     "currency": "TZS",
     *     "fee": "47.10",
     *     "channel": "MOBILE MONEY",
     *     "channelProvider": "TIGO PESA",
     *     "createdAt": "2023-11-07T05:31:56Z",
     *     "updatedAt": "2023-11-07T05:31:56Z",
     *     "beneficiary": {
     *       "accountNumber": "255650000000",
     *       "accountName": "John Doe"
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
     * {"message": "Payout not found"}
     * 
     * Status values: AUTHORIZED, SUCCESS, REVERSED
     */
    public function queryPayoutStatus($orderReference)
    {
        return $this->makeRequest('GET', "/third-parties/payouts/{$orderReference}");
    }
}
