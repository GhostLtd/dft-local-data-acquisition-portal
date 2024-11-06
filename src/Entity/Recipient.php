<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\RecipientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipientRepository::class)]
class Recipient
{
    use IdTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null; // 1top_info: Local Authority name

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $leadContact = null; // 1top_info: Lead contact

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'owner')]
    private Collection $projects;

    /**
     * @var Collection<int, FundAward>
     */
    #[ORM\OneToMany(targetEntity: FundAward::class, mappedBy: 'recipient', orphanRemoval: true)]
    private Collection $fundAwards;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->fundAwards = new ArrayCollection();
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

    public function getLeadContact(): ?Contact
    {
        return $this->leadContact;
    }

    public function setLeadContact(Contact $leadContact): static
    {
        $this->leadContact = $leadContact;
        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setOwner($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getOwner() === $this) {
                $project->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FundAward>
     */
    public function getFundAwards(): Collection
    {
        return $this->fundAwards;
    }

    public function addFundAward(FundAward $fundAward): static
    {
        if (!$this->fundAwards->contains($fundAward)) {
            $this->fundAwards->add($fundAward);
            $fundAward->setRecipient($this);
        }

        return $this;
    }

    public function removeFundAward(FundAward $fundAward): static
    {
        if ($this->fundAwards->removeElement($fundAward)) {
            // set the owning side to null (unless already changed)
            if ($fundAward->getRecipient() === $this) {
                $fundAward->setRecipient(null);
            }
        }

        return $this;
    }
}
