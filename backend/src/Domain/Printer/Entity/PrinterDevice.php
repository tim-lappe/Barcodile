<?php

declare(strict_types=1);

namespace App\Domain\Printer\Entity;

use App\Domain\Printer\Repository\PrinterDeviceRepository;
use App\Domain\Shared\Id\PrinterDeviceId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrinterDeviceRepository::class)]
#[ORM\Table(name: 'printer_device')]
class PrinterDevice
{
    #[ORM\Id]
    #[ORM\Column(name: 'printer_device_id', type: 'printer_device_id', unique: true)]
    private PrinterDeviceId $printerDeviceId;

    #[ORM\Column(name: 'driver_code', length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $driverCode = '';

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $connection = [];

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
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
