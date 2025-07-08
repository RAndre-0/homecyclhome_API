<?php

namespace App\Entity;

use App\Repository\ModelePlanningRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ModelePlanningRepository::class)]
class ModelePlanning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["get_modele_planning", "get_modeles_planning"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["get_modele_planning", "get_modeles_planning", "get_modele_interventions", "get_modele_intervention"])]
    private ?string $name = null;

    /**
     * @var Collection<int, ModeleInterventions>
     */
    #[ORM\OneToMany(targetEntity: ModeleInterventions::class, mappedBy: 'modeleIntervention')]
    #[Groups(["get_modele_planning"])]
    private Collection $modeleInterventions;

    public function __construct()
    {
        $this->modeleInterventions = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, ModeleInterventions>
     */
    public function getModeleInterventions(): Collection
    {
        return $this->modeleInterventions;
    }

    public function addModeleIntervention(ModeleInterventions $modeleIntervention): static
    {
        if (!$this->modeleInterventions->contains($modeleIntervention)) {
            $this->modeleInterventions->add($modeleIntervention);
            $modeleIntervention->setModeleIntervention($this);
        }

        return $this;
    }

    public function removeModeleIntervention(ModeleInterventions $modeleIntervention): static
    {
        if ($this->modeleInterventions->removeElement($modeleIntervention)) {
            // set the owning side to null (unless already changed)
            if ($modeleIntervention->getModeleIntervention() === $this) {
                $modeleIntervention->setModeleIntervention(null);
            }
        }

        return $this;
    }
}
