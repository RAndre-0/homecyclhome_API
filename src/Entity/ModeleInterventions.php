<?php

namespace App\Entity;

use App\Repository\ModeleInterventionsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ModeleInterventionsRepository::class)]
class ModeleInterventions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["get_modele_interventions", "get_modele_intervention", "get_modele_planning"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(["get_modele_planning", "get_modele_interventions", "get_modele_intervention"])]
    private ?\DateTimeInterface $interventionTime = null;

    #[ORM\ManyToOne(inversedBy: 'modeleInterventions')]
    #[Groups(["get_modele_planning", "get_modele_interventions", "get_modele_intervention"])]
    private ?TypeIntervention $typeIntervention = null;

    #[ORM\ManyToOne(inversedBy: 'modeleInterventions')]
    #[Groups(["get_modele_interventions", "get_modele_intervention"])]
    private ?ModelePlanning $modeleIntervention = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInterventionTime(): ?\DateTimeInterface
    {
        return $this->interventionTime;
    }

    public function setInterventionTime(\DateTimeInterface $interventionTime): static
    {
        $this->interventionTime = $interventionTime;

        return $this;
    }

    public function getTypeIntervention(): ?TypeIntervention
    {
        return $this->typeIntervention;
    }

    public function setTypeIntervention(?TypeIntervention $typeIntervention): static
    {
        $this->typeIntervention = $typeIntervention;

        return $this;
    }

    public function getModeleIntervention(): ?ModelePlanning
    {
        return $this->modeleIntervention;
    }

    public function setModeleIntervention(?ModelePlanning $modeleIntervention): static
    {
        $this->modeleIntervention = $modeleIntervention;

        return $this;
    }
}
