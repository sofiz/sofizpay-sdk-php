<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Services;

use Sofiz\SofizPay\SofizPayClient;
use Sofiz\SofizPay\Models\DztAsset;
use Sofiz\SofizPay\Models\Payment;
use Sofiz\SofizPay\Exceptions\NetworkException;
use Sofiz\SofizPay\Exceptions\ValidationException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\MemoText;
use Soneso\StellarSDK\Memo;
use Exception;

/**
 * Service for handling payments
 */
class PaymentService
{
    private SofizPayClient $client;
    private DztAsset $dztAsset;

    public function __construct(SofizPayClient $client, DztAsset $dztAsset)
    {
        $this->client = $client;
        $this->dztAsset = $dztAsset;
    }

    /**
     * Send a DZT payment
     */
    public function sendPayment(
        string $sourceSecretKey,
        string $destinationAccountId,
        string $amount,
        ?string $memo = null
    ): string {
        try {
            // Validate inputs
            $this->validatePaymentInputs($sourceSecretKey, $destinationAccountId, $amount);

            $stellarSdk = $this->client->getStellarSdk();
            $sourceKeyPair = KeyPair::fromSeed($sourceSecretKey);
            $sourceAccountId = $sourceKeyPair->getAccountId();

            // Load source account
            $sourceAccount = $stellarSdk->requestAccount($sourceAccountId);

            // Create payment operation
            $paymentOperation = (new PaymentOperationBuilder(
                $destinationAccountId,
                $this->dztAsset->getAsset(),
                $amount
            ))->build();

            // Create transaction
            $transactionBuilder = new TransactionBuilder($sourceAccount);
            $transactionBuilder->addOperation($paymentOperation);

            // Add memo if provided
            if ($memo !== null) {
                $memoObj = Memo::text($memo);
                $transactionBuilder->addMemo($memoObj);
            }

            $transaction = $transactionBuilder->build();

            // Sign transaction
            $transaction->sign($sourceKeyPair, $this->client->getStellarNetwork());

            // Submit transaction
            $response = $stellarSdk->submitTransaction($transaction);

            if (!$response->isSuccessful()) {
                throw new NetworkException('Transaction failed: ' . $response->getExtras()?->getResultCodes()?->getTransactionResultCode());
            }

            return $response->getHash();
        } catch (Exception $e) {
            if ($e instanceof ValidationException || $e instanceof NetworkException) {
                throw $e;
            }
            throw new NetworkException('Failed to send payment: ' . $e->getMessage());
        }
    }

