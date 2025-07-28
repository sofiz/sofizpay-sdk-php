<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Sofiz\SofizPay\SofizPayClient;
use Sofiz\SofizPay\Models\CibTransaction;
use Sofiz\SofizPay\Models\SignatureVerification;
use Sofiz\SofizPay\Exceptions\NetworkException;
use Sofiz\SofizPay\Exceptions\ValidationException;
use Sofiz\SofizPay\Exceptions\SofizPayException;

/**
 * CIB payment service for handling CIB transactions and signature verification
 */
class CibService
{
    private SofizPayClient $client;
    private HttpClient $httpClient;
    private string $baseUrl;

    public function __construct(SofizPayClient $client, string $baseUrl = 'https://api.sofizpay.com')
    {
        $this->client = $client;
        $this->httpClient = $client->getHttpClient();
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Create a CIB transaction
     *
     * @param string $account The Stellar account ID
     * @param string $amount The amount for the transaction
     * @param string $fullName The full name of the user
     * @param string $phone The phone number of the user
     * @param string $email The email address of the user
     * @param string|null $returnUrl The URL to redirect to after payment
     * @param string|null $memo Optional memo for the transaction
     * @param bool $redirect Whether to redirect to payment URL immediately
     * @return CibTransaction
     * @throws ValidationException
     * @throws NetworkException
     * @throws SofizPayException
     */
    public function createTransaction(
        string $account,
        string $amount,
        string $fullName,
        string $phone,
        string $email,
        ?string $returnUrl = null,
        ?string $memo = null,
        bool $redirect = false
    ): CibTransaction {
        // Validate required parameters
        if (empty($account)) {
            throw new ValidationException('Account parameter is required');
        }
        
        if (empty($amount)) {
            throw new ValidationException('Amount parameter is required');
        }
        
        if (!is_numeric($amount) || (float)$amount <= 0) {
            throw new ValidationException('Amount must be a positive number');
        }
        
        if (empty($fullName)) {
            throw new ValidationException('Full name is required');
        }
        
        if (empty($phone)) {
            throw new ValidationException('Phone number is required');
        }
        
        if (empty($email)) {
            throw new ValidationException('Email address is required');
        }

        try {
            $params = [
                'account' => $account,
                'amount' => $amount,
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'redirect' => $redirect ? 'yes' : 'no',
            ];

            if ($returnUrl) {
                $params['return_url'] = $returnUrl;
            }

            if ($memo) {
                $params['memo'] = $memo;
            }

            $response = $this->httpClient->get($this->baseUrl . "/make-cib-transaction/", [
                'query' => $params,
                'timeout' => 30,
            ]);

            $rawContent = $response->getBody()->getContents();
            $data = json_decode($rawContent, true);

            // Debug output
            echo "Debug: API Response Status: " . $response->getStatusCode() . "\n";
            echo "Debug: Raw Response: " . substr($rawContent, 0, 500) . "\n";
            echo "Debug: API Response Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";

            if (!$data || !isset($data['success']) || !$data['success']) {
                throw new SofizPayException(
                    $data['error'] ?? 'Failed to create CIB transaction',
                    $response->getStatusCode()
                );
            }

            return new CibTransaction(
                $data['transaction_id'],
                (string)$data['cib_transaction_id'], // Convert to string
                $data['payment_url'],
                $data['amount'],
                $data['status'],
                $data['more_info_url'],
                $data['cib_response'] ?? []
            );

        } catch (RequestException $e) {
            throw new NetworkException(
                'Network error while creating CIB transaction: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            if ($e instanceof SofizPayException) {
                throw $e;
            }
            throw new SofizPayException('Unexpected error while creating CIB transaction: ' . $e->getMessage());
        }
    }

    /**
     * Verify the signature from a CIB transaction return URL
     *
     * @param string $returnUrl The complete return URL with parameters
     * @param string $publicKeyPem The merchant's public key in PEM format
     * @return SignatureVerification
     * @throws ValidationException
     * @throws SofizPayException
     */
    public function verifySignature(string $returnUrl, string $publicKeyPem): SignatureVerification
    {
        try {
            // Parse the URL to extract parameters
            $urlParts = parse_url($returnUrl);
            if (!isset($urlParts['query'])) {
                throw new ValidationException('Invalid return URL: no query parameters found');
            }

            parse_str($urlParts['query'], $params);

            // Validate required parameters
            $requiredParams = ['payment_status', 'transaction_id', 'cib_transaction_id', 'signature', 'message'];
            foreach ($requiredParams as $param) {
                if (!isset($params[$param])) {
                    throw new ValidationException("Missing required parameter: {$param}");
                }
            }

            $paymentStatus = $params['payment_status'];
            $transactionId = $params['transaction_id'];
            $cibTransactionId = $params['cib_transaction_id'];
            $signature = $params['signature'];
            $message = $params['message'];

            // Extract amount from message (assuming message format: base_url + status + amount)
            $amount = $this->extractAmountFromMessage($message, $paymentStatus);

            // Verify the signature
            $isValid = $this->verifyMessageSignature($message, $signature, $publicKeyPem);

            return new SignatureVerification(
                $isValid,
                $paymentStatus,
                $transactionId,
                $cibTransactionId,
                $amount,
                $message,
                $isValid ? null : 'Invalid signature'
            );

        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            
            return new SignatureVerification(
                false,
                $params['payment_status'] ?? 'unknown',
                $params['transaction_id'] ?? '',
                $params['cib_transaction_id'] ?? '',
                '',
                $params['message'] ?? '',
                'Error verifying signature: ' . $e->getMessage()
            );
        }
    }

    /**
     * Verify a message signature using RSA public key
     *
     * @param string $message The original message
     * @param string $signature The base64 URL-safe encoded signature
     * @param string $publicKeyPem The public key in PEM format
     * @return bool
     */
    private function verifyMessageSignature(string $message, string $signature, string $publicKeyPem): bool
    {
        try {
            // Decode the URL-safe base64 signature
            $decodedSignature = base64_decode(strtr($signature, '-_', '+/'));
            if ($decodedSignature === false) {
                return false;
            }

            // Load the public key
            $publicKey = openssl_pkey_get_public($publicKeyPem);
            if ($publicKey === false) {
                return false;
            }

            // Verify the signature
            $result = openssl_verify($message, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
            
            // Clean up
            if (is_resource($publicKey)) {
                openssl_free_key($publicKey);
            }

            return $result === 1;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract amount from the message
     * Message format is typically: base_url + status + amount
     *
     * @param string $message The complete message
     * @param string $status The payment status
     * @return string The extracted amount
     */
    private function extractAmountFromMessage(string $message, string $status): string
    {
        try {
            // Find the status in the message and extract what comes after it
            $statusPos = strrpos($message, $status);
            if ($statusPos !== false) {
                $amountPart = substr($message, $statusPos + strlen($status));
                // Remove any non-numeric characters except decimal point
                $amount = preg_replace('/[^0-9.]/', '', $amountPart);
                return $amount ?: '0';
            }
            return '0';
        } catch (\Exception $e) {
            return '0';
        }
    }

    /**
     * Parse CIB return URL parameters
     *
     * @param string $returnUrl The complete return URL
     * @return array Parsed parameters
     */
    public function parseReturnUrl(string $returnUrl): array
    {
        $urlParts = parse_url($returnUrl);
        $params = [];
        
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $params);
        }
        
        return $params;
    }

    /**
     * Check if a return URL indicates a successful payment
     *
     * @param string $returnUrl The return URL to check
     * @return bool True if payment was successful
     */
    public function isPaymentSuccessful(string $returnUrl): bool
    {
        $params = $this->parseReturnUrl($returnUrl);
        return isset($params['payment_status']) && $params['payment_status'] === 'success';
    }
}
