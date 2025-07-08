<?php

namespace App\Entity;

use App\Repository\InterventionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["get_produit", "get_interventions", "get_intervention"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?string $veloCategorie = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?bool $veloElectrique = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?string $veloMarque = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?string $veloModele = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?string $commentaireClient = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?string $photo = null;

    /**
     * @var Collection<int, InterventionProduit>
     */
    #[ORM\OneToMany(targetEntity: InterventionProduit::class, mappedBy: 'intervention')]
    #[Groups(["get_intervention"])]
    #[MaxDepth(1)]
    private Collection $interventionProduit;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    #[Groups(["get_interventions", "get_intervention"])]
    #[MaxDepth(1)]
    private ?TypeIntervention $typeIntervention = null;

    #[ORM\ManyToOne(inversedBy: 'demandes_intervention')]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?User $client = null;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?User $technicien = null;

    /**
     * @var Collection<int, CommentaireIntervention>
     */
    #[ORM\OneToMany(targetEntity: CommentaireIntervention::class, mappedBy: 'intervention')]
    private Collection $commentaires;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?\DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["get_interventions", "get_intervention"])]
    private ?\DateTimeInterface $fin = null;

    public function __construct()
    {
        $this->interventionProduit = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVeloCategorie(): ?string
    {
        return $this->veloCategorie;
    }

    public function setVeloCategorie(?string $veloCategorie): static
    {
        $this->veloCategorie = $veloCategorie;
        return $this;
    }

    public function isVeloElectrique(): ?bool
    {
        return $this->veloElectrique;
    }

    public function setVeloElectrique(?bool $veloElectrique): static
    {
        $this->veloElectrique = $veloElectrique;
        return $this;
    }

    public function getVeloMarque(): ?string
    {
        return $this->veloMarque;
    }

    public function setVeloMarque(?string $veloMarque): static
    {
        $this->veloMarque = $veloMarque;
        return $this;
    }

    public function getVeloModele(): ?string
    {
        return $this->veloModele;
    }

    public function setVeloModele(string $veloModele): static
    {
        $this->veloModele = $veloModele;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCommentaireClient(): ?string
    {
        return $this->commentaireClient;
    }

    public function setCommentaireClient(?string $commentaireClient): static
    {
        $this->commentaireClient = $commentaireClient;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    /**
     * @return Collection<int, InterventionProduit>
     */
    public function getInterventionProduit(): Collection
    {
        return $this->interventionProduit;
    }

    public function addInterventionProduit(InterventionProduit $interventionProduit): static
    {
        if (!$this->interventionProduit->contains($interventionProduit)) {
            $this->interventionProduit->add($interventionProduit);
            $interventionProduit->setIntervention($this);
        }

        return $this;
    }

    public function removeInterventionProduit(InterventionProduit $interventionProduit): static
    {
        if ($this->interventionProduit->removeElement($interventionProduit)) {
            // set the owning side to null (unless already changed)
            if ($interventionProduit->getIntervention() === $this) {
                $interventionProduit->setIntervention(null);
            }
        }

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

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

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

    /**
     * @return Collection<int, CommentaireIntervention>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(CommentaireIntervention $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setIntervention($this);
        }

        return $this;
    }

    public function removeCommentaire(CommentaireIntervention $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getIntervention() === $this) {
                $commentaire->setIntervention(null);
            }
        }

        return $this;
    }

    public function getDebut(): ?\DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(?\DateTimeInterface $debut): static
    {
        $this->debut = $debut;

        return $this;
    }

    public function getFin(): ?\DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(?\DateTimeInterface $fin): static
    {
        $this->fin = $fin;

        return $this;
    }
}
