<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Port;

use App\Scanner\Domain\ValueObject\ListedInputDevice;

interface InputDeviceListingPort
{
    /**
     * @return list<ListedInputDevice>
     */
    public function listAvailableInputDevices(): array;
}
