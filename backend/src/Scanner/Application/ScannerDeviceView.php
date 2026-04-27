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
        public bool $addOnEan,
        public bool $createIfMissingEan,
        public bool $remOnPublic,
    ) {
    }
}
