# SofizPay PHP SDK

A comprehensive PHP SDK for SofizPay's DZD token operations on the Stellar blockchain. This SDK provides seamless integration for DZD token operations, built on a reliable foundation with a simple yet powerful interface for managing DZD transactions, account balances, and payment history.

## 🚀 Features

- ✅ **DZD Payment Operations**: Send DZD tokens with optional memos
- ✅ **CIB Payment Integration**: Create CIB transactions and verify payment callbacks
- ✅ **Account Management**: Check balances, verify trustlines, and account existence
- ✅ **Payment History**: Retrieve transaction history with pagination
- ✅ **Memo Search**: Find transactions by memo text
- ✅ **Signature Verification**: Verify payment callback signatures with RSA
- ✅ **Multi-Network Support**: Mainnet (default)
- ✅ **Error Handling**: Comprehensive exception system
- ✅ **Service Architecture**: Modular design for maintainability
- ✅ **Fixed Asset Configuration**: Pre-configured DZD asset settings

## 🔧 Requirements

- **PHP**: 8.0 or higher
- **Composer**: For dependency management
- **Extensions**: cURL, JSON
- **Account**: With DZD trustline for live operations

## 📦 Installation

Install the SDK and its dependencies using Composer:

```bash
composer install
```

## 🏃‍♂️ Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use Sofiz\SofizPay\SofizPayClient;

// Initialize the client (defaults to mainnet)
$client = new SofizPayClient();

// Check DZD balance
$balance = $client->getDzdBalance('GA_YOUR_ACCOUNT_ID_HERE');
if ($balance) {
    echo "DZD Balance: " . $balance->getBalance() . " DZD\n";
}

// Send DZD payment
$transactionHash = $client->sendPayment(
    'SA_YOUR_SECRET_KEY_HERE',          // Source account secret key
    'GA_DESTINATION_ACCOUNT_ID_HERE',   // Destination account ID
    '100.50',                           // Amount in DZD
    'Payment memo'                      // Optional memo
);

echo "Payment successful! Hash: {$transactionHash}\n";
```

## 📖 Complete Examples

Run the comprehensive example that demonstrates all SDK features:

```bash
php example.php
```

For CIB payment integration specifically:

```bash
php cib_example.php
```

These examples showcase:
- SDK initialization and configuration
- All account operations (balance checking, trustline verification)  
- Payment operations (sending DZD, payment history)
- CIB payment creation and signature verification
- Transaction search by memo
- Error handling patterns
- Service-based access methods

## 🛠 API Reference

### Client Initialization

```php
// Default: Mainnet with default API endpoint
$client = new SofizPayClient();

// Explicit network specification
$client = new SofizPayClient('mainnet');  

// With custom HTTP client
$httpClient = new GuzzleHttp\Client(['timeout' => 30]);
$client = new SofizPayClient('mainnet', $httpClient);

// Full configuration
$client = new SofizPayClient(
    network: 'mainnet',
    httpClient: $httpClient,
    baseUrl: 'https://www.sofizpay.com'
);
```

### Account Operations

#### Check DZD Balance
```php
$balance = $client->getDzdBalance('GA_ACCOUNT_ID');
if ($balance) {
    echo "Balance: " . $balance->getBalance() . " DZD\n";
    echo "Authorized: " . ($balance->isAuthorized() ? 'Yes' : 'No') . "\n";
    echo "Limit: " . ($balance->getLimit() ?: 'Unlimited') . "\n";
}
```
```php
#### Check Account Existence
```php
$exists = $client->accounts()->accountExists('GA_ACCOUNT_ID');
echo $exists ? "Account exists" : "Account not found";
```

#### Verify DZD Trustline
```php
$hasTrustline = $client->accounts()->hasDzdTrustline('GA_ACCOUNT_ID');
echo $hasTrustline ? "DZD trustline established" : "No DZD trustline";
```

### Payment Operations

#### Send DZD Payment
```php
try {
    $hash = $client->sendPayment(
        'SA_SOURCE_SECRET_KEY',      // Source secret key
        'GA_DESTINATION_ACCOUNT',     // Destination account
        '250.75',                     // Amount in DZD
        'Invoice #INV-2024-001'       // Optional memo maximum 28 characters
    );
    echo "Payment successful: {$hash}\n";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
}
```

