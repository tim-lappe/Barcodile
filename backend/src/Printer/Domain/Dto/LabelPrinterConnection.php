<?php

declare(strict_types=1);

namespace App\Printer\Domain\Dto;

interface LabelPrinterConnection
{
    /**
     * @return array<string, mixed>
     */
    public function connectionData(): array;
}
