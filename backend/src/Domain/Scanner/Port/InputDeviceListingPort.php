<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Port;

use App\Domain\Scanner\ValueObject\ListedInputDevice;

interface InputDeviceListingPort
{
    /**
     * @return list<ListedInputDevice>
     */
    public function listAvailableInputDevices(): array;
}
