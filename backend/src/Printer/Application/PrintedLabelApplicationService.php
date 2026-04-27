<?php

declare(strict_types=1);

namespace App\Printer\Application;

use App\Printer\Application\Dto\PrintedLabelResponse;
use App\Printer\Domain\Entity\PrintedLabel;
use App\Printer\Domain\Entity\PrinterDevice;
use App\Printer\Domain\Exception\LabelPrintJobFailedException;
use App\Printer\Domain\Repository\PrintedLabelRepository;
use App\Printer\Domain\Repository\PrinterDeviceRepository;
use App\Printer\Domain\Service\LabelPrinterDriverRegistry;
use App\Printer\Domain\Service\PrintedLabelRecorder;
use App\SharedKernel\Domain\Id\PrintedLabelId;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PrintedLabelApplicationService
{
    private const HISTORY_LIMIT = 100;
    private const PRINT_SOURCE_RESEND = 'resend';

    public function __construct(
        private PrinterDeviceRepository $deviceRepo,
        private PrintedLabelRepository $labelRepo,
        private LabelPrinterDriverRegistry $driverRegistry,
        private PrintedLabelRecorder $labelRecorder,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return list<PrintedLabelResponse>
     */
    public function listPrintedLabels(string $printerDeviceId): array
    {
        $device = $this->mustFindDevice($printerDeviceId);

        return array_map(
            fn (PrintedLabel $label): PrintedLabelResponse => $this->mapLabel($label),
            $this->labelRepo->findRecentForPrinter($device->getId(), self::HISTORY_LIMIT),
        );
    }

    public function getPrintedLabelImage(string $printerDeviceId, string $printedLabelId): string
    {
        return $this->mustFindLabel($printerDeviceId, $printedLabelId)->getPngBytes();
    }

    public function resendPrintedLabel(string $printerDeviceId, string $printedLabelId): void
    {
        $device = $this->mustFindDevice($printerDeviceId);
        $label = $this->mustFindLabelForDevice($device, $printedLabelId);
        $driver = $this->driverRegistry->get($device->getDriverCode());
        $pngBytes = $label->getPngBytes();

        try {
            $driver->printLabelImage(
                $driver->createConnection($device->getConnection()),
                $driver->createPrintSettings($device->getPrintSettings()),
                $label->getLabelSize(),
                $pngBytes,
            );
        } catch (LabelPrintJobFailedException $e) {
            $this->logResendFailure($device, $label, $e);
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $this->labelRecorder->record($device, $label->getLabelSize(), $pngBytes, self::PRINT_SOURCE_RESEND);
    }

    private function mapLabel(PrintedLabel $label): PrintedLabelResponse
    {
        $labelSize = $label->getLabelSize();

        return new PrintedLabelResponse(
            (string) $label->getId(),
            (string) $label->getPrinterDeviceId(),
            $label->getDriverCode(),
            $labelSize->widthMillimeters(),
            $labelSize->heightMillimeters(),
            $label->getSource(),
            $label->getCreatedAt()->format(\DATE_ATOM),
            $this->urlGenerator->generate('api_printer_device_printed_label_image', [
                'printerDeviceId' => (string) $label->getPrinterDeviceId(),
                'printedLabelId' => (string) $label->getId(),
            ]),
        );
    }

    private function mustFindDevice(string $printerDeviceId): PrinterDevice
    {
        $device = $this->deviceRepo->find(PrinterDeviceId::fromString($printerDeviceId));
        if (!$device instanceof PrinterDevice) {
            throw new NotFoundHttpException('Printer device not found.');
        }

        return $device;
    }

    private function mustFindLabel(string $printerDeviceId, string $printedLabelId): PrintedLabel
    {
        return $this->mustFindLabelForDevice($this->mustFindDevice($printerDeviceId), $printedLabelId);
    }

    private function mustFindLabelForDevice(PrinterDevice $device, string $printedLabelId): PrintedLabel
    {
        $label = $this->labelRepo->findForPrinter(
            PrintedLabelId::fromString($printedLabelId),
            $device->getId(),
        );
        if (!$label instanceof PrintedLabel) {
            throw new NotFoundHttpException('Printed label not found.');
        }

        return $label;
    }

    private function logResendFailure(
        PrinterDevice $device,
        PrintedLabel $label,
        LabelPrintJobFailedException $failure,
    ): void {
        $this->logger->error('Printer label resend failed.', [
            'printerDeviceId' => (string) $device->getId(),
            'printedLabelId' => (string) $label->getId(),
            'driverCode' => $device->getDriverCode(),
            'error' => $failure->getMessage(),
        ]);
    }
}
