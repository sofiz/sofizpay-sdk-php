<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Models;

/**
 * Signature verification result model
 */
class SignatureVerification
{
    private bool $isValid;
    private string $paymentStatus;
    private string $transactionId;
    private string $cibTransactionId;
    private string $amount;
    private string $message;
    private ?string $error;

    public function __construct(
        bool $isValid,
        string $paymentStatus,
        string $transactionId,
        string $cibTransactionId,
        string $amount,
        string $message,
        ?string $error = null
    ) {
        $this->isValid = $isValid;
        $this->paymentStatus = $paymentStatus;
        $this->transactionId = $transactionId;
        $this->cibTransactionId = $cibTransactionId;
        $this->amount = $amount;
        $this->message = $message;
        $this->error = $error;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getCibTransactionId(): string
    {
        return $this->cibTransactionId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function isSuccessful(): bool
    {
        return $this->isValid && $this->paymentStatus === 'success';
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'payment_status' => $this->paymentStatus,
            'transaction_id' => $this->transactionId,
            'cib_transaction_id' => $this->cibTransactionId,
            'amount' => $this->amount,
            'message' => $this->message,
            'error' => $this->error,
            'is_successful' => $this->isSuccessful(),
        ];
    }
}
