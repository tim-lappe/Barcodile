<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

use App\Scanner\Domain\Entity\ScannerDevice;

final readonly class ScannedCode
{
    public function __construct(
        public ScannerDevice $device,
        public string $text,
    ) {
    }
}