    /**
     * Get payment history for an account (latest first)
     */
    public function getPaymentHistory(
        string $accountId,
        int $limit = 20,
        ?string $cursor = null
    ): array {
        try {
            $stellarSdk = $this->client->getStellarSdk();
            
            // Order by descending (newest first) to get latest transactions
            $requestBuilder = $stellarSdk->payments()
                ->forAccount($accountId)
                ->limit($limit)
                ->order('desc');
            
            if ($cursor !== null) {
                $requestBuilder = $requestBuilder->cursor($cursor);
            }

            $response = $requestBuilder->execute();
            $payments = [];

            foreach ($response->getOperations() as $operation) {
                // Check if this is a payment operation
                if ($operation instanceof \Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse) {
                    
                    // Get asset information via getAsset() method
                    $asset = $operation->getAsset();
                    $assetCode = '';
                    $assetIssuer = '';
                    
                    if ($asset && method_exists($asset, 'getCode') && method_exists($asset, 'getIssuer')) {
                        $assetCode = $asset->getCode();
                        $assetIssuer = $asset->getIssuer();
                    }
                    
                    // Check if this is a DZT payment
                    if ($assetCode === $this->dztAsset->getAssetCode() &&
                        $assetIssuer === $this->dztAsset->getIssuerAccountId()) {
                    
                    $transactionHash = method_exists($operation, 'getTransactionHash') ? 
                        $operation->getTransactionHash() : '';
                    $from = method_exists($operation, 'getFrom') ? $operation->getFrom() : '';
                    $to = method_exists($operation, 'getTo') ? $operation->getTo() : '';
                    $amount = method_exists($operation, 'getAmount') ? $operation->getAmount() : '0';
                    $createdAt = method_exists($operation, 'getCreatedAt') ? $operation->getCreatedAt() : '';
                    $pagingToken = method_exists($operation, 'getPagingToken') ? $operation->getPagingToken() : null;
                    
                    // Try to get memo from transaction
                    $memo = null;
                    try {
                        if (method_exists($operation, 'getTransaction')) {
                            $transaction = $operation->getTransaction();
                            if ($transaction && method_exists($transaction, 'getMemo')) {
                                $memoObj = $transaction->getMemo();
                                if ($memoObj && method_exists($memoObj, 'getValue')) {
                                    $memo = $memoObj->getValue();
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Ignore memo extraction errors
                    }
                    
                    $payments[] = new Payment(
                        $transactionHash,
                        $from,
                        $to,
                        $amount,
                        $assetCode,
                        $assetIssuer,
                        $memo,
                        $createdAt,
                        true, // Assume successful if we got the operation
                        $pagingToken
                    );
                    }
                }
            }

            return $payments;
        } catch (Exception $e) {
            throw new NetworkException('Failed to get payment history: ' . $e->getMessage());
        }
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
        try {
            $stellarSdk = $this->client->getStellarSdk();
            
            // Get transactions for this account in descending order (latest first) and filter by memo
            $requestBuilder = $stellarSdk->transactions()
                ->forAccount($accountId)
                ->limit($limit * 3) // Get more transactions to account for filtering
                ->order('desc');
            
            if ($cursor !== null) {
                $requestBuilder = $requestBuilder->cursor($cursor);
            }
            
            $response = $requestBuilder->execute();
            $matchingPayments = [];
            $foundCount = 0;

            foreach ($response->getTransactions() as $transaction) {
                // Stop if we've found enough matching transactions
                if ($foundCount >= $limit) {
                    break;
                }
                
                $transactionMemo = $transaction->getMemo()?->getValue();
                
                if ($transactionMemo === $memo) {
                    // Get operations for this transaction
                    $operationsResponse = $stellarSdk->operations()
                        ->forTransaction($transaction->getHash())
                        ->execute();

                    foreach ($operationsResponse->getOperations() as $operation) {
                        // Check if this is a payment operation
                        if ($operation instanceof \Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse) {
                            
                            // Get asset information
                            $asset = $operation->getAsset();
                            $assetCode = '';
                            $assetIssuer = '';
                            
                            if ($asset && method_exists($asset, 'getCode') && method_exists($asset, 'getIssuer')) {
                                $assetCode = $asset->getCode();
                                $assetIssuer = $asset->getIssuer();
                            }
                            
                            // Check if this is a DZT payment
                            if ($assetCode === $this->dztAsset->getAssetCode() &&
                                $assetIssuer === $this->dztAsset->getIssuerAccountId()) {
                                
                                $from = method_exists($operation, 'getFrom') ? $operation->getFrom() : '';
                                $to = method_exists($operation, 'getTo') ? $operation->getTo() : '';
                                $amount = method_exists($operation, 'getAmount') ? $operation->getAmount() : '0';
                                $pagingToken = method_exists($operation, 'getPagingToken') ? $operation->getPagingToken() : null;
                                
                                $matchingPayments[] = new Payment(
                                    $transaction->getHash(),
                                    $from,
                                    $to,
                                    $amount,
                                    $assetCode,
                                    $assetIssuer,
                                    $transactionMemo,
                                    $transaction->getCreatedAt(),
                                    $transaction->isSuccessful(),
                                    $pagingToken
                                );
                                $foundCount++;
                                
                                // Break if we've found enough matching payments
                                if ($foundCount >= $limit) {
                                    break 2; // Break out of both loops
                                }
                            }
                        }
                    }
                }
            }

            return $matchingPayments;
        } catch (Exception $e) {
            throw new NetworkException('Failed to get transactions by memo: ' . $e->getMessage());
        }
    }

    private function validatePaymentInputs(string $sourceSecretKey, string $destinationAccountId, string $amount): void
    {
        if (empty($sourceSecretKey)) {
            throw new ValidationException('Source secret key cannot be empty');
        }

        if (empty($destinationAccountId)) {
            throw new ValidationException('Destination account ID cannot be empty');
        }

        if (empty($amount) || !is_numeric($amount) || (float)$amount <= 0) {
            throw new ValidationException('Amount must be a positive number');
        }

        try {
            KeyPair::fromSeed($sourceSecretKey);
        } catch (Exception $e) {
            throw new ValidationException('Invalid source secret key format');
        }
    }
}
