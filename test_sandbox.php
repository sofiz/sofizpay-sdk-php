<?php

require_once __DIR__ . '/vendor/autoload.php';

use SofizPay\SofizPaySDK;

// 1. Initialize SDK in Sandbox Mode
// SofizPaySDK($horizonUrl, $isSandbox)
$sdk = new SofizPaySDK('https://horizon.stellar.org', true);

echo "--- Starting SofizPay PHP SDK Sandbox Test ---\n";
echo "Current Mode: SANDBOX\n\n";

// 2. Test makeSandboxCIBTransaction
echo "1. Testing makeSandboxCIBTransaction (Dedicated)...\n";
$params = [
    'account' => 'GB3R3DRQXBPSC2XSFLPDRVCAVRCVJXAPJGBPMJ45JBRJC5QJPM7QTUSO',
    'amount' => 150.00,
    'full_name' => 'PHP Sandbox Tester',
    'phone' => '0555000000',
    'email' => 'php_test@sofizpay.com',
    'memo' => 'PHP Sandbox Test'
];

$result = $sdk->makeSandboxCIBTransaction($params);
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// 3. Test checkSandboxCIBStatus
if ($result['success'] && isset($result['data']['cib_transaction_id'])) {
    $cibTransactionId = $result['data']['cib_transaction_id'];
    echo "2. Testing checkSandboxCIBStatus for ID: $cibTransactionId...\n";
    $status = $sdk->checkSandboxCIBStatus($cibTransactionId);
    echo "Status Result: " . json_encode($status, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "2. Skipping checkCIBStatus (no order number received).\n";
}

echo "\n--- Sandbox Test Completed ---\n";
