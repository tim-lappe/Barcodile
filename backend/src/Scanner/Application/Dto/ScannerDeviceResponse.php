<?php

declare(strict_types=1);

namespace App\Scanner\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class ScannerDeviceResponse
{
    /**
     * @param list<string> $lastScannedCodes
     */
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $deviceIdentifier,
        public string $name,
        public array $lastScannedCodes,
        #[SerializedName('automationAddInventoryOnBarcodeScan')]
        public bool $addOnBarcodeScan,
        #[SerializedName('automationCreateCatalogItemIfMissingForBarcode')]
        public bool $createIfMissingBarcode,
        #[SerializedName('automationRemoveInventoryOnPublicCodeScan')]
        public bool $remOnPublic,
        #[SerializedName('automationPrintInventoryLabelOnBarcodeScan')]
        public bool $printLabelOnBarcodeScan,
        #[SerializedName('automationPrinterDeviceId')]
        public ?string $printerDeviceId,
    ) {
    }
}
