<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Port;

use App\SharedKernel\Domain\Label\LabelContent;

interface LabelPrinter
{
    public function print(LabelContent $content, string $printerDeviceId): void;
}
