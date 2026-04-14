<div align="center">
  <img src="https://github.com/kenandarabeh/sofizpay-sdk/blob/main/assets/sofizpay-logo.png?raw=true" alt="SofizPay Logo" width="200" />

  <h2>SofizPay PHP SDK</h2>
  <p><strong>The official PHP library for secure digital payments on the SofizPay platform.</strong></p>

  [![Packagist Version](https://img.shields.io/packagist/v/sofizpay/sofizpay-sdk-php.svg)](https://packagist.org/packages/sofizpay/sofizpay-sdk-php)
  [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
  [![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
</div>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Methods](#core-methods)
- [Digital Services (Missions)](#digital-services-missions)
- [Bank Integration (CIB)](#bank-integration-cib)
- [Real-time Transaction Streaming](#real-time-transaction-streaming)
- [Response Format](#response-format)
- [Security Best Practices](#security-best-practices)
- [Support](#support)

---

## 🌟 Overview

The SofizPay PHP SDK provides a comprehensive interface for integrating **DZT digital payments** into your PHP applications. It supports on-chain Stellar payments, CIB/Dahabia bank deposits, and digital service recharges (Missions).

**Key Benefits:**
- ⚡ Modern async-compatible wrapper (using Guzzle)
- 🌍 Compatible with Laravel, Symfony, and plain PHP
- 📊 Exhaustive transaction history (Path Payments, Trustlines)
- 🏦 CIB/Dahabia bank deposit link generation
- 📱 Phone, Internet & Game recharges (Mission APIs)

---

## 📦 Installation

### Composer

```bash
composer require sofizpay/sofizpay-sdk-php
```

**Requirements:** 
- PHP `>= 7.4`
- Extensions: `ext-openssl`, `ext-json`
- Dependencies: `guzzlehttp/guzzle`, `soneso/stellar-php-sdk`

---

## 🚀 Quick Start

```php
<?php
require_once 'vendor/autoload.php';
use SofizPay\SofizPaySDK;

// Initialize the SDK
$sdk = new SofizPaySDK();

// 1. Check DZT balance
$balance = $sdk.getBalance('YOUR_PUBLIC_KEY');
if ($balance['success']) {
    echo "💰 Balance: " . $balance['balance'] . " DZT\n";
}

// 2. Send a DZT payment
$result = $sdk.submit([
    'secretkey'            => 'YOUR_SECRET_KEY',      // 56-char Stellar seed starting with 'S'
    'destinationPublicKey' => 'RECIPIENT_PUBLIC_KEY', // Recipient's public key
    'amount'               => '100.0',                // Amount in DZT
    'memo'                 => 'Invoice #1234'         // Optional memo (max 28 chars)
]);

if ($result['success']) {
    echo "✅ Payment sent! Hash: " . $result['transactionHash'] . "\n";
} else {
    echo "❌ Error: " . $result['error'] . "\n";
}
```

---

## 🔧 Core Methods

### `getBalance(string $publicKey)`

Returns the current **DZT** balance for a given Stellar account.

```php
// Fetch balance for a specific public key
$result = $sdk.getBalance('GCAZI...YOUR_PUBLIC_KEY');

// Response
[
  'success'      => true,
  'balance'      => '1500.0000000',
  'publicKey'    => 'GCAZI...',
  'asset_code'   => 'DZT',
  'asset_issuer' => 'GCAZI7YBLIDJWIVEL7ETNAZGPP3LC24NO6KAOBWZHUERXQ7M5BC52DLV',
  'timestamp'    => '2025-07-28T10:30:00+00:00'
]
```

---

### `submit(array $data)`

Submits a DZT payment to the Stellar network.

```php
$result = $sdk.submit([
    'secretkey'            => 'SXXX...YOUR_SECRET',      // 56-char Stellar seed starting with 'S'
    'destinationPublicKey' => 'GXXX...RECIPIENT',         // Recipient's public key
    'amount'               => '250.50',                  // Amount in DZT
    'memo'                 => 'Order #5567'              // Optional memo (max 28 chars)
]);

// Success Response
[
  'success'            => true,
  'transactionId'      => 'abc123...hash',
  'transactionHash'    => 'abc123...hash',
  'amount'             => '250.50',
  'memo'               => 'Order #5567',
  'destinationPublicKey' => 'GXXX...',
  'timestamp'          => '2025-07-28T10:30:00+00:00'
]
```

> ⚠️ **Memo Truncation:** Memos longer than 28 characters are automatically truncated.

---

### `getTransactions(string $publicKey, ?int $limit, ?string $cursor)`

Fetches **exhaustive transaction history** via Stellar. Includes payments, trustlines, and account creation.

```php
// Fetch up to 100 recent transactions
$history = $sdk.getTransactions('YOUR_PUBLIC_KEY', 100);

if ($history['success']) {
    foreach ($history['transactions'] as $tx) {
        // Log each transaction details
        echo "[{$tx['timestamp']}] {$tx['type']} — {$tx['amount']} {$tx['asset_code']}\n";
    }
}
```

---

### `searchTransactionsByMemo(string $publicKey, string $memo, int $limit)`

Performs a case-insensitive search over recent transactions.

```php
// Search for transactions containing the specific memo
$results = $sdk.searchTransactionsByMemo('YOUR_PUBLIC_KEY', 'Order #1234', 10);
if ($results['success']) {
    echo "Found " . count($results['transactions']) . " matches";
}
```

---

## 📱 Digital Services (Missions)

Mission APIs allow users to spend DZT on real-world digital services. All calls require `encrypted_sk`.

### Phone Recharge

```php
$result = $sdk.rechargePhone([
    'encrypted_sk' => 'USER_ENCRYPTED_SECRET_KEY', // User's encrypted secret key
    'phone'        => '0661000000',               // Recipient's phone number
    'operator'     => 'mobilis',                  // 'mobilis' | 'djezzy' | 'ooredoo'
    'amount'       => '100',                      // Recharge amount
    'offer'        => 'Top'                       // Offer type (e.g., 'Top', 'Pix')
]);

if ($result['success']) {
    // Process successful recharge
    print_r($result['data']);
}
```

### Internet Recharge (Idoom 4G)

```php
$result = $sdk.rechargeInternet([
    'encrypted_sk' => 'USER_ENCRYPTED_SECRET_KEY', // User's encrypted secret key
    'phone'        => '0661000000',               // Account phone number
    'operator'     => 'idoom',                    // Network operator
    'amount'       => '2000',                     // Recharge amount
    'offer'        => 'adsl'                      // Offer type (e.g., 'adsl', '4g')
]);
```

### Game Top-up (FreeFire, PUBG)

```php
$result = $sdk.rechargeGame([
    'encrypted_sk' => 'USER_ENCRYPTED_SECRET_KEY',
    'operator'     => 'freefire',   // e.g., 'freefire', 'pubg'
    'playerId'     => '123456789',
    'amount'       => '500',        // 'amount' from getProducts()
    'offer'        => 'diamonds'    // 'name' from getProducts()
]);
```

### Bill Payment

```php
$result = $sdk.payBill([
    'encrypted_sk' => 'USER_ENCRYPTED_SECRET_KEY',
    'operator'     => 'sonelgaz', // e.g., 'sonelgaz', 'ade'
    'bill_id'      => 'BILL_999',
    'amount'       => '1500'
]);
```

### Operation History & Details

```php
// Recent operations (paginated)
$history = $sdk.getOperationHistory('USER_ENCRYPTED_SK', 10, 0);
if ($history['success']) {
    print_r($history['data']);
}

// Details of a specific operation
$details = $sdk.getOperationDetails('OPERATION_ID', 'USER_ENCRYPTED_SK');
```

### Get Available Products

```php
// Fetch list of available recharging products
$products = $sdk.getProducts('USER_ENCRYPTED_SK');
if ($products['success']) {
    // List all available services and offers
    print_r($products['data']);
}
```

> [!TIP]
> Use the product `name` for the `offer` field and the product `amount` for the `amount` field.

---

## 🏦 Bank Integration (CIB)

Generate secure Dahabia/CIB bank payment links.

```php
$result = $sdk.makeCIBTransaction([
    'account'    => 'YOUR_STELLAR_PUBLIC_KEY',    // Your merchant Stellar public key
    'amount'     => 2500,                         // Amount in DZD (Algerian Dinars)
    'full_name'  => 'Ahmed Benali',               // Customer's full name
    'phone'      => '0661234567',                 // Customer's phone number
    'email'      => 'ahmed@example.com',           // Customer's email
    'memo'       => 'Order #789',                 // Internal order reference
    'return_url' => 'https://yoursite.com/callback', // Redirect after payment
    'redirect'   => 'no'                          // 'yes' for auto-redirect
]);

if ($result['success']) {
    // Get the generated hosted payment URL
    $paymentUrl = $result['data']['payment_url'];
    // Redirect the user to the payment gateway
}
```

### 🧪 Sandbox Environment

For safe testing without real money, use the dedicated sandbox methods. These methods always point to the SofizPay Sandbox environment.

```php
// 1. Generate a sandbox payment link
$result = $sdk->makeSandboxCIBTransaction([
    'account'   => 'YOUR_PUBLIC_KEY',
    'amount'    => 150.0,
    'full_name' => 'Sandbox Tester',
    'phone'     => '0555000000',
    'email'     => 'sandbox@example.com'
]);

if ($result['success']) {
    echo "Sandbox URL: " . $result['data']['payment_url'] . "\n";
    $cibId = $result['data']['cib_transaction_id'];

    // 2. Check sandbox status
    $status = $sdk->checkSandboxCIBStatus($cibId);
    echo "Sandbox Status: " . $status['data']['status'] . "\n";
}
```

### Check CIB Status (Production)

To monitor the progress of a real CIB/Dahabia payment, use the `cib_transaction_id` returned in the `data` of the `makeCIBTransaction` response.

```php
// Check the status of a specific CIB transaction using its ID
$status = $sdk->checkCIBStatus('CIB_TRANSACTION_ID');
if ($status['success']) {
    // Current status (e.g., 'success', 'pending', 'failed')
    echo "Status: " . $status['data']['status'];
}
```

### 💡 Best Practice: Secure Order Flow

For maximum security, never expose the `cib_transaction_id` to the end-user. Always store it in your database and retrieve it server-side.

```php
// 1. Initiate payment and save the ID hidden from the user
$result = $sdk.makeCIBTransaction([
    'account' => 'YOUR_PUBLIC_KEY',
    'amount'  => 5000,
    'memo'    => 'Order #9988'
]);

if ($result['success']) {
    $cibId = $result['data']['cib_transaction_id'];
    // ✅ SAVE to database: UPDATE orders SET cib_hash = '$cibId' WHERE id = 9988;
    
    // Redirect user to payment page
    header('Location: ' . $result['data']['payment_url']);
}

// 2. When checking status, retrieve from database
$order = $db->getRepository(Order::class)->find(9988);
$status = $sdk.checkCIBStatus($order->getCibHash());

if ($status['success'] && $status['data']['status'] === 'success') {
    // ✅ Mark order as PAID in your database
}
```

---

## 🔴 Real-time Transaction Streaming

The PHP SDK allows you to start/stop transaction polling (simulated streaming).

```php
// Monitor account activity (simulated via polling)
$status = $sdk.getStreamStatus('YOUR_PUBLIC_KEY');
```

> Note: Real-time streaming is best handled via the JavaScript SDK in the browser or via Node.js for production environments.

---

## 📤 Response Format

All methods return an associative array:

```php
// ✅ Success
[
  'success'   => true,
  'data'      => [...], // method-specific data fields
  'timestamp' => '2025-07-28T10:30:00+00:00'
]

// ❌ Failure
[
  'success'   => false,
  'error'     => 'Error message describing the failure',
  'timestamp' => '2025-07-28T10:30:00+00:00'
]
```


---

## 🛡️ Security Best Practices

- ❌ Never expose secret keys in client-side code.
- ✅ Use environment variables (`$_ENV` or `getenv()`).
- ✅ Keep `encrypted_sk` secure in your backend database.
- ✅ Always use HTTPS for API communications.

---

## 📞 Support

- 🌐 **Website**: [SofizPay.com](https://sofizpay.com)
- 📚 **Full Docs**: [docs.sofizpay.com](https://docs.sofizpay.com)
- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/kenandarabeh/sofizpay-sdk-php/issues)

---

## License

MIT © [SofizPay Team](https://github.com/kenandarabeh)

**Built with ❤️ for PHP developers | Version `1.0.2`**