#### Get Payment History
```php
// Get latest 10 payments
$payments = $client->getPaymentHistory('GA_ACCOUNT_ID', 10);

foreach ($payments as $payment) {
    echo "Amount: " . $payment->getAmount() . " DZD\n";
    echo "From: " . $payment->getFromAccount() . "\n";
    echo "To: " . $payment->getToAccount() . "\n";
    echo "Memo: " . ($payment->getMemo() ?: 'None') . "\n";
    echo "Date: " . $payment->getCreatedAt() . "\n";
    echo "Hash: " . $payment->getTransactionHash() . "\n";
    echo "---\n";
}
```

#### Search by Memo
```php
$payments = $client->getTransactionsByMemo('GA_ACCOUNT_ID', 'invoice-2024', 20);
echo "Found " . count($payments) . " transactions with memo 'invoice-2024'\n";
```

### CIB Payment Operations

#### Create CIB Transaction
```php
try {
    $cibTransaction = $client->createCibTransaction(
        account: 'GA_YOUR_STELLAR_ACCOUNT',
        amount: '150.00',                           // Amount in DZD
        fullName: 'Ahmed Ben Ali',                  // Customer full name
        phone: '+213555123456',                     // Customer phone number
        email: 'ahmed@example.com',                 // Customer email
        returnUrl: 'https://yoursite.com/callback', // Callback URL (optional)
        memo: 'Order #12345',                       // Optional memo
        redirect: false                             // Set to true for immediate redirect
    );
    
    echo "Transaction created successfully!\n";
    echo "Payment URL: " . $cibTransaction->getPaymentUrl() . "\n";
    echo "Transaction ID: " . $cibTransaction->getTransactionId() . "\n";
    echo "CIB Transaction ID: " . $cibTransaction->getCibTransactionId() . "\n";
    
    // Redirect user to payment page
    header('Location: ' . $cibTransaction->getPaymentUrl());
    exit;
    
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
} catch (SofizPayException $e) {
    echo "SofizPay error: " . $e->getMessage() . "\n";
}
```

#### Verify Payment Callback
```php
// In your callback endpoint (e.g., callback.php)
try {
    // Get the full callback URL
    $callbackUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Load  public key
    $PublicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1N+bDPxpqeB9QB0affr/
02aeRXAAnqHuLrgiUlVNdXtF7t+2w8pnEg+m9RRlc+4YEY6UyKTUjVe6k7v2p8Jj
UItk/fMNOEg/zY222EbqsKZ2mF4hzqgyJ3QHPXjZEEqABkbcYVv4ZyV2Wq0x0ykI
+Hy/5YWKeah4RP2uEML1FlXGpuacnMXpW6n36dne3fUN+OzILGefeRpmpnSGO5+i
JmpF2mRdKL3hs9WgaLSg6uQyrQuJA9xqcCpUmpNbIGYXN9QZxjdyRGnxivTE8awx
THV3WRcKrP2krz3ruRGF6yP6PVHEuPc0YDLsYjV5uhfs7JtIksNKhRRAQ16bAsj/
9wIDAQAB
-----END PUBLIC KEY-----';
    
    // Verify the signature
    $verification = $client->verifyCibSignature($callbackUrl, $PublicKey);
    
    if ($verification->isValid() && $verification->isSuccessful()) {
        // Payment successful - fulfill the order
        $transactionId = $verification->getTransactionId();
        $amount = $verification->getAmount();
        
        // Update your database
        updateOrderStatus($transactionId, 'paid');
        sendConfirmationEmail($customerEmail);
        
        // Redirect to success page
        header('Location: /success?order=' . $transactionId);
        
    } elseif ($verification->isValid() && !$verification->isSuccessful()) {
        // Payment failed but signature is valid
        header('Location: /payment-failed');
        
    } else {
        // Invalid signature - security alert!
        error_log('Invalid CIB signature detected: ' . $verification->getError());
        header('Location: /error');
    }
    
} catch (Exception $e) {
    error_log('CIB callback error: ' . $e->getMessage());
    header('Location: /error');
}
```

