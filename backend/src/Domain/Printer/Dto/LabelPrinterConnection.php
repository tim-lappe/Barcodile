<?php

declare(strict_types=1);

namespace App\Domain\Printer\Dto;

interface LabelPrinterConnection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
