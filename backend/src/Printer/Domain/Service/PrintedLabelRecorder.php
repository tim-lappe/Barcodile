<?php

declare(strict_types=1);

namespace App\Printer\Domain\Service;

use App\Printer\Domain\Entity\PrintedLabel;
use App\Printer\Domain\Entity\PrinterDevice;
use App\Printer\Domain\Repository\PrintedLabelRepository;
use App\SharedKernel\Domain\Label\LabelSize;

final readonly class PrintedLabelRecorder
{
    public function __construct(
        private PrintedLabelRepository $labelRepo,
    ) {
    }

    public function record(
        PrinterDevice $device,
        LabelSize $labelSize,
        string $pngBytes,
        string $source,
    ): void {
        $this->labelRepo->save(new PrintedLabel(
            $device->getId(),
            $device->getDriverCode(),
            $labelSize,
            $pngBytes,
            $source,
        ));
    }
}
