<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Events;

use App\SharedKernel\Domain\Id\ScannerDeviceId;

final readonly class CodeScanned
{
    public function __construct(
        public ScannerDeviceId $scannerDeviceId,
        public string $text,
    ) {
    }
}
