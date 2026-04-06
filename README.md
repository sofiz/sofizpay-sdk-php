<div align="center">
  <img src="https://github.com/kenandarabeh/sofizpay-sdk/blob/main/assets/sofizpay-logo.png?raw=true" alt="SofizPay Logo" width="200" />

  <h2>SofizPay PHP SDK</h2>
  <p><strong>The official PHP library for secure digital payments on the SofizPay platform.</strong></p>

  [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
  [![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
  [![Docs](https://img.shields.io/badge/Docs-docs.sofizpay.com-blue)](https://docs.sofizpay.com)
</div>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Stellar Payments (DZT)](#stellar-payments-dzt)
- [CIB Bank Payments](#cib-bank-payments)
- [Digital Services (Missions)](#digital-services-missions)
- [Operation History](#operation-history)
- [Webhook Signature Verification](#webhook-signature-verification)
- [Security Best Practices](#security-best-practices)
- [Support](#support)

---

## 🌟 Overview

The SofizPay PHP SDK provides a complete interface for integrating **DZT digital payments** into your PHP applications. It supports on-chain Stellar payments, CIB/Dahabia bank deposits, digital service recharges (Missions), and webhook signature verification.

📚 **Full API Reference:** [docs.sofizpay.com](https://docs.sofizpay.com)

---

## 📦 Installation

```bash
composer require sofizpay/sofizpay-sdk-php
```

**Requirements:** PHP `>= 7.4`, `ext-openssl`, `guzzlehttp/guzzle`, `soneso/stellar-php-sdk`

---

## 🚀 Quick Start

```php
<?php
require_once 'vendor/autoload.php';
use SofizPay\SofizPaySDK;

$sdk = new SofizPaySDK();

// Get DZT balance
$balance = $sdk->getBalance('YOUR_PUBLIC_KEY');
echo "Balance: " . $balance['balance'] . " DZT\n";

// Send DZT payment
$result = $sdk->submit([
    'secretkey'            => getenv('SOFIZPAY_SECRET'),
    'destinationPublicKey' => 'RECIPIENT_PUBLIC_KEY',
    'amount'               => '100.0',
    'memo'                 => 'Invoice #1234'
]);

if ($result['success']) {
    echo "✅ TX: " . $result['transactionHash'] . "\n";
}
```

---

## ⭐ Stellar Payments (DZT)

### `getBalance(string $publicKey)`

```php
$result = $sdk->getBalance('GCAZI...YOUR_PUBLIC_KEY');
// ['success' => true, 'balance' => '1500.0000000', 'asset_code' => 'DZT', ...]
```

### `submit(array $params)` / `sendPayment(...)`

```php
$result = $sdk->submit([
    'secretkey'            => 'SXXX...SECRET',     // 56-char Stellar seed
    'destinationPublicKey' => 'GXXX...RECIPIENT',
    'amount'               => '100.0',              // DZT amount
    'memo'                 => 'Order #5567'         // Optional, max 28 chars
]);
```

### `getTransactions(string $publicKey, int $limit, string $cursor)`

Fetches exhaustive history via Stellar `/operations?join=transactions`. Captures:

| Type | Description |
|------|-------------|
| `sent` | DZT sent from this account |
| `received` | DZT received by this account |
| `trustline` | DZT trustline setup |
| `account_created` | Account creation event |

```php
$history = $sdk->getTransactions('YOUR_PUBLIC_KEY', 100);
foreach ($history['transactions'] as $tx) {
    echo "[{$tx['timestamp']}] {$tx['type']} — {$tx['amount']} DZT\n";
}
```

### `searchTransactionsByMemo(string $publicKey, string $memo)` / `getTransactionByHash(string $hash)`

```php
$results = $sdk->searchTransactionsByMemo('YOUR_PUBLIC_KEY', 'Order #1234');
$tx      = $sdk->getTransactionByHash('abc123...hash');
```

---

## 🏦 CIB Bank Payments

Initiates a CIB/Dahabia bank payment and redirects the customer to the secure SofizPay payment gateway.

**Endpoint:** `GET https://www.sofizpay.com/make-cib-transaction/`


### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `account` | `string` | ✅ | Your merchant Stellar public key |
| `amount` | `string` | ✅ | Amount in Algerian Dinars (DZD) |
| `full_name` | `string` | ✅ | Customer's full name as on card |
| `phone` | `string` | ✅ | Customer's phone number |
| `email` | `string` | ✅ | Customer's email for receipt |
| `return_url` | `string` | ✅ | URL to redirect after payment |
| `memo` | `string` | ✅ | Order reference (e.g. `"Order #1234"`) |
| `redirect` | `string` | ✅ | `"yes"` → auto-redirect; `"no"` → returns URL in response |
| `keep_return_url` | `bool` | ❌ | `true` → adds RSA signature to callback for verification |

### Example — Get Payment URL (`redirect: "no"`)

```php
$cib = $sdk->makeCIBTransaction([
    'account'        => 'YOUR_STELLAR_PUBLIC_KEY',
    'amount'         => '2500',
    'full_name'      => 'Ahmed Benali',
    'phone'          => '0661234567',
    'email'          => 'ahmed@example.com',
    'return_url'     => 'https://yoursite.com/payment/callback',
    'memo'           => 'Order #789',
    'redirect'       => 'no',          // Returns payment URL in response body
]);

if ($cib['success']) {
    $paymentUrl = $cib['data']['payment_url'];
    header('Location: ' . $paymentUrl);
}
```

### Example — Auto-redirect (`redirect: "yes"`)

```php
$cib = $sdk->makeCIBTransaction([
    'account'         => 'YOUR_STELLAR_PUBLIC_KEY',
    'amount'          => '1500',
    'full_name'       => 'Youcef Amrani',
    'phone'           => '0770000000',
    'email'           => 'youcef@example.com',
    'return_url'      => 'https://yoursite.com/payment/callback',
    'memo'            => 'Order #999',
    'redirect'        => 'yes',         // Server sends HTTP 302 redirect
    'keep_return_url' => true,          // Enables RSA-signed callbacks
]);
```

### Check CIB Status

**Endpoint:** `GET https://www.sofizpay.com/cib-transaction-check/`

```php
$status = $sdk->checkCIBStatus('ORDER_NUMBER');
echo $status['data']['status']; // e.g. "success" or "pending"
```

---

## 📱 Digital Services (Missions)

All services use the same endpoint with `encrypted_sk` authentication.

**Endpoint:** `POST https://www.sofizpay.com/services/operation_post`

### Phone Recharge

**Operators:** `djezzy` · `ooredoo` · `mobilis`

```php
$result = $sdk->rechargePhone([
    'encrypted_sk' => 'USER_ENCRYPTED_SK',
    'phone'        => '0661000000',
    'operator'     => 'mobilis',   // 'djezzy' | 'ooredoo' | 'mobilis'
    'amount'       => 100,
    'offer'        => 'prepaid'    // 'prepaid' or 'postpaid'
]);
```

### Internet Recharge

**Operators:** `algerie-telecom` · `djezzy` · `ooredoo` · `mobilis`

```php
$result = $sdk->rechargeInternet([
    'encrypted_sk' => 'USER_ENCRYPTED_SK',
    'phone'        => '0661000000',
    'operator'     => 'algerie-telecom',
    'amount'       => 200,
    'offer'        => 'prepaid'
]);
```

### Game Top-up

**Games:** `freefire` · `pubg`

```php
$result = $sdk->rechargeGame([
    'encrypted_sk' => 'USER_ENCRYPTED_SK',
    'operator'     => 'freefire',  // 'freefire' | 'pubg'
    'player_id'    => '123456789', // In-game player ID
    'amount'       => 500
]);
```

### Bill Payment

**Providers:** `ade` (Water) · `sonelgaz` (Electricity) · `algerie-telecom`

```php
$result = $sdk->payBill([
    'encrypted_sk' => 'USER_ENCRYPTED_SK',
    'operator'     => 'sonelgaz',
    'ref'          => 'BILL_REFERENCE_NUMBER',
    'amount'       => 1500
]);
```

### Get Available Products

**Endpoint:** `GET https://www.sofizpay.com/services/get_products`

```php
$products = $sdk->getProducts('USER_ENCRYPTED_SK');
if ($products['success']) {
    print_r($products['data']);
}
```

---

## 📋 Operation History

### Get History (paginated)

**Endpoint:** `GET https://www.sofizpay.com/operation-history/`

```php
$history = $sdk->getOperationHistory('USER_ENCRYPTED_SK', 10, 0);
// Parameters: encrypted_sk, limit, offset
```

### Get Operation Details

**Endpoint:** `GET https://www.sofizpay.com/operation-details/{operation_id}/`

```php
$details = $sdk->getOperationDetails('OPERATION_ID', 'USER_ENCRYPTED_SK');
```

---

## 🔒 Webhook Signature Verification

SofizPay signs callback payloads with RSA-SHA256 when `keep_return_url = true` is set in the CIB transaction. Always verify before processing.

```php
// In your return_url handler:
$payload = json_decode(file_get_contents('php://input'), true) ?? $_GET;

$isValid = $sdk->verifySignature([
    'message'            => $payload['message'],
    'signature_url_safe' => $payload['signature_url_safe']
]);

if ($isValid) {
    // ✅ Process the confirmed payment
    http_response_code(200);
} else {
    // ❌ Reject — signature mismatch
    http_response_code(400);
}
```

---

## 📤 Response Format

```php
// ✅ Success
['success' => true, 'data' => [...], 'timestamp' => '...']

// ❌ Failure
['success' => false, 'error' => 'Message', 'timestamp' => '...']
```

---

## 🛡️ Security Best Practices

- ❌ Never hardcode secret keys in source files
- ✅ Use `getenv('SOFIZPAY_SECRET')` or `.env` files
- ✅ Always call `verifySignature()` on CIB callbacks
- ✅ Use `encrypted_sk` for all Mission API calls (never the raw secret)
- ✅ Validate `return_url` matches your known domain before processing

---

## 📞 Support

- 🌐 **Website**: [SofizPay.com](https://sofizpay.com)
- 📚 **Official Docs**: [docs.sofizpay.com](https://docs.sofizpay.com)
- 🐛 **Issues**: [GitHub Issues](https://github.com/kenandarabeh/sofizpay-sdk-php/issues)

---

MIT © [SofizPay Team](https://github.com/kenandarabeh) | **Version `1.0.2`**
