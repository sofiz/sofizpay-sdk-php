# SofizPay PHP SDK

A comprehensive PHP SDK for SofizPay that provides seamless integration with the Stellar network for DZT (Dzuut) token operations. Built on the reliable Soneso Stellar SDK, this SDK offers a simple yet powerful interface for managing DZT transactions, account balances, and payment history.

## 🚀 Features

- ✅ **DZT Payment Operations**: Send DZT tokens with optional memos
- ✅ **Account Management**: Check balances, verify trustlines, and account existence
- ✅ **Payment History**: Retrieve transaction history with pagination
- ✅ **Memo Search**: Find transactions by memo text
- ✅ **Multi-Network Support**: Mainnet (default) and testnet compatibility
- ✅ **Error Handling**: Comprehensive exception system
- ✅ **Service Architecture**: Modular design for maintainability
- ✅ **Fixed Asset Configuration**: Pre-configured DZT asset settings

## 🔧 Requirements

- **PHP**: 8.0 or higher
- **Composer**: For dependency management
- **Extensions**: cURL, JSON
- **Stellar Account**: With DZT trustline for live operations

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

// Check DZT balance
$balance = $client->getDztBalance('GA_YOUR_ACCOUNT_ID_HERE');
if ($balance) {
    echo "DZT Balance: " . $balance->getBalance() . " DZT\n";
}

// Send DZT payment
$transactionHash = $client->sendPayment(
    'SA_YOUR_SECRET_KEY_HERE',          // Source account secret key
    'GA_DESTINATION_ACCOUNT_ID_HERE',   // Destination account ID
    '100.50',                           // Amount in DZT
    'Payment memo'                      // Optional memo
);

echo "Payment successful! Hash: {$transactionHash}\n";
```

## 📖 Complete Example

Run the comprehensive example that demonstrates all SDK features:

```bash
php example.php
```

This example showcases:
- SDK initialization and configuration
- All account operations (balance checking, trustline verification)
- Payment operations (sending DZT, payment history)
- Transaction search by memo
- Error handling patterns
- Service-based access methods

## 🛠 API Reference

### Client Initialization

```php
// Default: Mainnet
$client = new SofizPayClient();

// Explicit network specification
$client = new SofizPayClient('mainnet');  // or 'testnet'

