<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Services;

use Sofiz\SofizPay\SofizPayClient;
use Sofiz\SofizPay\Models\DztAsset;
use Sofiz\SofizPay\Models\Balance;
use Sofiz\SofizPay\Exceptions\NetworkException;
use Soneso\StellarSDK\Responses\Account\AccountBalanceResponse;
use Exception;

/**
 * Service for handling account operations
 */
class AccountService
{
    private SofizPayClient $client;
    private DztAsset $dztAsset;

    public function __construct(SofizPayClient $client, DztAsset $dztAsset)
    {
        $this->client = $client;
        $this->dztAsset = $dztAsset;
    }

    /**
     * Get DZT balance for an account
     */
    public function getDztBalance(string $accountId): ?Balance
    {
        try {
            $stellarSdk = $this->client->getStellarSdk();
            $account = $stellarSdk->requestAccount($accountId);
            
            foreach ($account->getBalances() as $balance) {
                if ($balance->getAssetType() !== 'native' && 
                    $balance->getAssetCode() === $this->dztAsset->getAssetCode() &&
                    $balance->getAssetIssuer() === $this->dztAsset->getIssuerAccountId()) {
                    
                    return new Balance(
                        $accountId,
                        $balance->getBalance(),
                        $balance->getAssetCode(),
                        $balance->getAssetIssuer(),
                        $balance->getLimit(),
                        $this->isBalanceAuthorized($balance)
                    );
                }
            }

            // DZT asset not found in account balances
            return null;
        } catch (Exception $e) {
            throw new NetworkException('Failed to get DZT balance: ' . $e->getMessage());
        }
    }

    /**
     * Get all balances for an account
     */
    public function getAllBalances(string $accountId): array
    {
        try {
            $stellarSdk = $this->client->getStellarSdk();
            $account = $stellarSdk->requestAccount($accountId);
            $balances = [];
            
            foreach ($account->getBalances() as $balance) {
                if ($balance->getAssetType() === 'native') {
                    $balances[] = new Balance(
                        $accountId,
                        $balance->getBalance(),
                        'XLM',
                        'native',
                        null,
                        true
                    );
                } else {
                    $balances[] = new Balance(
                        $accountId,
                        $balance->getBalance(),
                        $balance->getAssetCode(),
                        $balance->getAssetIssuer(),
                        $balance->getLimit(),
                        $this->isBalanceAuthorized($balance)
                    );
                }
            }

            return $balances;
        } catch (Exception $e) {
            throw new NetworkException('Failed to get account balances: ' . $e->getMessage());
        }
    }

    /**
     * Check if account exists on the network
     */
    public function accountExists(string $accountId): bool
    {
        try {
            $stellarSdk = $this->client->getStellarSdk();
            $stellarSdk->requestAccount($accountId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if account has trustline for DZT asset
     */
    public function hasDztTrustline(string $accountId): bool
    {
        try {
            $balance = $this->getDztBalance($accountId);
            return $balance !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    private function isBalanceAuthorized(AccountBalanceResponse $balance): bool
    {
        // For simplicity, assume all balances are authorized
        // This can be enhanced later if specific authorization checking is needed
        return true;
    }
}
