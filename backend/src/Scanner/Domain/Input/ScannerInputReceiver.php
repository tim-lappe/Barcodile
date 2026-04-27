<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Input;

use App\Scanner\Domain\Repository\ScannerDeviceRepository;
use App\SharedKernel\Domain\Id\ScannerDeviceId;
use Exception;

final class ScannerInputReceiver
{
    public function __construct(
        private readonly ScannerDeviceRepository $deviceRepository,
    ) {
    }

    public function receiveInput(ScannerDeviceId $deviceId, string $input): void
    {
        $device = $this->deviceRepository->find($deviceId);
        if (null === $device) {
            throw new Exception('Device not found');
        }
        $device->recordScannedCode($input);
        $this->deviceRepository->flush();
    }
}
