<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Inventory\Repository\LocationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[Groups(['location:read', 'inventory_item:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'location_id', unique: true)]
    private LocationId $locationId;

    #[Groups(['location:read', 'location:write', 'inventory_item:read'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[Groups(['location:read', 'location:write'])]
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
