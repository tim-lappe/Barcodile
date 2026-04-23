<?php

declare(strict_types=1);

namespace App\Application\Scanner\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PatchScannerDeviceAutomationsRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[SerializedName('automationAddInventoryOnEanScan')]
        public bool $addOnEan,
        #[Assert\NotNull]
        #[SerializedName('automationCreateCatalogItemIfMissingForEan')]
        public bool $createIfMissingEan,
        #[Assert\NotNull]
        #[SerializedName('automationRemoveInventoryOnPublicCodeScan')]
        public bool $remOnPublic,
    ) {
    }
}
