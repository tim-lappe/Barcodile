<?php

declare(strict_types=1);

namespace App\Printer\Domain\Entity;

use App\Printer\Domain\Repository\PrinterDeviceRepository;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrinterDeviceRepository::class)]
#[ORM\Table(name: 'printer_device')]
class PrinterDevice
{
    #[ORM\Id]
    #[ORM\Column(name: 'printer_device_id', type: 'printer_device_id', unique: true)]
    private PrinterDeviceId $printerDeviceId;

    #[ORM\Column(name: 'driver_code', length: 64)]
    private string $driverCode = '';

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $connection = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'print_settings', type: 'json')]
    private array $printSettings = [];

    #[ORM\Column(length: 255)]
    private string $name = '';

    public function __construct()
    {
        $this->printerDeviceId = new PrinterDeviceId();
    }

    public function getId(): PrinterDeviceId
    {
        return $this->printerDeviceId;
    }

    public function getDriverCode(): string
    {
        return $this->driverCode;
    }

    public function changeDriverCode(string $driverCode): static
    {
        $this->driverCode = $driverCode;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConnection(): array
    {
        return $this->connection;
    }

    /**
     * @param array<string, mixed> $connection
     */
    public function changeConnection(array $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPrintSettings(): array
    {
        return $this->printSettings;
    }

    /**
     * @param array<string, mixed> $printSettings
     */
    public function changePrintSettings(array $printSettings): static
    {
        $this->printSettings = $printSettings;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function changeName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
