<?php

declare(strict_types=1);

namespace App\Scanner\Application;

final readonly class ScannerDeviceView
{
    /**
     * @param list<string> $lastScannedCodes
     */
    public function __construct(
        public string $resourceId,
        public string $deviceIdentifier,
        public string $name,
        public array $lastScannedCodes,
        public bool $addOnBarcodeScan,
        public bool $createIfMissingBarcode,
        public bool $remOnPublic,
        public bool $printLabelOnBarcodeScan,
        public ?string $printerDeviceId,
    ) {
    }
}
