<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner;

use App\Application\Scanner\ScannerInventoryAutomationApplicationService;
use App\Domain\Scanner\Events\CodeScanned;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class InventoryAutomationOnCodeScanned
{
    public function __construct(
        private ScannerInventoryAutomationApplicationService $scannerInventoryAutomationApplicationService,
    ) {
    }

    #[AsEventListener]
    public function onCodeScanned(CodeScanned $event): void
    {
        $this->scannerInventoryAutomationApplicationService->handleCodeScanned($event);
    }
}
