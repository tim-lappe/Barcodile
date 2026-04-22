<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Entity;

use App\Domain\Scanner\Events\CodeScanned;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Domain\Shared\DomainEventRecorder;
use App\Domain\Shared\RecordsDomainEvents;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ScannerDeviceRepository::class)]
#[ORM\Table(name: 'scanner_device')]
class ScannerDevice implements RecordsDomainEvents
{
    use DomainEventRecorder;

    private const int MAX_LAST_SCANNED_CODES = 100;

    #[Groups(['scanner_device:read'])]
    #[ORM\Id]
    #[ORM\Column(name: 'scanner_device_id', type: 'scanner_device_id', unique: true)]
    private ScannerDeviceId $scannerDeviceId;

    #[Groups(['scanner_device:read', 'scanner_device:write'])]
    #[ORM\Column(name: 'device_identifier', length: 512)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 512)]
    private string $deviceIdentifier = '';

    #[Groups(['scanner_device:read', 'scanner_device:write'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    /**
     * @var list<string>|null
     */
    #[Groups(['scanner_device:read'])]
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $lastScannedCodes = null;

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
}
