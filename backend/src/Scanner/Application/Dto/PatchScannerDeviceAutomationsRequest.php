<?php

declare(strict_types=1);

namespace App\Scanner\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PatchScannerDeviceAutomationsRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[SerializedName('automationAddInventoryOnBarcodeScan')]
        public bool $addOnBarcodeScan,
        #[Assert\NotNull]
        #[SerializedName('automationCreateCatalogItemIfMissingForBarcode')]
        public bool $createIfMissingBarcode,
        #[Assert\NotNull]
        #[SerializedName('automationRemoveInventoryOnPublicCodeScan')]
        public bool $remOnPublic,
        #[Assert\NotNull]
        #[SerializedName('automationPrintInventoryLabelOnBarcodeScan')]
        public bool $printLabelOnBarcodeScan,
        #[SerializedName('automationPrinterDeviceId')]
        public ?string $printerDeviceId = null,
    ) {
    }
}
