<?php

declare(strict_types=1);

namespace Sofiz\SofizPay\Models;

use Soneso\StellarSDK\Asset;

/**
 * DZT Asset representation
 */
class DztAsset
{
    private Asset $asset;
    private string $issuerAccountId;
    private string $assetCode;

    public function __construct(string $issuerAccountId, string $assetCode = 'DZT')
    {
        $this->issuerAccountId = $issuerAccountId;
        $this->assetCode = $assetCode;
        
        // Create the Stellar Asset
        $this->asset = Asset::createNonNativeAsset($assetCode, $issuerAccountId);
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getIssuerAccountId(): string
    {
        return $this->issuerAccountId;
    }

    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    public function __toString(): string
    {
        return $this->assetCode . ':' . $this->issuerAccountId;
    }
}
