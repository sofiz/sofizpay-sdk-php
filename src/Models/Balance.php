<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Models;

/**
 * Account balance model
 */
class Balance
{
    private string $accountId;
    private string $balance;
    private string $assetCode;
    private string $assetIssuer;
    private ?string $limit;
    private bool $isAuthorized;

    public function __construct(
        string $accountId,
        string $balance,
        string $assetCode,
        string $assetIssuer,
        ?string $limit = null,
        bool $isAuthorized = true
    ) {
        $this->accountId = $accountId;
        $this->balance = $balance;
        $this->assetCode = $assetCode;
        $this->assetIssuer = $assetIssuer;
        $this->limit = $limit;
        $this->isAuthorized = $isAuthorized;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    public function getAssetIssuer(): string
    {
        return $this->assetIssuer;
    }

    public function getLimit(): ?string
    {
        return $this->limit;
    }

    public function isAuthorized(): bool
    {
        return $this->isAuthorized;
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'balance' => $this->balance,
            'asset_code' => $this->assetCode,
            'asset_issuer' => $this->assetIssuer,
            'limit' => $this->limit,
            'is_authorized' => $this->isAuthorized,
        ];
    }
}
