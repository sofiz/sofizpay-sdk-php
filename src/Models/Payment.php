<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Models;

/**
 * Payment transaction model
 */
class Payment
{
    private string $transactionHash;
    private string $fromAccount;
    private string $toAccount;
    private string $amount;
    private string $assetCode;
    private string $assetIssuer;
    private ?string $memo;
    private string $createdAt;
    private bool $successful;
    private ?string $pagingToken;

    public function __construct(
        string $transactionHash,
        string $fromAccount,
        string $toAccount,
        string $amount,
        string $assetCode,
        string $assetIssuer,
        ?string $memo,
        string $createdAt,
        bool $successful,
        ?string $pagingToken = null
    ) {
        $this->transactionHash = $transactionHash;
        $this->fromAccount = $fromAccount;
        $this->toAccount = $toAccount;
        $this->amount = $amount;
        $this->assetCode = $assetCode;
        $this->assetIssuer = $assetIssuer;
        $this->memo = $memo;
        $this->createdAt = $createdAt;
        $this->successful = $successful;
        $this->pagingToken = $pagingToken;
    }

    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }

    public function getFromAccount(): string
    {
        return $this->fromAccount;
    }

    public function getToAccount(): string
    {
        return $this->toAccount;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    public function getAssetIssuer(): string
    {
        return $this->assetIssuer;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getPagingToken(): ?string
    {
        return $this->pagingToken;
    }

    public function toArray(): array
    {
        return [
            'transaction_hash' => $this->transactionHash,
            'from_account' => $this->fromAccount,
            'to_account' => $this->toAccount,
            'amount' => $this->amount,
            'asset_code' => $this->assetCode,
            'asset_issuer' => $this->assetIssuer,
            'memo' => $this->memo,
            'created_at' => $this->createdAt,
            'successful' => $this->successful,
            'paging_token' => $this->pagingToken,
        ];
    }
}
