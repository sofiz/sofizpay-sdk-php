<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Models;

/**
 * CIB transaction model representing a CIB payment transaction
 */
class CibTransaction
{
    private string $transactionId;
    private string $cibTransactionId;
    private string $paymentUrl;
    private string $amount;
    private string $status;
    private string $moreInfoUrl;
    private array $cibResponse;

    public function __construct(
        string $transactionId,
        string $cibTransactionId,
        string $paymentUrl,
        string $amount,
        string $status,
        string $moreInfoUrl,
        array $cibResponse = []
    ) {
        $this->transactionId = $transactionId;
        $this->cibTransactionId = $cibTransactionId;
        $this->paymentUrl = $paymentUrl;
        $this->amount = $amount;
        $this->status = $status;
        $this->moreInfoUrl = $moreInfoUrl;
        $this->cibResponse = $cibResponse;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getCibTransactionId(): string
    {
        return $this->cibTransactionId;
    }

    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMoreInfoUrl(): string
    {
        return $this->moreInfoUrl;
    }

    public function getCibResponse(): array
    {
        return $this->cibResponse;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'cib_transaction_id' => $this->cibTransactionId,
            'payment_url' => $this->paymentUrl,
            'amount' => $this->amount,
            'status' => $this->status,
            'more_info_url' => $this->moreInfoUrl,
            'cib_response' => $this->cibResponse,
        ];
    }
}
