<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("Error: Please run 'composer install' first to generate vendor/autoload.php\n");
}

use SofizPay\SofizPaySDK;

function test_sdk() {
    $sdk = new SofizPaySDK();
    $publicKey = "GB3R3DRQXBPSC2XSFLPDRVCAVRCVJXAPJGBPMJ45JBRJC5QJPM7QTUSO";
    $enc_sk = "SCILSE4IMSKSZ7PPDP26CXOYFXWLUER47X5ROMYE6XLWSCZX2UPFKBCO";
    $tx_hash = "fbdb10065755547526725832a8298711e54c86121b6d05f37508681ee720f269";

    echo "--- Starting SofizPay PHP SDK Test ---\n";

    // 1. Get Balance
    echo "1. Get Balance: ";
    $balance = $sdk->getBalance($publicKey);
    echo "Success=" . ($balance['success'] ? 'Yes' : 'No') . " | Balance=" . ($balance['balance'] ?? '0') . "\n";

    // 2. Get Transactions
    echo "2. Get Transactions: ";
    $txs = $sdk->getTransactions($publicKey);
    echo "Found " . count($txs['transactions'] ?? []) . " transactions\n";

    // 3. Search by Memo
    echo "3. Search by Memo ('00'): ";
    $search = $sdk->searchTransactionsByMemo($publicKey, "00");
    echo "Found " . ($search['totalFound'] ?? 0) . " matching transactions\n";

    // 4. Get Transaction by Hash
    echo "4. Get Transaction by Hash: ";
    $hash_detail = $sdk->getTransactionByHash($tx_hash);
    echo "Success=" . ($hash_detail['success'] ? 'Yes' : 'No') . "\n";

    // 5. CIB: Create Transaction
    echo "5. CIB Create: ";
    $cib = $sdk->makeCIBTransaction([
        "account" => $publicKey,
        "amount" => 100.0,
        "full_name" => "PHP SDK Tester",
        "phone" => "0661000000",
        "email" => "test@sofizpay.com",
        "memo" => "Test CIB PHP"
    ]);
    echo "Success=" . (($cib['success'] ?? false) ? 'Yes' : 'No') . "\n";

    // 6. CIB: Status
    echo "6. CIB Status: ";
    $status = $sdk->checkCIBStatus("ORDER_123");
    echo "Success=" . (($status['success'] ?? false) ? 'Yes' : 'No') . "\n";

    // 7. Services: Products
    echo "7. Get Products: ";
    $products = $sdk->getProducts($enc_sk);
    echo "Success=" . (($products['success'] ?? false) ? 'Yes' : 'No') . " | Count=" . (count($products['data']['products'] ?? [])) . "\n";

    // 8. Services: History
    echo "8. Operation History: ";
    $history = $sdk->getOperationHistory($enc_sk, 1);
    echo "Success=" . (($history['success'] ?? false) ? 'Yes' : 'No') . "\n";

    // 9. Services: Details
    echo "9. Operation Details: ";
    $details = $sdk->getOperationDetails("OP_123", $enc_sk);
    echo "Success=" . (($details['success'] ?? false) ? 'Yes' : 'No') . "\n";

    // 10. Missions: Recharge & Bills
    echo "10. Recharge Phone: ";
    $res = $sdk->rechargePhone([
        "encrypted_sk" => $enc_sk,
        "phone" => "0661000000",
        "operator" => "mobilis",
        "amount" => 100
    ]);
    echo "Success=" . (($res['success'] ?? false) ? 'Yes' : 'No') . "\n";

    echo "11. Recharge Internet: ";
    $res = $sdk->rechargeInternet([
        "encrypted_sk" => $enc_sk,
        "operator" => "idoom",
        "id" => "0661000000",
        "amount" => 2000
    ]);
    echo "Success=" . (($res['success'] ?? false) ? 'Yes' : 'No') . "\n";

    echo "12. Recharge Game: ";
    $res = $sdk->rechargeGame([
        "encrypted_sk" => $enc_sk,
        "game" => "freefire",
        "id" => "123456",
        "amount" => 100
    ]);
    echo "Success=" . (($res['success'] ?? false) ? 'Yes' : 'No') . "\n";

    echo "13. Pay Bill: ";
    $res = $sdk->payBill([
        "encrypted_sk" => $enc_sk,
        "bill_type" => "sonelgaz",
        "id" => "999",
        "amount" => 5000
    ]);
    echo "Success=" . (($res['success'] ?? false) ? 'Yes' : 'No') . "\n";

    // 14. Signature Verification
    echo "14. Signature Verification: ";
    $isValid = $sdk->verifySignature([
        "message" => "test_message",
        "signature_url_safe" => "jHrONYl2NuBhjAYTgRq3xwRuW2ZYZIQlx1VWgiObu5FrSnY78pQ"
    ]);
    echo ($isValid ? "VALID" : "INVALID") . "\n";

    // 15. Payment Submission (Fail case with dummy key)
    echo "15. Submit Payment (Dummy Key): ";
    $submit = $sdk->sendPayment("SCILSE4IMSKSZ7PPDP26CXOYFXWLUER47X5ROMYE6XLWSCZX2UPFKBCO", "GB3R3DRQXBPSC2XSFLPDRVCAVRCVJXAPJGBPMJ45JBRJC5QJPM7QTUSO", "10.0", "Test PHP");
    echo "Success=" . (($submit['success'] ?? false) ? 'Yes' : 'No') . " | Error=" . ($submit['error'] ?? 'NONE') . "\n";

    echo "--- SDK Test Completed ---\n";
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Run the test
test_sdk();
