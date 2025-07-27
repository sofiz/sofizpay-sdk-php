<?php
/**
 * SofizPay PHP SDK - Complete Usage Example
 * 
 * This example demonstrates all the functionality available in the SofizPay SDK
 * for interacting with DZT (Dzuut) tokens on the Stellar network.
 * 
 * Features demonstrated:
 * - SDK initialization and configuration
 * - Account operations (balance checking, trustline verification)
 * - Payment operations (sending DZT, payment history)
 * - Transaction search by memo
 * - Error handling and validation
 * 
 * Prerequisites:
 * - Composer dependencies installed (run: composer install)
 * - Valid Stellar account with DZT trustline (for testing)
 * 
 * @author SofizPay Team
 * @version 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

use Sofiz\SofizPay\SofizPayClient;
use Sofiz\SofizPay\Exceptions\SofizPayException;
use Sofiz\SofizPay\Exceptions\NetworkException;
use Sofiz\SofizPay\Exceptions\ValidationException;

// =============================================================================
// CONFIGURATION & INITIALIZATION
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   SofizPay PHP SDK - Demo                     ║\n";
echo "║                     Complete Usage Example                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Initialize SofizPay client
// Network options: 'mainnet' (default) or 'testnet'
$network = 'mainnet'; // Change to 'testnet' for testing
$client = new SofizPayClient($network);

echo "🚀 SDK Initialized Successfully!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Network: " . $client->getNetwork() . "\n";
echo "DZT Asset Code: " . $client->getDztAsset()->getAssetCode() . "\n";
echo "DZT Issuer: " . $client->getDztAsset()->getIssuerAccountId() . "\n\n";

// =============================================================================
// EXAMPLE CONFIGURATION
// =============================================================================

// ⚠️  IMPORTANT: Real credentials for testing
// Replace these with your actual Stellar credentials
$exampleSourceSecret = 'your_secret_key';
$exampleDestinationId = 'GXXXX'; // Example destination account
$exampleAccountToCheck = 'GXXXX'; 
$testAmount = '1.00';
$testMemo = 'SofizPay SDK Test Payment';

// Check if we're using real credentials or examples
$usingRealCredentials = !str_contains($exampleSourceSecret, 'YOUR_SECRET_KEY_HERE');

if (!$usingRealCredentials) {
    echo "⚠️  DEMO MODE: Using placeholder credentials\n";
    echo "   To test with real transactions, replace the example credentials above.\n\n";
}

// =============================================================================
// 1. ACCOUNT OPERATIONS
// =============================================================================

echo "📊 ACCOUNT OPERATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// 1.1 Check if account exists
echo "1.1 Checking account existence...\n";
try {
    if ($usingRealCredentials) {
        $accountExists = $client->accounts()->accountExists($exampleAccountToCheck);
        echo "✓ Account " . substr($exampleAccountToCheck, 0, 10) . "... " . 
             ($accountExists ? "EXISTS" : "NOT FOUND") . "\n";
    } else {
        echo "   [DEMO] Would check if account exists on Stellar network\n";
    }
} catch (Exception $e) {
    echo "✗ Account check failed: " . $e->getMessage() . "\n";
}

// 1.2 Check DZT trustline
echo "\n1.2 Checking DZT trustline...\n";
try {
    if ($usingRealCredentials) {
        $hasTrustline = $client->accounts()->hasDztTrustline($exampleAccountToCheck);
        echo "✓ DZT Trustline: " . ($hasTrustline ? "ESTABLISHED" : "NOT FOUND") . "\n";
    } else {
        echo "   [DEMO] Would check if account has DZT trustline\n";
    }
} catch (Exception $e) {
    echo "✗ Trustline check failed: " . $e->getMessage() . "\n";
}

// 1.3 Get DZT balance
echo "\n1.3 Getting DZT balance...\n";
try {
    if ($usingRealCredentials) {
        $dztBalance = $client->getDztBalance($exampleAccountToCheck);
        if ($dztBalance) {
            echo "✓ DZT Balance: " . $dztBalance->getBalance() . " DZT\n";
            echo "  - Asset Code: " . $dztBalance->getAssetCode() . "\n";
            echo "  - Authorized: " . ($dztBalance->isAuthorized() ? "Yes" : "No") . "\n";
            echo "  - Limit: " . ($dztBalance->getLimit() ?: "Unlimited") . "\n";
        } else {
            echo "✗ No DZT balance found (account may not have DZT trustline)\n";
        }
    } else {
        echo "   [DEMO] Would fetch DZT balance: 1,234.56 DZT\n";
        echo "   [DEMO] Asset authorized: Yes\n";
    }
} catch (Exception $e) {
    echo "✗ Balance check failed: " . $e->getMessage() . "\n";
}

// 1.4 Get all balances
echo "\n1.4 Getting all account balances...\n";
try {
    if ($usingRealCredentials) {
        $allBalances = $client->accounts()->getAllBalances($exampleAccountToCheck);
        echo "✓ Found " . count($allBalances) . " asset balance(s):\n";
        foreach ($allBalances as $balance) {
            $assetName = $balance->getAssetCode();
            if ($balance->getAssetIssuer() === 'native') {
                $assetName = 'XLM (Lumens)';
            }
            echo "  - {$assetName}: " . $balance->getBalance() . "\n";
        }
    } else {
        echo "   [DEMO] Would show all asset balances:\n";
        echo "   [DEMO] - XLM (Lumens): 500.00\n";
        echo "   [DEMO] - DZT: 1,234.56\n";
    }
} catch (Exception $e) {
    echo "✗ All balances check failed: " . $e->getMessage() . "\n";
}

// =============================================================================
// 2. PAYMENT OPERATIONS
// =============================================================================

echo "\n\n💸 PAYMENT OPERATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// 2.1 Send DZT payment
echo "2.1 Sending DZT payment...\n";
try {
    if ($usingRealCredentials) {
        echo "Sending {$testAmount} DZT from source to " . substr($exampleDestinationId, 0, 10) . "...\n";
        $transactionHash = $client->sendPayment(
            $exampleSourceSecret,
            $exampleDestinationId,
            $testAmount,
            $testMemo
        );
        echo "✓ Payment successful!\n";
        echo "  - Transaction Hash: {$transactionHash}\n";
        echo "  - Amount: {$testAmount} DZT\n";
        echo "  - Memo: {$testMemo}\n";
    } else {
        echo "   [DEMO] Would send {$testAmount} DZT payment\n";
        echo "   [DEMO] Transaction Hash: abc123def456...\n";
        echo "   [DEMO] Memo: {$testMemo}\n";
    }
} catch (ValidationException $e) {
    echo "✗ Validation Error: " . $e->getMessage() . "\n";
} catch (NetworkException $e) {
    echo "✗ Network Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Payment failed: " . $e->getMessage() . "\n";
}

// 2.2 Get payment history
echo "\n2.2 Getting payment history...\n";
try {
    $historyLimit = 5;
    if ($usingRealCredentials) {
        $paymentHistory = $client->getPaymentHistory($exampleAccountToCheck, $historyLimit);
        echo "✓ Found " . count($paymentHistory) . " recent DZT payment(s):\n";
        
        foreach ($paymentHistory as $i => $payment) {
            echo "  " . ($i + 1) . ". {$payment->getAmount()} DZT\n";
            echo "     From: " . substr($payment->getFromAccount(), 0, 10) . "...\n";
            echo "     To: " . substr($payment->getToAccount(), 0, 10) . "...\n";
            echo "     Memo: " . ($payment->getMemo() ?: 'None') . "\n";
            echo "     Date: {$payment->getCreatedAt()}\n";
            echo "     Hash: " . substr($payment->getTransactionHash(), 0, 15) . "...\n";
            if ($i < count($paymentHistory) - 1) echo "     ---\n";
        }
    } else {
        echo "   [DEMO] Would show {$historyLimit} recent payments:\n";
        echo "   [DEMO] 1. 50.00 DZT - From: GA1234... To: GA5678... (2 hours ago)\n";
        echo "   [DEMO] 2. 25.75 DZT - From: GA9012... To: GA3456... (1 day ago)\n";
        echo "   [DEMO] 3. 100.00 DZT - From: GA7890... To: GA1234... (3 days ago)\n";
    }
} catch (Exception $e) {
    echo "✗ Payment history failed: " . $e->getMessage() . "\n";
}

// 2.3 Search transactions by memo
echo "\n2.3 Searching transactions by memo...\n";
$searchMemo = 'SofizPay SDK Test Payment';
try {
    if ($usingRealCredentials) {
        $memoTransactions = $client->getTransactionsByMemo($exampleAccountToCheck, $searchMemo, 10);
        echo "✓ Found " . count($memoTransactions) . " transaction(s) with memo '{$searchMemo}':\n";
        
        foreach ($memoTransactions as $i => $payment) {
            echo "  " . ($i + 1) . ". {$payment->getAmount()} DZT\n";
            echo "     From: " . substr($payment->getFromAccount(), 0, 10) . "...\n";
            echo "     To: " . substr($payment->getToAccount(), 0, 10) . "...\n";
            echo "     Date: {$payment->getCreatedAt()}\n";
            echo "     Hash: " . substr($payment->getTransactionHash(), 0, 15) . "...\n";
            if ($i < count($memoTransactions) - 1) echo "     ---\n";
        }
    } else {
        echo "   [DEMO] Would search for transactions with memo '{$searchMemo}':\n";
        echo "   [DEMO] Found 2 matching transactions\n";
        echo "   [DEMO] 1. 150.00 DZT - invoice-2024-001 (1 week ago)\n";
        echo "   [DEMO] 2. 275.50 DZT - invoice-2024-002 (2 weeks ago)\n";
    }
} catch (Exception $e) {
    echo "✗ Memo search failed: " . $e->getMessage() . "\n";
}

// =============================================================================
// 3. SERVICE-BASED ACCESS
// =============================================================================

echo "\n\n⚙️  SERVICE-BASED ACCESS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "You can also access functionality through dedicated services:\n\n";

echo "3.1 Payment Service Methods:\n";
echo "  - \$client->payments()->sendPayment(...)\n";
echo "  - \$client->payments()->getPaymentHistory(...)\n";
echo "  - \$client->payments()->getTransactionsByMemo(...)\n\n";

echo "3.2 Account Service Methods:\n";
echo "  - \$client->accounts()->getDztBalance(...)\n";
echo "  - \$client->accounts()->getAllBalances(...)\n";
echo "  - \$client->accounts()->accountExists(...)\n";
echo "  - \$client->accounts()->hasDztTrustline(...)\n\n";

// =============================================================================
// 4. ERROR HANDLING EXAMPLES
// =============================================================================

echo "🔧 ERROR HANDLING EXAMPLES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "4.1 Testing validation errors...\n";
try {
    // This will throw a ValidationException
    if (!$usingRealCredentials) {
        echo "   [DEMO] Would validate invalid secret key format\n";
        echo "   [DEMO] ValidationException: Invalid secret key format\n";
    } else {
        $client->sendPayment('INVALID_SECRET', $exampleDestinationId, '10', 'test');
    }
} catch (ValidationException $e) {
    echo "✓ Caught ValidationException: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✓ Caught Exception: " . $e->getMessage() . "\n";
}

echo "\n4.2 Testing network errors...\n";
try {
    if (!$usingRealCredentials) {
        echo "   [DEMO] Would test with non-existent account\n";
        echo "   [DEMO] NetworkException: Account not found\n";
    } else {
        $client->getDztBalance('GA_NON_EXISTENT_ACCOUNT_ID_THAT_DOES_NOT_EXIST_ON_NETWORK');
    }
} catch (NetworkException $e) {
    echo "✓ Caught NetworkException: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✓ Caught Exception: " . $e->getMessage() . "\n";
}

// =============================================================================
// 5. SDK FEATURES SUMMARY
// =============================================================================

echo "\n\n📋 SDK FEATURES SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Send DZT payments with optional memos\n";
echo "✅ Check account DZT balances and trustlines\n";
echo "✅ Retrieve all asset balances for an account\n";
echo "✅ Get payment history with pagination support\n";
echo "✅ Search transactions by memo text\n";
echo "✅ Account existence verification\n";
echo "✅ Fixed DZT asset configuration (mainnet issuer)\n";
echo "✅ Support for both mainnet and testnet\n";
echo "✅ Comprehensive error handling with custom exceptions\n";
echo "✅ Service-based architecture for modular access\n";
echo "✅ Built on reliable Soneso Stellar SDK\n\n";

echo "🔗 Network Information:\n";
echo "   Current: " . strtoupper($client->getNetwork()) . "\n";
echo "   DZT Issuer: " . $client->getDztAsset()->getIssuerAccountId() . "\n\n";

if (!$usingRealCredentials) {
    echo "💡 To test with real transactions:\n";
    echo "   1. Replace placeholder credentials with real Stellar account data\n";
    echo "   2. Ensure accounts have DZT trustlines established\n";
    echo "   3. Have sufficient XLM for transaction fees\n";
    echo "   4. Run this script again\n\n";
}

echo "🎉 Demo completed successfully!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
