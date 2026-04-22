<?php

declare(strict_types=1);

namespace App\Application\Scanner\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PatchScannerDeviceAutomationsRequest
{
    public function __construct(
        #[Assert\NotNull]
        public bool $automationAddInventoryOnEanScan,
        #[Assert\NotNull]
        public bool $automationCreateCatalogItemIfMissingForEan,
        #[Assert\NotNull]
        public bool $automationRemoveInventoryOnPublicCodeScan,
    ) {
    }
}
