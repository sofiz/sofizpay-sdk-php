<?php

namespace SofizPay;

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Xdr\XdrTransactionBuilder;
use Soneso\StellarSDK\Requests\Order;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictReceiveOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictSendOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ChangeTrustOperationResponse;

class SofizPaySDK {
    private StellarSDK $sdk;
    private bool $isSandbox;
    public const DZT_ASSET_CODE = 'DZT';
    public const DZT_ASSET_ISSUER = 'GCAZI7YBLIDJWIVEL7ETNAZGPP3LC24NO6KAOBWZHUERXQ7M5BC52DLV';

    public function __construct(string $horizonUrl = 'https://horizon.stellar.org', bool $isSandbox = false) {
        $this->sdk = new StellarSDK($horizonUrl);
        $this->isSandbox = $isSandbox;
    }

    /**
     * Submit a payment (Alias for sendPayment for JS parity)
     */
    public function submit(array $params): array {
        return $this->sendPayment(
            $params['secretkey'] ?? '',
            $params['destinationPublicKey'] ?? '',
            $params['amount'] ?? '0',
            $params['memo'] ?? null
        );
    }

    /**
     * Send DZT payment
     */
    public function sendPayment(string $secretKey, string $destinationId, string $amount, ?string $memoText = null): array {
        $startTime = microtime(true);
        try {
            try {
                $sourceKeyPair = KeyPair::fromSeed($secretKey);
            } catch (\Exception $e) {
                throw new \Exception("Invalid secret key format (Seed must be 56 characters starting with S)");
            }
            
            $sourceAccountId = $sourceKeyPair->getAccountId();
            $sourceAccount = $this->sdk->requestAccount($sourceAccountId);
            $asset = Asset::createNonNativeAsset(self::DZT_ASSET_CODE, self::DZT_ASSET_ISSUER);

            $paymentOperation = (new \Soneso\StellarSDK\PaymentOperationBuilder($destinationId, $asset, $amount))->build();
            
            $transactionBuilder = new TransactionBuilder($sourceAccount);
            $transactionBuilder->addOperation($paymentOperation);

            if ($memoText) {
                if (strlen($memoText) > 28) {
                    $memoText = substr($memoText, 0, 28);
                }
                $transactionBuilder->addMemo(Memo::text($memoText));
            }

            $transaction = $transactionBuilder->build();
            $transaction->sign($sourceKeyPair, Network::public());

            $response = $this->sdk->submitTransaction($transaction);

            return [
                'success' => true,
                'transactionId' => $response->getHash(),
                'transactionHash' => $response->getHash(),
                'amount' => $amount,
                'memo' => $memoText,
                'destinationPublicKey' => $destinationId,
                'duration' => microtime(true) - $startTime,
                'timestamp' => date('c')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => microtime(true) - $startTime,
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Get DZT balance
     */
    public function getBalance(string $publicKey): array {
        try {
            $account = $this->sdk->requestAccount($publicKey);
            $balanceValue = '0.0000000';

            foreach ($account->getBalances() as $balance) {
                if ($balance->getAssetCode() === self::DZT_ASSET_CODE && 
                    $balance->getAssetIssuer() === self::DZT_ASSET_ISSUER) {
                    $balanceValue = $balance->getBalance();
                    break;
                }
            }

            return [
                'success' => true,
                'balance' => $balanceValue,
                'publicKey' => $publicKey,
                'asset_code' => self::DZT_ASSET_CODE,
                'asset_issuer' => self::DZT_ASSET_ISSUER,
                'timestamp' => date('c')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balance' => '0.0000000',
                'publicKey' => $publicKey,
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Get transactions history
     */
    public function getTransactions(string $publicKey, ?int $limit = 50, ?string $cursor = null): array {
        try {
            $builder = $this->sdk->operations()->forAccount($publicKey)
                ->includeTransactions(true)
                ->order('desc')
                ->limit($limit ?? 50);
            
            if ($cursor) {
                $builder->cursor($cursor);
            }

            $response = $builder->execute();
            $transactions = [];

            foreach ($response->getOperations() as $op) {
                $txData = [
                    'id' => $op->getTransactionHash(),
                    'transactionId' => $op->getTransactionHash(),
                    'hash' => $op->getTransactionHash(),
                    'created_at' => $op->getCreatedAt(),
                    'timestamp' => $op->getCreatedAt(),
                    'paging_token' => $op->getPagingToken(),
                    'successful' => $op->isTransactionSuccessful(),
                ];

                $memo = '';
                if ($op->getTransaction() !== null) {
                    $memo = $op->getTransaction()->getMemo()->getValue() ?? '';
                }
                $txData['memo'] = $memo;

                // 1. Regular Payment
                if ($op instanceof PaymentOperationResponse) {
                    $asset = $op->getAsset();
                    if ($asset instanceof \Soneso\StellarSDK\AssetTypeCreditAlphanum && 
                        $asset->getCode() === self::DZT_ASSET_CODE) {
                        
                        $txData['type'] = $op->getSourceAccount() === $publicKey ? 'sent' : 'received';
                        $txData['amount'] = $op->getAmount();
                        $txData['from'] = $op->getSourceAccount();
                        $txData['to'] = $op->getTo();
                        $txData['asset_code'] = $asset->getCode();
                        $txData['asset_issuer'] = $asset->getIssuer();
                        $transactions[] = $txData;
                    }
                }
                // 2. Path Payments (Strict Receive/Send)
                elseif ($op instanceof PathPaymentStrictReceiveOperationResponse || $op instanceof PathPaymentStrictSendOperationResponse) {
                    $asset = $op->getAsset();
                    if ($asset instanceof \Soneso\StellarSDK\AssetTypeCreditAlphanum && 
                        $asset->getCode() === self::DZT_ASSET_CODE &&
                        $asset->getIssuer() === self::DZT_ASSET_ISSUER) {
                        
                        $txData['type'] = $op->getSourceAccount() === $publicKey ? 'sent' : 'received';
                        $txData['amount'] = $op->getAmount();
                        $txData['from'] = $op->getSourceAccount();
                        $txData['to'] = $op->getTo();
                        $txData['asset_code'] = $asset->getCode();
                        $txData['asset_issuer'] = $asset->getIssuer();
                        $transactions[] = $txData;
                    }
                }
                // 3. Trustline (DZT Setup)
                elseif ($op instanceof ChangeTrustOperationResponse) {
                    if ($op->getAssetCode() === self::DZT_ASSET_CODE && 
                        $op->getAssetIssuer() === self::DZT_ASSET_ISSUER) {
                        
                        $txData['type'] = 'trustline';
                        $txData['amount'] = '0';
                        $txData['asset_code'] = $op->getAssetCode();
                        $transactions[] = $txData;
                    }
                }
                // 4. Account Creation
                elseif ($op instanceof CreateAccountOperationResponse) {
                    if ($op->getAccount() === $publicKey) {
                        $txData['type'] = 'account_created';
                        $txData['amount'] = $op->getStartingBalance();
                        $txData['from'] = $op->getFunder();
                        $txData['asset_code'] = 'XLM';
                        $transactions[] = $txData;
                    }
                }
            }

            return [
                'success' => true,
                'transactions' => $transactions,
                'total' => count($transactions),
                'publicKey' => $publicKey,
                'timestamp' => date('c')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transactions' => [],
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Search transactions by memo
     */
    public function searchTransactionsByMemo(string $publicKey, string $memo, int $limit = 50): array {
        try {
            $transactions = $this->getTransactions($publicKey, 100);
            
            if (!$transactions['success'] || empty($transactions['transactions'])) {
                return [
                    'success' => true,
                    'transactions' => [],
                    'total' => 0,
                    'totalFound' => 0,
                    'searchMemo' => $memo,
                    'publicKey' => $publicKey,
                    'timestamp' => date('c')
                ];
            }
            
            $filtered = array_filter($transactions['transactions'], function($tx) use ($memo) {
                return isset($tx['memo']) && stripos($tx['memo'], (string)$memo) !== false;
            });
            
            $limited = array_slice($filtered, 0, $limit);

            return [
                'success' => true,
                'transactions' => array_values($limited),
                'total' => count($limited),
                'totalFound' => count($filtered),
                'searchMemo' => $memo,
                'publicKey' => $publicKey,
                'timestamp' => date('c')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Get transaction by hash
     */
    public function getTransactionByHash(string $hash): array {
        try {
            $tx = $this->sdk->requestTransaction($hash);
            return [
                'success' => true,
                'found' => true,
                'hash' => $hash,
                'ledger' => $tx->getLedger(),
                'created_at' => $tx->getCreatedAt(),
                'successful' => $tx->isSuccessful(),
                'memo' => $tx->getMemo() ? $tx->getMemo()->getValue() : null,
                'timestamp' => date('c')
            ];
        } catch (\Exception $e) {
            // Check if it's a 404 error
            $is404 = false;
            if (method_exists($e, 'getStatusCode') && $e->getStatusCode() === 404) {
                $is404 = true;
            } elseif (stripos($e->getMessage(), '404') !== false || stripos($e->getMessage(), 'not found') !== false) {
                $is404 = true;
            }

            return [
                'success' => true,
                'found' => false,
                'error' => $is404 ? 'Transaction not found' : $e->getMessage(),
                'hash' => $hash,
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Web API Services
     */
    public function makeCIBTransaction(array $params): array {
        return $this->_makeCIBRequest($params, false);
    }

    /**
     * Initiate a CIB transaction specifically in Sandbox mode.
     */
    public function makeSandboxCIBTransaction(array $params): array {
        return $this->_makeCIBRequest($params, true);
    }

    private function _makeCIBRequest(array $params, bool $useSandbox): array {
        $baseUrl = $useSandbox
            ? 'https://sofizpay.com/sandbox/make-cib-transaction/'
            : 'https://www.sofizpay.com/make-cib-transaction/';
        $queryParams = [
            'account' => $params['account'] ?? '',
            'amount' => $params['amount'] ?? 0,
            'full_name' => $params['full_name'] ?? '',
            'phone' => $params['phone'] ?? '',
            'email' => $params['email'] ?? '',
            'redirect' => 'no'
        ];
        
        if (!empty($params['return_url'])) $queryParams['return_url'] = $params['return_url'];
        if (!empty($params['memo'])) $queryParams['memo'] = $params['memo'];

        return $this->_get($baseUrl, $queryParams);
    }

    public function getProducts(?string $encSk = null): array {
        return $this->_get('https://sofizpay.com/services/get_products/', [], ['encrypted_sk' => $encSk]);
    }

    public function checkCIBStatus(string $cibTransactionId): array {
        return $this->_checkCIBStatusRequest($cibTransactionId, false);
    }

    /**
     * Check status of a CIB transaction specifically in Sandbox mode.
     */
    public function checkSandboxCIBStatus(string $cibTransactionId): array {
        return $this->_checkCIBStatusRequest($cibTransactionId, true);
    }

    private function _checkCIBStatusRequest(string $cibTransactionId, bool $useSandbox): array {
        $baseUrl = $useSandbox
            ? 'https://sofizpay.com/sandbox/cib-transaction-check/'
            : 'https://www.sofizpay.com/cib-transaction-check/';
        return $this->_get($baseUrl, ['order_number' => $cibTransactionId]);
    }

    public function getOperationHistory(string $encSk, int $limit = 10, int $offset = 0): array {
        return $this->_get('https://sofizpay.com/services/operation-history/', [
            'encrypted_sk' => $encSk, 
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    public function getOperationDetails(string $operationId, string $encSk): array {
        return $this->_get("https://sofizpay.com/services/operation-detail/{$operationId}/", ['encrypted_sk' => $encSk]);
    }

    /**
     * Service operations (Recharge, Bills, etc)
     */
    public function rechargePhone(array $data): array {
        return $this->_performServiceOperation($data);
    }

    public function rechargeInternet(array $data): array {
        return $this->_performServiceOperation($data);
    }

    public function rechargeGame(array $data): array {
        return $this->_performServiceOperation($data);
    }

    public function payBill(array $data): array {
        return $this->_performServiceOperation($data);
    }

    private function _performServiceOperation(array $data): array {
        return $this->_post('https://www.sofizpay.com/services/operation_post', $data);
    }

    private function _get(string $url, array $params = [], array $json = []): array {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'SofizPay-PHP-SDK/1.0.2'
                ]
            ];
            if (!empty($params)) {
                $options['query'] = $params;
            }
            if (!empty($json)) {
                $options['json'] = $json;
                $options['headers']['Content-Type'] = 'application/json';
            }
            
            $response = $client->request('GET', $url, $options);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            
            return [
                'success' => true,
                'data' => is_array($data) ? $data : $content,
                'timestamp' => date('c')
            ];
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
                $errData = json_decode($body, true);
                if (isset($errData['error'])) $errorMsg .= " - " . $errData['error'];
            }
            return ['success' => false, 'error' => $errorMsg, 'timestamp' => date('c')];
        }
    }

    private function _post(string $url, array $data): array {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $response = $client->request('POST', $url, [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'SofizPay-PHP-SDK/1.0.2'
                ]
            ]);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            
            return [
                'success' => true,
                'data' => is_array($data) ? $data : $content,
                'timestamp' => date('c')
            ];
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
                $errData = json_decode($body, true);
                if (isset($errData['error'])) $errorMsg .= " - " . $errData['error'];
            }
            return ['success' => false, 'error' => $errorMsg, 'timestamp' => date('c')];
        }
    }

    public function verifySignature(array $data): bool {
        if (empty($data['message']) || empty($data['signature_url_safe'])) return false;

        $publicKey = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1N+bDPxpqeB9QB0affr/\n02aeRXAAnqHuLrgiUlVNdXtF7t+2w8pnEg+m9RRlc+4YEY6UyKTUjVe6k7v2p8Jj\nUItk/fMNOEg/zY222EbqsKZ2mF4hzqgyJ3QHPXjZEEqABkbcYVv4ZyV2Wq0x0ykI\n+Hy/5YWKeah4RP2uEML1FlXGpuacnMXpW6n36dne3fUN+OzILGefeRpmpnSGO5+i\nJmpF2mRdKL3hs9WgaLSg6uQyrQuJA9xqcCpUmpNbIGYXN9QZxjdyRGnxivTE8awx\nTHV3WRcKrP2krz3ruRGF6yP6PVHEuPc0YDLsYjV5uhfs7JtIksNKhRRAQ16bAsj/\n9wIDAQAB\n-----END PUBLIC KEY-----";

        $signature = str_replace(['-', '_'], ['+', '/'], $data['signature_url_safe']);
        $signature .= str_repeat('=', (4 - strlen($signature) % 4) % 4);
        $signature = base64_decode($signature);

        $result = @openssl_verify($data['message'], $signature, $publicKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }
}
