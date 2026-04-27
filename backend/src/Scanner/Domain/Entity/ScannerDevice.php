<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Entity;

use App\Scanner\Domain\Events\CodeScanned;
use App\Scanner\Domain\Repository\ScannerDeviceRepository;
use App\SharedKernel\Domain\DomainEventRecorder;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use App\SharedKernel\Domain\Id\ScannerDeviceId;
use App\SharedKernel\Domain\RecordsDomainEvents;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScannerDeviceRepository::class)]
#[ORM\Table(name: 'scanner_device')]
class ScannerDevice implements RecordsDomainEvents
{
    use DomainEventRecorder;

    private const int MAX_LAST_SCANNED_CODES = 100;

    #[ORM\Id]
    #[ORM\Column(name: 'scanner_device_id', type: 'scanner_device_id', unique: true)]
    private ScannerDeviceId $scannerDeviceId;

    #[ORM\Column(name: 'device_identifier', length: 512)]
    private string $deviceIdentifier = '';

    #[ORM\Column(length: 255)]
    private string $name = '';

    /**
     * @var list<string>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $lastScannedCodes = null;

    #[ORM\Column(name: 'automation_add_inventory_on_ean_scan', options: ['default' => false])]
    private bool $addInvOnEan = false;

    #[ORM\Column(name: 'automation_create_catalog_item_if_missing_for_ean', options: ['default' => false])]
    private bool $createItemIfEan = false;

    #[ORM\Column(name: 'automation_remove_inventory_on_public_code_scan', options: ['default' => false])]
    private bool $remInvOnPublic = false;

    #[ORM\Column(name: 'automation_print_label_after_ean_add_inventory', options: ['default' => false])]
    private bool $printLabelAfterEanAdd = false;

    #[ORM\Column(name: 'automation_label_printer_device_id', type: 'printer_device_id', nullable: true)]
    private ?PrinterDeviceId $automationLabelPrinterDeviceId = null;

    public function __construct()
    {
        $this->scannerDeviceId = new ScannerDeviceId();
    }

    public function getId(): ScannerDeviceId
    {
        return $this->scannerDeviceId;
    }

    public function getDeviceIdentifier(): string
    {
        return $this->deviceIdentifier;
    }

    public function changeDeviceIdentifier(string $deviceIdentifier): static
    {
        $this->deviceIdentifier = $deviceIdentifier;

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

    /**
     * @return list<string>
     */
    public function getLastScannedCodes(): array
    {
        return $this->lastScannedCodes ?? [];
    }

    public function recordScannedCode(string $text): void
    {
        $codes = $this->lastScannedCodes ?? [];
        $codes[] = $text;
        if (\count($codes) > self::MAX_LAST_SCANNED_CODES) {
            $codes = \array_slice($codes, -self::MAX_LAST_SCANNED_CODES);
        }
        $this->lastScannedCodes = $codes;
        $this->recordDomainEvent(new CodeScanned($this->scannerDeviceId, $text));
    }

    public function isAutomationAddInventoryOnEanScan(): bool
    {
        return $this->addInvOnEan;
    }

    public function changeAutomationAddInventoryOnEanScan(bool $enabled): static
    {
        $this->addInvOnEan = $enabled;
        if (!$enabled) {
            $this->createItemIfEan = false;
            $this->printLabelAfterEanAdd = false;
            $this->automationLabelPrinterDeviceId = null;
        }

        return $this;
    }

    public function isAutomationCreateCatalogItemIfMissingForEan(): bool
    {
        return $this->createItemIfEan;
    }

    public function changeAutomationCreateCatalogItemIfMissingForEan(bool $enabled): static
    {
        $this->createItemIfEan = $enabled;

        return $this;
    }

    public function isAutomationRemoveInventoryOnPublicCodeScan(): bool
    {
        return $this->remInvOnPublic;
    }

    public function changeAutomationRemoveInventoryOnPublicCodeScan(bool $enabled): static
    {
        $this->remInvOnPublic = $enabled;

        return $this;
    }

    public function isAutomationPrintLabelAfterEanAddInventory(): bool
    {
        return $this->printLabelAfterEanAdd;
    }

    public function changeAutomationPrintLabelAfterEanAddInventory(bool $enabled): static
    {
        $this->printLabelAfterEanAdd = $enabled;
        if (!$enabled) {
            $this->automationLabelPrinterDeviceId = null;
        }

        return $this;
    }

    public function getAutomationLabelPrinterDeviceId(): ?PrinterDeviceId
    {
        return $this->automationLabelPrinterDeviceId;
    }

    public function changeAutomationLabelPrinterDeviceId(?PrinterDeviceId $printerDeviceId): static
    {
        $this->automationLabelPrinterDeviceId = $printerDeviceId;

        return $this;
    }
}
