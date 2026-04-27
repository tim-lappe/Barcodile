<?php

declare(strict_types=1);

namespace App\Printer\Domain\Entity;

use App\Printer\Domain\Repository\PrintedLabelRepository;
use App\SharedKernel\Domain\Id\PrintedLabelId;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use App\SharedKernel\Domain\Label\LabelSize;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnexpectedValueException;

#[ORM\Entity(repositoryClass: PrintedLabelRepository::class)]
#[ORM\Table(name: 'printed_label')]
#[ORM\Index(name: 'idx_printed_label_printer_created_at', columns: ['printer_device_id', 'created_at'])]
class PrintedLabel
{
    #[ORM\Id]
    #[ORM\Column(name: 'printed_label_id', type: 'printed_label_id', unique: true)]
    private PrintedLabelId $printedLabelId;

    #[ORM\Column(name: 'printer_device_id', type: 'printer_device_id')]
    private PrinterDeviceId $printerDeviceId;

    #[ORM\Column(name: 'driver_code', length: 64)]
    private string $driverCode;

    #[ORM\Column(name: 'label_width_millimeters')]
    private int $widthMm;

    #[ORM\Column(name: 'label_height_millimeters')]
    private int $heightMm;

    /**
     * @var resource|string
     */
    #[ORM\Column(name: 'png_bytes', type: Types::BLOB)]
    private mixed $pngBytes;

    #[ORM\Column(length: 32)]
    private string $source;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        PrinterDeviceId $printerDeviceId,
        string $driverCode,
        LabelSize $labelSize,
        string $pngBytes,
        string $source,
        ?DateTimeImmutable $createdAt = null,
    ) {
        $this->printedLabelId = new PrintedLabelId();
        $this->printerDeviceId = $printerDeviceId;
        $this->driverCode = $driverCode;
        $this->widthMm = $labelSize->widthMillimeters();
        $this->heightMm = $labelSize->heightMillimeters();
        $this->pngBytes = $pngBytes;
        $this->source = $source;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): PrintedLabelId
    {
        return $this->printedLabelId;
    }

    public function getPrinterDeviceId(): PrinterDeviceId
    {
        return $this->printerDeviceId;
    }

    public function getDriverCode(): string
    {
        return $this->driverCode;
    }

    public function getLabelSize(): LabelSize
    {
        return new LabelSize($this->widthMm, $this->heightMm);
    }

    public function getPngBytes(): string
    {
        if (\is_string($this->pngBytes)) {
            return $this->pngBytes;
        }

        if (\is_resource($this->pngBytes)) {
            return stream_get_contents($this->pngBytes) ?: '';
        }

        throw new UnexpectedValueException('Printed label PNG bytes are not readable.');
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
