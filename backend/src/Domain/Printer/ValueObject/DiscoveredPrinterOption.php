<?php

declare(strict_types=1);

namespace App\Domain\Printer\ValueObject;

use App\Domain\Printer\Dto\LabelPrinterConnection;
use App\Domain\Printer\Dto\LabelPrintSettings;

final readonly class DiscoveredPrinterOption
{
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
        public ?LabelPrinterConnection $suggestedConnection = null,
        public ?LabelPrintSettings $suggestedSettings = null,
    ) {
    }
}
