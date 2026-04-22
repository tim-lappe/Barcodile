<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Input;

use App\Domain\Scanner\Entity\ScannerDeviceId;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;

final class ScannerInputReceiver
{

    public function __construct(
        private readonly ScannerDeviceRepository $scannerDeviceRepository,
    ) {
    }

    public function receiveInput(ScannerDeviceId $deviceId, string $input): void
    {
        $device = $this->scannerDeviceRepository->find($deviceId);
        if (null === $device) {
            throw new \Exception('Device not found');
        }
        $device->recordScannedCode($input);
        $this->scannerDeviceRepository->flush();
    }
}