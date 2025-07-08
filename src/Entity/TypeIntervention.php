<?php

namespace App\Entity;

use App\Repository\TypeInterventionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Entity(repositoryClass: TypeInterventionRepository::class)]
class TypeIntervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["get_types_intervention", "get_type_Intervention"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["get_interventions", "get_intervention", "get_types_intervention", "get_type_Intervention", "get_modele_planning", "get_modele_interventions", "get_modele_intervention"])]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(["get_interventions", "get_intervention", "get_types_intervention", "get_type_Intervention", "get_modele_planning", "get_modele_interventions", "get_modele_intervention"])]
    private ?\DateTimeInterface $duree = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(["get_interventions", "get_intervention", "get_types_intervention", "get_type_Intervention", "get_modele_interventions", "get_modele_intervention"])]
    private ?string $prixDepart = null;

    /**
     * @var Collection<int, Intervention>
     */
    #[ORM\OneToMany(targetEntity: Intervention::class, mappedBy: 'typeIntervention')]
    #[MaxDepth(1)]
    private Collection $interventions;

    /**
     * @var Collection<int, ModeleInterventions>
     */
    #[ORM\OneToMany(targetEntity: ModeleInterventions::class, mappedBy: 'typeIntervention')]
    #[MaxDepth(1)]
    private Collection $modeleInterventions;

    public function __construct()
    {
        $this->interventions = new ArrayCollection();
        $this->modeleInterventions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDuree(): ?\DateTimeInterface
    {
        return $this->duree;
    }

    public function setDuree(\DateTimeInterface $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getPrixDepart(): ?string
    {
        return $this->prixDepart;
    }

    public function setPrixDepart(string $prixDepart): static
    {
        $this->prixDepart = $prixDepart;

        return $this;
    }

    /**
     * @return Collection<int, Intervention>
     */
    public function getInterventions(): Collection
    {
        return $this->interventions;
    }

    public function addIntervention(Intervention $intervention): static
    {
        if (!$this->interventions->contains($intervention)) {
            $this->interventions->add($intervention);
            $intervention->setTypeIntervention($this);
        }

        return $this;
    }

    public function removeIntervention(Intervention $intervention): static
    {
        if ($this->interventions->removeElement($intervention)) {
            // set the owning side to null (unless already changed)
            if ($intervention->getTypeIntervention() === $this) {
                $intervention->setTypeIntervention(null);
            }
        }

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
            $modeleIntervention->setTypeIntervention($this);
        }

        return $this;
    }

    public function removeModeleIntervention(ModeleInterventions $modeleIntervention): static
    {
        if ($this->modeleInterventions->removeElement($modeleIntervention)) {
            // set the owning side to null (unless already changed)
            if ($modeleIntervention->getTypeIntervention() === $this) {
                $modeleIntervention->setTypeIntervention(null);
            }
        }

        return $this;
    }
}
