<?php

declare(strict_types=1);

namespace App\Printer\Domain\Adapter;

use App\Inventory\Domain\Port\LabelPrinter;
use App\Printer\Domain\Dto\LabelPrintSettingOptions;
use App\Printer\Domain\Dto\LabelSizePrintSettingOption;
use App\Printer\Domain\Entity\PrinterDevice;
use App\Printer\Domain\Exception\LabelPrintJobFailedException;
use App\Printer\Domain\Repository\PrinterDeviceRepository;
use App\Printer\Domain\Service\LabelPrinterDriverRegistry;
use App\Printer\Domain\Service\LabelSizeSelector;
use App\Printer\Domain\Service\PrintedLabelRecorder;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use App\SharedKernel\Domain\Label\Label;
use App\SharedKernel\Domain\Label\LabelContent;
use App\SharedKernel\Domain\Label\LabelImageGenerator;
use App\SharedKernel\Domain\Label\LabelSize;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class InventoryLabelPrinter implements LabelPrinter
{
    private const PRINT_SOURCE_INVENTORY = 'inventory';

    public function __construct(
        private PrinterDeviceRepository $deviceRepository,
        private LabelPrinterDriverRegistry $driverRegistry,
        private LabelSizeSelector $labelSizeSelector,
        private LabelImageGenerator $labelImageGenerator,
        private LoggerInterface $logger,
        private PrintedLabelRecorder $labelRecorder,
    ) {
    }

    public function print(LabelContent $content, string $printerDeviceId): void
    {
        $device = $this->mustFind($printerDeviceId);
        $driver = $this->driverRegistry->get($device->getDriverCode());
        $label = new Label($content, $this->labelSizeSelector->select($content, $this->availableLabelSizes($driver->printSettingOptions())));
        $pngBytes = $this->labelImageGenerator->generate($label->content(), $label->size());

        $this->logPrintRequest($device, $label, $pngBytes);

        try {
            $driver->printLabelImage(
                $driver->createConnection($device->getConnection()),
                $driver->createPrintSettings($device->getPrintSettings()),
                $label->size(),
                $pngBytes,
            );
        } catch (LabelPrintJobFailedException $e) {
            $this->logger->error('Printer label failed.', [
                'printerDeviceId' => (string) $device->getId(),
                'driverCode' => $device->getDriverCode(),
                'error' => $e->getMessage(),
            ]);
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $this->labelRecorder->record($device, $label->size(), $pngBytes, self::PRINT_SOURCE_INVENTORY);
    }

    /**
     * @return list<LabelSize>
     */
    private function availableLabelSizes(LabelPrintSettingOptions $options): array
    {
        return array_map(
            static fn (LabelSizePrintSettingOption $option): LabelSize => $option->size,
            $options->labelSizes,
        );
    }

    private function logPrintRequest(PrinterDevice $device, Label $label, string $pngBytes): void
    {
        $this->logger->info('Printer label requested.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'labelWidthMillimeters' => $label->size()->widthMillimeters(),
            'labelHeightMillimeters' => $label->size()->heightMillimeters(),
            'imageBytes' => \strlen($pngBytes),
        ]);
    }

    private function mustFind(string $printerDeviceId): PrinterDevice
    {
        $device = $this->deviceRepository->find(PrinterDeviceId::fromString($printerDeviceId));
        if (!$device instanceof PrinterDevice) {
            throw new NotFoundHttpException('Printer device not found.');
        }

        return $device;
    }
}
