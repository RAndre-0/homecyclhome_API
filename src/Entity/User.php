<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["get_users", "get_user"])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(["get_users", "get_user"])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(["get_users", "get_user"])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Intervention>
     */
    #[ORM\OneToMany(targetEntity: Intervention::class, mappedBy: 'client')]
    private Collection $demandes_intervention;

    /**
     * @var Collection<int, Intervention>
     */
    #[ORM\OneToMany(targetEntity: Intervention::class, mappedBy: 'technicien')]
    private Collection $interventions;

    /**
     * @var Collection<int, CommentaireIntervention>
     */
    #[ORM\OneToMany(targetEntity: CommentaireIntervention::class, mappedBy: 'technicien')]
    private Collection $commentaireInterventions;

    #[ORM\OneToOne(mappedBy: 'technician', cascade: ['persist', 'remove'])]
    private ?Zone $zone = null;

    public function __construct()
    {
        $this->demandes_intervention = new ArrayCollection();
        $this->interventions = new ArrayCollection();
        $this->commentaireInterventions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Retourne le champ utilisé pour l'authentification.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->getUserIdentifier();
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Intervention>
     */
    public function getDemandesIntervention(): Collection
    {
        return $this->demandes_intervention;
    }

    public function addDemandesIntervention(Intervention $demandesIntervention): static
    {
        if (!$this->demandes_intervention->contains($demandesIntervention)) {
            $this->demandes_intervention->add($demandesIntervention);
            $demandesIntervention->setClient($this);
        }

        return $this;
    }

    public function removeDemandesIntervention(Intervention $demandesIntervention): static
    {
        if ($this->demandes_intervention->removeElement($demandesIntervention)) {
            // set the owning side to null (unless already changed)
            if ($demandesIntervention->getClient() === $this) {
                $demandesIntervention->setClient(null);
            }
        }

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
            $intervention->setTechnicien($this);
        }

        return $this;
    }

    public function removeIntervention(Intervention $intervention): static
    {
        if ($this->interventions->removeElement($intervention)) {
            // set the owning side to null (unless already changed)
            if ($intervention->getTechnicien() === $this) {
                $intervention->setTechnicien(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CommentaireIntervention>
     */
    public function getCommentaireInterventions(): Collection
    {
        return $this->commentaireInterventions;
    }

    public function addCommentaireIntervention(CommentaireIntervention $commentaireIntervention): static
    {
        if (!$this->commentaireInterventions->contains($commentaireIntervention)) {
            $this->commentaireInterventions->add($commentaireIntervention);
            $commentaireIntervention->setTechnicien($this);
        }

        return $this;
    }

    public function removeCommentaireIntervention(CommentaireIntervention $commentaireIntervention): static
    {
        if ($this->commentaireInterventions->removeElement($commentaireIntervention)) {
            // set the owning side to null (unless already changed)
            if ($commentaireIntervention->getTechnicien() === $this) {
                $commentaireIntervention->setTechnicien(null);
            }
        }

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        // unset the owning side of the relation if necessary
        if ($zone === null && $this->zone !== null) {
            $this->zone->setTechnician(null);
        }

        // set the owning side of the relation if necessary
        if ($zone !== null && $zone->getTechnician() !== $this) {
            $zone->setTechnician($this);
        }

        $this->zone = $zone;

        return $this;
    }
}
