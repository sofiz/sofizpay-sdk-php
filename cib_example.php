<?php
/**
 * SofizPay PHP SDK - CIB Payment Integration Example
 * 
 * This example demonstrates how to integrate CIB payments using the SofizPay SDK.
 * It shows the complete workflow from creating a payment to verifying the callback.
 * 
 * Features demonstrated:
 * - Creating CIB transactions
 * - Handling payment redirects
 * - Verifying payment callback signatures
 * - Error handling for CIB operations
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
// CONFIGURATION
// =============================================================================

// Initialize SofizPay client
$client = new SofizPayClient(
    network: 'mainnet',
    httpClient: null,
    baseUrl: 'https://www.sofizpay.com' // Real SofizPay API endpoint
);

// Configuration
$stellarAccount = 'GC6D5TAAKIAB2PGY3U64KTPGR5WXJZ7QBPKZALEIMEOTTKO4LEPQUBUM'; // Real Stellar account
$merchantReturnUrl = 'https://merchant-test.com/payment/callback?orderid=83712873'; // Test callback URL


echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    CIB Payment Integration                     ║\n";
echo "║                      SofizPay SDK Example                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// =============================================================================
// EXAMPLE 1: CREATING A CIB PAYMENT
// =============================================================================

echo "💳 Creating a CIB Payment Transaction\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $cibTransaction = $client->createCibTransaction(
        account: $stellarAccount,
        amount: '150.00', // Amount in DZD
        fullName: 'Mohammed Benali',
        phone: '+213555123456',
        email: 'mohammed@example.com',
        returnUrl: $merchantReturnUrl,
        memo: 'Order #ORD-2024-001', //max length 28 characters
        redirect: false // Set to true to redirect immediately
    );

    echo "✅ CIB Transaction Created Successfully!\n\n";
    echo "📋 Transaction Details:\n";
    echo "   Transaction ID: " . $cibTransaction->getTransactionId() . "\n";
    echo "   CIB Transaction ID: " . $cibTransaction->getCibTransactionId() . "\n";
    echo "   Amount: " . $cibTransaction->getAmount() . " DZD\n";
    echo "   Status: " . $cibTransaction->getStatus() . "\n";
    echo "   Payment URL: " . $cibTransaction->getPaymentUrl() . "\n";
    echo "   More Info URL: " . $cibTransaction->getMoreInfoUrl() . "\n\n";

    // In a real application, you would redirect the user to the payment URL
    echo "🔗 Next Step: Redirect user to payment URL\n";
    echo "   " . $cibTransaction->getPaymentUrl() . "\n\n";

    echo "💡 Implementation Example:\n";
    echo "   header('Location: ' . \$cibTransaction->getPaymentUrl());\n";
    echo "   exit;\n\n";

} catch (ValidationException $e) {
    echo "❌ Validation Error: " . $e->getMessage() . "\n";
    echo "   Please check your input parameters.\n\n";
} catch (NetworkException $e) {
    echo "❌ Network Error: " . $e->getMessage() . "\n";
    echo "   Please check your internet connection and API endpoint.\n\n";
} catch (SofizPayException $e) {
    echo "❌ SofizPay Error: " . $e->getMessage() . "\n";
    echo "   Please check your account configuration.\n\n";
}

