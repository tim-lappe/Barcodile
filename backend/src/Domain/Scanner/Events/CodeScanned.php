<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Events;

use App\Domain\Shared\Id\ScannerDeviceId;

final readonly class CodeScanned
{
    public function __construct(
        public ScannerDeviceId $scannerDeviceId,
        public string $text,
    ) {
    }
}
