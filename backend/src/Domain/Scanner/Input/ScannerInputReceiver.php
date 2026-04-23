<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Input;

use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Domain\Shared\Id\ScannerDeviceId;
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
