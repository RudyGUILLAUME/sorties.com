<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $mail = null;

    #[ORM\Column]
    private ?bool $administrateur = null;

    #[ORM\Column]
    private ?bool $actif = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'organisateur')]
    private Collection $sorties_organises;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\ManyToMany(targetEntity: Sortie::class, mappedBy: 'participants')]
    private Collection $sorties_inscrits;

    public function __construct()
    {
        $this->sorties_organises = new ArrayCollection();
        $this->sorties_inscrits = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function isAdministrateur(): ?bool
    {
        return $this->administrateur;
    }

    public function setAdministrateur(bool $administrateur): static
    {
        $this->administrateur = $administrateur;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortiesOrganises(): Collection
    {
        return $this->sorties_organises;
    }

    public function addSortiesOrganise(Sortie $sortiesOrganise): static
    {
        if (!$this->sorties_organises->contains($sortiesOrganise)) {
            $this->sorties_organises->add($sortiesOrganise);
            $sortiesOrganise->setOrganisateur($this);
        }

        return $this;
    }

    public function removeSortiesOrganise(Sortie $sortiesOrganise): static
    {
        if ($this->sorties_organises->removeElement($sortiesOrganise)) {
            // set the owning side to null (unless already changed)
            if ($sortiesOrganise->getOrganisateur() === $this) {
                $sortiesOrganise->setOrganisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortiesInscrits(): Collection
    {
        return $this->sorties_inscrits;
    }

    public function addSortiesInscrit(Sortie $sortiesInscrit): static
    {
        if (!$this->sorties_inscrits->contains($sortiesInscrit)) {
            $this->sorties_inscrits->add($sortiesInscrit);
            $sortiesInscrit->addParticipant($this);
        }

        return $this;
    }

    public function removeSortiesInscrit(Sortie $sortiesInscrit): static
    {
        if ($this->sorties_inscrits->removeElement($sortiesInscrit)) {
            $sortiesInscrit->removeParticipant($this);
        }

        return $this;
    }
}
