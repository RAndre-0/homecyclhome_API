<?php

namespace App\Entity;

use App\Repository\InterventionProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Entity(repositoryClass: InterventionProduitRepository::class)]
class InterventionProduit
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'interventionProduit')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["get_intervention"])]
    private ?Produit $produit = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'interventionProduit')]
    #[ORM\JoinColumn(nullable: false)]
    #[MaxDepth(1)]
    #[Groups(["get_produit"])]
    private ?Intervention $intervention = null;

    #[ORM\Column]
    #[Groups(["get_produit", "get_intervention"])]
    private ?int $quantite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(["get_produit", "get_intervention"])]
    private ?string $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["get_intervention"])]
    private ?string $designation = null;

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function setIntervention(?Intervention $intervention): static
    {
        $this->intervention = $intervention;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): static
    {
        $this->designation = $designation;

        return $this;
    }
}
