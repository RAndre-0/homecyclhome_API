<?php

namespace App\Entity;

use App\Repository\ZoneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Validator as AcmeAssert;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
class Zone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["get_zones"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["get_zones"])]
    private ?string $name = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\CssColor(
        formats: Assert\CssColor::HEX_LONG,
        message: 'La couleur doit être une suite de 6 caractère hexadécimaux.',
    )]
    #[Groups(["get_zones"])]
    private ?string $color = "#757575";

    #[ORM\Column(nullable: true)]
    #[Groups(["get_zones"])]
    #[AcmeAssert\ValidCoordinates()]
    private ?array $coordinates = null;

    #[ORM\OneToOne(inversedBy: 'zone', cascade: ['persist'])]
    #[Groups(["get_zones"])]
    private ?User $technicien = null;    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getCoordinates(): ?array
    {
        return $this->coordinates;
    }

    public function setCoordinates(?array $coordinates): static
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function getTechnicien(): ?User
    {
        return $this->technicien;
    }

    public function setTechnicien(?User $technicien): static
    {
        $this->technicien = $technicien;

        return $this;
    }

    public function containsPoint(float $latitude, float $longitude): bool
    {
        $polygon = array_map(
            fn ($coord) => [$coord['longitude'], $coord['latitude']],
            $this->getCoordinates() ?? []
        );

        $x = $longitude;
        $y = $latitude;
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-10) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
    
}
