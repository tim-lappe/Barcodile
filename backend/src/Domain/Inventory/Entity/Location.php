<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Inventory\Repository\LocationRepository;
use App\Domain\Shared\Id\LocationId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\Column(type: 'location_id', unique: true)]
    private LocationId $locationId;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(referencedColumnName: 'location_id', onDelete: 'SET NULL')]
    private ?Location $parent = null;

    public function __construct()
    {
        $this->locationId = new LocationId();
    }

    public function getId(): LocationId
    {
        return $this->locationId;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function changeParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
