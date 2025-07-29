<?php

declare(strict_types=1);

namespace Sofiz\SofizPay;

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Network;
use GuzzleHttp\Client as HttpClient;
use Sofiz\SofizPay\Models\DztAsset;
use Sofiz\SofizPay\Services\PaymentService;
use Sofiz\SofizPay\Services\AccountService;
use Sofiz\SofizPay\Services\CibService;

/**
 * Main SofizPay SDK Client
 */
class SofizPayClient
{
    private StellarSDK $stellarSdk;
    private HttpClient $httpClient;
    private string $network;
    private Network $stellarNetwork;
    private DztAsset $dztAsset;
    private PaymentService $paymentService;
    private AccountService $accountService;
    private CibService $cibService;

    // Fixed DZT asset issuer
    private const DZT_ISSUER_ACCOUNT_ID = 'GCAZI7YBLIDJWIVEL7ETNAZGPP3LC24NO6KAOBWZHUERXQ7M5BC52DLV';

    public function __construct(
        string $network = 'mainnet',
        ?HttpClient $httpClient = null,
        string $baseUrl = 'https://api.sofizpay.com'
    ) {
        $this->network = $network;
        $this->httpClient = $httpClient ?? new HttpClient();
        
        // Initialize Stellar SDK
        $this->initializeStellarSdk();
        
        // Initialize DZT Asset with fixed issuer
        $this->dztAsset = new DztAsset(self::DZT_ISSUER_ACCOUNT_ID);
        
        // Initialize services
        $this->paymentService = new PaymentService($this, $this->dztAsset);
        $this->accountService = new AccountService($this, $this->dztAsset);
        $this->cibService = new CibService($this, $baseUrl);
    }

    private function initializeStellarSdk(): void
    {
        if ($this->network === 'mainnet') {
            $this->stellarSdk = StellarSDK::getPublicNetInstance();
            $this->stellarNetwork = Network::public();
        } else {
            $this->stellarSdk = StellarSDK::getTestNetInstance();
            $this->stellarNetwork = Network::testnet();
        }
    }

    /**
     * Get the Stellar SDK instance
     */
    public function getStellarSdk(): StellarSDK
    {
        return $this->stellarSdk;
    }

    /**
     * Get the Stellar Network instance
     */
    public function getStellarNetwork(): Network
    {
        return $this->stellarNetwork;
    }

    /**
     * Get the HTTP client instance
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Get the current network
     */
    public function getNetwork(): string
    {
        return $this->network;
    }

    /**
     * Get the DZT asset instance
     */
    public function getDztAsset(): DztAsset
    {
        return $this->dztAsset;
    }

    /**
     * Get the payment service
     */
    public function payments(): PaymentService
    {
        return $this->paymentService;
    }

    /**
     * Get the account service
     */
    public function accounts(): AccountService
    {
        return $this->accountService;
    }

    /**
     * Get the CIB service
     */
    public function cib(): CibService
    {
        return $this->cibService;
    }

    // Convenience methods for direct access to main functionality

    /**
     * Send a DZT payment
     */
    public function sendPayment(
        string $sourceSecretKey,
        string $destinationAccountId,
        string $amount,
        ?string $memo = null
    ): string {
        return $this->paymentService->sendPayment($sourceSecretKey, $destinationAccountId, $amount, $memo);
    }

    /**
     * Get payment history for an account (latest first)
     */
    public function getPaymentHistory(
        string $accountId,
        int $limit = 20,
        ?string $cursor = null
    ): array {
        return $this->paymentService->getPaymentHistory($accountId, $limit, $cursor);
    }

    /**
     * Get transactions by memo (latest first)
     */
    public function getTransactionsByMemo(
        string $accountId, 
        string $memo, 
        int $limit = 20, 
        ?string $cursor = null
    ): array {
        return $this->paymentService->getTransactionsByMemo($accountId, $memo, $limit, $cursor);
    }

    /**
     * Get DZD balance for an account
     */
    public function getDzdBalance(string $accountId): ?\Sofiz\SofizPay\Models\Balance
    {
        return $this->accountService->getDzdBalance($accountId);
    }

    /**
     * Create a CIB transaction
     */
    public function createCibTransaction(
        string $account,
        string $amount,
        string $fullName,
        string $phone,
        string $email,
        ?string $returnUrl = null,
        ?string $memo = null,
        bool $redirect = false
    ): \Sofiz\SofizPay\Models\CibTransaction {
        return $this->cibService->createTransaction(
            $account,
            $amount,
            $fullName,
            $phone,
            $email,
            $returnUrl,
            $memo,
            $redirect
        );
    }

    /**
     * Verify CIB transaction signature
     */
    public function verifyCibSignature(string $returnUrl, string $publicKeyPem): \Sofiz\SofizPay\Models\SignatureVerification
    {
        return $this->cibService->verifySignature($returnUrl, $publicKeyPem);
    }
}
