<?php

namespace App\Entity;

use App\Repository\CommentaireInterventionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CommentaireInterventionRepository::class)]
class CommentaireIntervention
{
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["get_intervention"])]
    private ?string $contenu = null;

    #[ORM\Column]
    #[Groups(["get_intervention"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'commentaireInterventions')]
    private ?User $technicien = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    private ?Intervention $intervention = null;

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function setIntervention(?Intervention $intervention): static
    {
        $this->intervention = $intervention;

        return $this;
    }
}