#### CIB Service Direct Access
```php
// Get CIB service for advanced operations
$cibService = $client->cib();

// Parse return URL parameters
$params = $cibService->parseReturnUrl($callbackUrl);

// Quick success check (without signature verification)
$isSuccessful = $cibService->isPaymentSuccessful($callbackUrl);

// Note: Always verify signatures for security!
```

### Service-Based Access

The SDK provides service-based access for modular functionality:

```php
// Payment Service
$paymentService = $client->payments();
$hash = $paymentService->sendPayment($secret, $destination, $amount);
$history = $paymentService->getPaymentHistory($accountId);

// Account Service  
$accountService = $client->accounts();
$balance = $accountService->getDzdBalance($accountId);
$exists = $accountService->accountExists($accountId);
```



## ⚠️ Error Handling

The SDK provides comprehensive error handling with custom exceptions:

### Exception Types

- **`ValidationException`**: Invalid input parameters
- **`NetworkException`**: blockchain network errors
- **`SofizPayException`**: Base exception class

### Error Handling Pattern

```php
use Sofiz\SofizPay\Exceptions\ValidationException;
use Sofiz\SofizPay\Exceptions\NetworkException;
use Sofiz\SofizPay\Exceptions\SofizPayException;

try {
    $result = $client->sendPayment($secret, $destination, $amount);
} catch (ValidationException $e) {
    // Handle validation errors (invalid keys, amounts, etc.)
    echo "Validation Error: " . $e->getMessage();
} catch (NetworkException $e) {
    // Handle network/blockchain errors (account not found, insufficient funds, etc.)
    echo "Network Error: " . $e->getMessage();
} catch (SofizPayException $e) {
    // Handle other SDK errors
    echo "SDK Error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle unexpected errors
    echo "Unexpected Error: " . $e->getMessage();
}
```

## 🧪 Testing

### Demo Mode

Run the example script to see all features in demo mode:

```bash
php example.php
```

### Live Testing

To test with real blockchain accounts:

1. Replace placeholder credentials in `example.php`
2. Ensure accounts have DZD trustlines established
3. Have sufficient XLM for transaction fees
4. Run the example again

### Prerequisites for Live Testing

- Valid blockchain account with secret key
- DZD trustline established on the account
- Sufficient XLM balance for transaction fees (≥0.00001 XLM per transaction)

## 🏗️ Architecture

### Project Structure

```
sofizpay-sdk-php/
├── src/
│   ├── SofizPayClient.php          # Main SDK client
│   ├── Services/
│   │   ├── AccountService.php       # Account operations
│   │   └── PaymentService.php       # Payment operations
│   ├── Models/
│   │   ├── Balance.php             # Balance data model
│   │   ├── Payment.php             # Payment data model
│   │   └── DztAsset.php            # DZD asset model
│   └── Exceptions/
│       ├── SofizPayException.php    # Base exception
│       ├── ValidationException.php  # Validation errors
│       └── NetworkException.php     # Network errors
├── example.php                     # Comprehensive example
├── composer.json                   # Dependencies
└── README.md                       # This file
```

### Design Principles

- **Service-Oriented**: Modular services for different operations  
- **Exception Safety**: Comprehensive error handling
- **blockchain Integration**: Built on proven Soneso blockchain SDK
- **Type Safety**: Full PHP type declarations
- **Documentation**: Extensive inline documentation

## 🤝 Contributing

This SDK is developed for SofizPay's DZD token operations. For issues or feature requests, please contact the SofizPay development team.

## 📄 License

This project is proprietary software developed for SofizPay.

## 🔗 Dependencies

- **[soneso/blockchain-php-sdk](https://github.com/Soneso/blockchain-php-sdk)**: blockchain network integration
- **[guzzlehttp/guzzle](https://github.com/guzzle/guzzle)**: HTTP client library

## 📞 Support

For technical support or questions:

- Review the comprehensive `example.php` for usage patterns
- Check error messages and exception types for troubleshooting
- Ensure proper blockchain account setup and DZD trustlines

---

**Built with ❤️ for the SofizPay ecosystem**