// With custom HTTP client
$httpClient = new GuzzleHttp\Client(['timeout' => 30]);
$client = new SofizPayClient('mainnet', $httpClient);
```

### Account Operations

#### Check DZT Balance
```php
$balance = $client->getDztBalance('GA_ACCOUNT_ID');
if ($balance) {
    echo "Balance: " . $balance->getBalance() . " DZT\n";
    echo "Authorized: " . ($balance->isAuthorized() ? 'Yes' : 'No') . "\n";
    echo "Limit: " . ($balance->getLimit() ?: 'Unlimited') . "\n";
}
```


#### Check Account Existence
```php
$exists = $client->accounts()->accountExists('GA_ACCOUNT_ID');
echo $exists ? "Account exists" : "Account not found";
```

#### Verify DZT Trustline
```php
$hasTrustline = $client->accounts()->hasDztTrustline('GA_ACCOUNT_ID');
echo $hasTrustline ? "DZT trustline established" : "No DZT trustline";
```

### Payment Operations

#### Send DZT Payment
```php
try {
    $hash = $client->sendPayment(
        'SA_SOURCE_SECRET_KEY',      // Source secret key
        'GA_DESTINATION_ACCOUNT',     // Destination account
        '250.75',                     // Amount in DZT
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
    echo "Amount: " . $payment->getAmount() . " DZT\n";
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

### Service-Based Access

The SDK provides service-based access for modular functionality:

```php
// Payment Service
$paymentService = $client->payments();
$hash = $paymentService->sendPayment($secret, $destination, $amount);
$history = $paymentService->getPaymentHistory($accountId);

// Account Service  
$accountService = $client->accounts();
$balance = $accountService->getDztBalance($accountId);
$exists = $accountService->accountExists($accountId);
```

## 🔒 Configuration

### DZT Asset Configuration

The SDK is pre-configured with the official DZT asset:

- **Asset Code**: `DZT`
- **Issuer**: `GCAZI7YBLIDJWIVEL7ETNAZGPP3LC24NO6KAOBWZHUERXQ7M5BC52DLV`
- **Network**: Mainnet (default)

### Network Configuration

```php
// Mainnet (default)
$client = new SofizPayClient('mainnet');


// Check current network
echo "Current network: " . $client->getNetwork() . "\n";
```

## ⚠️ Error Handling

The SDK provides comprehensive error handling with custom exceptions:

### Exception Types

- **`ValidationException`**: Invalid input parameters
- **`NetworkException`**: Stellar network errors
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
    // Handle network/Stellar errors (account not found, insufficient funds, etc.)
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

To test with real Stellar accounts:

1. Replace placeholder credentials in `example.php`
2. Ensure accounts have DZT trustlines established
3. Have sufficient XLM for transaction fees
4. Run the example again

### Prerequisites for Live Testing

- Valid Stellar account with secret key
- DZT trustline established on the account
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
│   │   └── DztAsset.php            # DZT asset model
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
- **Stellar Integration**: Built on proven Soneso Stellar SDK
- **Type Safety**: Full PHP type declarations
- **Documentation**: Extensive inline documentation

## 🤝 Contributing

This SDK is developed for SofizPay's DZT token operations. For issues or feature requests, please contact the SofizPay development team.

## 📄 License

This project is proprietary software developed for SofizPay.

## 🔗 Dependencies

- **[soneso/stellar-php-sdk](https://github.com/Soneso/stellar-php-sdk)**: Stellar network integration
- **[guzzlehttp/guzzle](https://github.com/guzzle/guzzle)**: HTTP client library

## 📞 Support

For technical support or questions:

- Review the comprehensive `example.php` for usage patterns
- Check error messages and exception types for troubleshooting
- Ensure proper Stellar account setup and DZT trustlines

---

**Built with ❤️ for the SofizPay ecosystem**

```php
<?php

require_once 'vendor/autoload.php';

use Sofiz\SofizPay\SofizPayClient;

// Initialize for mainnet (default)
$client = new SofizPayClient();

// Initialize for testnet (for development/testing)
$client = new SofizPayClient('testnet');
```

## Core Features

### 1. Send DZT Payments

```php
// Send a DZT payment
$transactionHash = $client->sendPayment(
    'SA_SOURCE_SECRET_KEY',     // Source account secret key
    'GA_DESTINATION_ACCOUNT',   // Destination account ID
    '100.50',                   // Amount in DZT
    'Payment memo'              // Optional memo
);

echo "Payment sent! Hash: $transactionHash";
```

### 2. Get Account DZT Balance

```php
// Get DZT balance for an account
$balance = $client->getDztBalance('GA_ACCOUNT_ID');

if ($balance) {
    echo "Balance: " . $balance->getBalance() . " DZT";
    echo "Authorized: " . ($balance->isAuthorized() ? 'Yes' : 'No');
} else {
    echo "Account has no DZT balance (no trustline)";
}

// Get all balances
$allBalances = $client->accounts()->getAllBalances('GA_ACCOUNT_ID');
foreach ($allBalances as $balance) {
    echo $balance->getAssetCode() . ": " . $balance->getBalance();
}
```

### 3. Get Payment History

```php
// Get recent DZT payments for an account
$payments = $client->getPaymentHistory('GA_ACCOUNT_ID', 20); // Last 20 payments

foreach ($payments as $payment) {
    echo "From: " . $payment->getFromAccount();
    echo "To: " . $payment->getToAccount();
    echo "Amount: " . $payment->getAmount() . " DZT";
    echo "Memo: " . $payment->getMemo();
    echo "Date: " . $payment->getCreatedAt();
}

// Pagination support
$cursor = $payments[count($payments) - 1]->getPagingToken();
$nextPayments = $client->getPaymentHistory('GA_ACCOUNT_ID', 20, $cursor);
```

### 4. Find Transactions by Memo

```php
// Find DZT transactions with specific memo
$payments = $client->getTransactionsByMemo('invoice-12345', 50);

foreach ($payments as $payment) {
    echo "Transaction: " . $payment->getTransactionHash();
    echo "Amount: " . $payment->getAmount() . " DZT";
    echo "From: " . $payment->getFromAccount();
    echo "To: " . $payment->getToAccount();
}
```

## Service-Based Usage

You can also use the SDK services directly for more advanced usage:

```php
// Payment operations
$paymentService = $client->payments();
$hash = $paymentService->sendPayment($secretKey, $destination, $amount, $memo);
$history = $paymentService->getPaymentHistory($accountId);
$memoTxs = $paymentService->getTransactionsByMemo('search-memo');

// Account operations  
$accountService = $client->accounts();
$balance = $accountService->getDztBalance($accountId);
$allBalances = $accountService->getAllBalances($accountId);
$exists = $accountService->accountExists($accountId);
$hasTrustline = $accountService->hasDztTrustline($accountId);
```

## Error Handling

The SDK provides specific exception types:

```php
use Sofiz\SofizPay\Exceptions\ValidationException;
use Sofiz\SofizPay\Exceptions\NetworkException;

try {
    $hash = $client->sendPayment($secretKey, $destination, $amount);
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage();
} catch (Exception $e) {
    echo "General error: " . $e->getMessage();
}
```

## Configuration

### Network Selection

```php
// Mainnet (default) - for production use
$client = new SofizPayClient();
// or explicitly
$client = new SofizPayClient('mainnet');

// Testnet - for development and testing
$client = new SofizPayClient('testnet');
```

### DZT Asset Configuration

The SDK is pre-configured with the official DZT asset:
- **Asset Code**: `DZT`
- **Issuer Account**: `GCAZI7YBLIDJWIVEL7ETNAZGPP3LC24NO6KAOBWZHUERXQ7M5BC52DLV`

This configuration is fixed and cannot be changed, ensuring consistency across all SofizPay integrations.

## Development

### Running Tests

```bash
composer test
# or
vendor/bin/phpunit
```

### Code Quality

```bash
# Static analysis
composer phpstan

# Code style check
composer cs-check

# Code style fix
composer cs-fix
```

### Directory Structure

```
src/
├── SofizPayClient.php          # Main SDK client
├── Models/
│   ├── DztAsset.php           # DZT asset representation
│   ├── Payment.php            # Payment model
│   └── Balance.php            # Balance model
├── Services/
│   ├── PaymentService.php     # Payment operations
│   └── AccountService.php     # Account operations
└── Exceptions/
    ├── SofizPayException.php  # Base exception
    ├── NetworkException.php   # Network errors
    └── ValidationException.php # Validation errors

tests/                          # Unit tests
examples/                       # Usage examples
composer.json                   # Dependencies and scripts
phpunit.xml                     # PHPUnit configuration
phpstan.neon                    # PHPStan configuration
```

## API Reference

### SofizPayClient

Main client class providing access to all SDK functionality.

**Constructor Parameters:**
- `$network` (string): 'testnet' or 'mainnet' (default: 'mainnet')
- `$httpClient` (HttpClient|null): Optional custom HTTP client

**Fixed Configuration:**
- DZT Asset Issuer: `GCAZI7YBLIDJWIVEL7ETNAZGPP3LC24NO6KAOBWZHUERXQ7M5BC52DLV`
- Default Network: `mainnet`

**Methods:**
- `sendPayment(string $sourceSecretKey, string $destinationAccountId, string $amount, ?string $memo = null): string`
- `getPaymentHistory(string $accountId, int $limit = 20, ?string $cursor = null): Payment[]`
- `getTransactionsByMemo(string $memo, int $limit = 20): Payment[]`
- `getDztBalance(string $accountId): ?Balance`
- `payments(): PaymentService`
- `accounts(): AccountService`

### Models

#### Payment
Represents a DZT payment transaction.

**Properties:**
- `getTransactionHash(): string`
- `getFromAccount(): string`
- `getToAccount(): string`
- `getAmount(): string`
- `getAssetCode(): string`
- `getAssetIssuer(): string`
- `getMemo(): ?string`
- `getCreatedAt(): string`
- `isSuccessful(): bool`
- `getPagingToken(): ?string`

#### Balance
Represents an account balance for DZT or other assets.

**Properties:**
- `getAccountId(): string`
- `getBalance(): string`
- `getAssetCode(): string`
- `getAssetIssuer(): string`
- `getLimit(): ?string`
- `isAuthorized(): bool`

#### DztAsset
Represents the DZT asset configuration.

**Properties:**
- `getAsset(): Asset` (Stellar SDK Asset)
- `getIssuerAccountId(): string`
- `getAssetCode(): string`

## Examples

See the `examples/` directory for complete usage examples:

- `basic_usage.php` - SDK initialization and basic operations
- `send_payment.php` - Sending DZT payments
- `get_balance.php` - Checking account balances
- `get_payment_history.php` - Retrieving payment history
- `get_transactions_by_memo.php` - Finding transactions by memo

## Features

- Built on Stellar PHP SDK v1.8+
- Support for both testnet and mainnet
- Comprehensive DZT asset handling
- Payment sending with memo support
- Payment history retrieval with pagination
- Transaction search by memo
- Account balance checking
- Comprehensive error handling
- PSR-12 compliant code
- Full test coverage
- Static analysis with PHPStan

## License

MIT