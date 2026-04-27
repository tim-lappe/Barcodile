<?php

declare(strict_types=1);

namespace App\Printer\Domain\ValueObject;

use App\Printer\Domain\Dto\LabelPrinterConnection;
use App\Printer\Domain\Dto\LabelPrintSettings;

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
