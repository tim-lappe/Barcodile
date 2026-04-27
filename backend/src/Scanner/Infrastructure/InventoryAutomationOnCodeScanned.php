<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

use App\Scanner\Domain\Events\CodeScanned;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class InventoryAutomationOnCodeScanned
{
    public function __construct(
        private ScannerInvAutomationService $invAutomation,
    ) {
    }

    #[AsEventListener]
    public function onCodeScanned(CodeScanned $event): void
    {
        $this->invAutomation->handleCodeScanned($event);
    }
}
