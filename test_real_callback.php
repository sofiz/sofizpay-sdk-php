<?php
/**
 * Real CIB Callback Test
 * Testing with actual callback URL from SofizPay
 */

require_once __DIR__ . '/vendor/autoload.php';

use Sofiz\SofizPay\SofizPayClient;
use Sofiz\SofizPay\Exceptions\SofizPayException;
use Sofiz\SofizPay\Exceptions\ValidationException;

echo "🧪 Testing Real CIB Payment Callback\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Initialize client
$client = new SofizPayClient(
    network: 'mainnet',
    httpClient: null,
    baseUrl: 'https://www.sofizpay.com'
);

// Real callback URL provided by user
$callbackUrl = 'https://merchant-test.com/payment/callback?orderid=1212123&orderkey=hi&sofizpay_return=cib&payment_status=success&transaction_id=12344&cib_transaction_id=12344&signature=aECU3TRG2p0q4G7-IMLZdqIQU38jX8TRT1E4XXxN18loiOXd_779SXXrzluryxevtwJDCTLoy6nLOExLv0lYkflC9ISNmjRgbqLVRQcF9SJvl3HfjkYhn3CUOi5VReCqNCrVtQZFeqgD_l6XJG6UBNX77zUuOqMhFiTDlnnLwNJnC3TiFOCQzJungWcX9-iY4xXi0j_AN25Rm5To_QcIF-DO2DQnx9zZqQfdmm8pAw3_feYMt-FNKpgqnFIJKgfGPlfvCX8ILegu1QehmPYR7mKb5Cx-i3mLu7p1-sXePN-4HJfHpP0k0LxhQxkZByyllJiVF8IQIx4tNs-_Fp-huQ==&message=1212123hicibsuccess100';

// this is the public key for sofizpay signature 
$publicKeyPem = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1N+bDPxpqeB9QB0affr/
02aeRXAAnqHuLrgiUlVNdXtF7t+2w8pnEg+m9RRlc+4YEY6UyKTUjVe6k7v2p8Jj
UItk/fMNOEg/zY222EbqsKZ2mF4hzqgyJ3QHPXjZEEqABkbcYVv4ZyV2Wq0x0ykI
+Hy/5YWKeah4RP2uEML1FlXGpuacnMXpW6n36dne3fUN+OzILGefeRpmpnSGO5+i
JmpF2mRdKL3hs9WgaLSg6uQyrQuJA9xqcCpUmpNbIGYXN9QZxjdyRGnxivTE8awx
THV3WRcKrP2krz3ruRGF6yP6PVHEuPc0YDLsYjV5uhfs7JtIksNKhRRAQ16bAsj/
9wIDAQAB
-----END PUBLIC KEY-----';

echo "📋 Analyzing Callback URL:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "URL: " . $callbackUrl . "\n\n";

// Parse URL parameters
$cibService = $client->cib();
$params = $cibService->parseReturnUrl($callbackUrl);

echo "📊 Parsed Parameters:\n";
foreach ($params as $key => $value) {
    if ($key === 'signature') {
        echo "   {$key}: " . substr($value, 0, 50) . "... (truncated)\n";
    } else {
        echo "   {$key}: {$value}\n";
    }
}
echo "\n";

// Quick success check
$isSuccessful = $cibService->isPaymentSuccessful($callbackUrl);
echo "⚡ Quick Success Check: " . ($isSuccessful ? '✅ Success' : '❌ Failed') . "\n\n";

echo "🔐 Signature Verification Test:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    // Verify the signature
    $verification = $client->verifyCibSignature($callbackUrl, $publicKeyPem);

    echo "🔍 Verification Results:\n";
    echo "   Signature Valid: " . ($verification->isValid() ? '✅ Yes' : '❌ No') . "\n";
    echo "   Payment Status: " . strtoupper($verification->getPaymentStatus()) . "\n";
    echo "   Transaction ID: " . $verification->getTransactionId() . "\n";
    echo "   CIB Transaction ID: " . $verification->getCibTransactionId() . "\n";
    echo "   Extracted Amount: " . $verification->getAmount() . "\n";
    echo "   Message: " . $verification->getMessage() . "\n";
    echo "   Is Successful Payment: " . ($verification->isSuccessful() ? '✅ Yes' : '❌ No') . "\n";

    if ($verification->getError()) {
        echo "   Error: " . $verification->getError() . "\n";
    }
    echo "\n";

    // Detailed analysis
    echo "📝 Detailed Analysis:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($verification->isValid()) {
        echo "✅ SIGNATURE IS VALID\n";
        echo "   The callback is authentic and came from SofizPay.\n\n";
        
        if ($verification->isSuccessful()) {
            echo "🎉 PAYMENT SUCCESSFUL\n";
            echo "   You can safely process this payment!\n";
            echo "   Actions to take:\n";
            echo "   1. Update order status to 'paid'\n";
            echo "   2. Send confirmation to customer\n";
            echo "   3. Fulfill the order\n";
        } else {
            echo "⚠️  PAYMENT FAILED\n";
            echo "   The signature is valid but payment was not successful.\n";
            echo "   Actions to take:\n";
            echo "   1. Mark order as 'payment_failed'\n";
            echo "   2. Notify customer\n";
            echo "   3. Offer alternative payment methods\n";
        }
    } else {
        echo "🚨 SIGNATURE IS INVALID\n";
        echo "   This callback may be tampered with or forged!\n";
        echo "   SECURITY ALERT - DO NOT PROCESS THIS PAYMENT!\n";
        echo "   Actions to take:\n";
        echo "   1. Log security incident\n";
        echo "   2. Do not fulfill order\n";
        echo "   3. Investigate the source\n";
    }

} catch (ValidationException $e) {
    echo "❌ Validation Error: " . $e->getMessage() . "\n";
    echo "   The callback URL is missing required parameters.\n\n";
} catch (SofizPayException $e) {
    echo "❌ Verification Error: " . $e->getMessage() . "\n";
    echo "   There was an error during signature verification.\n\n";
}


