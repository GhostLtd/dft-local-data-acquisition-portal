<?php

namespace App\Entity;

use App\Entity\Enum\Fund;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\Traits\IdTrait;
use App\Repository\AuthorityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthorityRepository::class)]
class Authority
{
    use IdTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null; // 1top_info: Local Authority name

    #[ORM\ManyToOne(inversedBy: 'authoritiesAdminOf')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $admin = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'owner')]
    private Collection $projects;

    /**
     * @var Collection<int, FundAward>
     */
    #[ORM\OneToMany(targetEntity: FundAward::class, mappedBy: 'authority', orphanRemoval: true)]
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

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): static
    {
        $this->admin = $admin;
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
            $fundAward->setAuthority($this);
        }

        return $this;
    }

    public function removeFundAward(FundAward $fundAward): static
    {
        if ($this->fundAwards->removeElement($fundAward)) {
            // set the owning side to null (unless already changed)
            if ($fundAward->getAuthority() === $this) {
                $fundAward->setAuthority(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    /**
     * Returns a collection of the projects that this authority has, that receive funding from the specified $fund
     * @return Collection<Project>
     */
    public function getProjectsForFund(Fund $fund): Collection
    {
        return $this->projects->filter(fn(Project $p) => $p->getProjectFunds()->reduce(
            fn(bool $carry, ProjectFund $projectFund) => $carry || $projectFund->getFund() === $fund,
            false,
        ));
    }

    /**
     * Returns a collection of the projectFunds that this authority has, that receive funding from the specified $fund
     * @return Collection<ProjectFund>
     */
    public function getProjectFundsForFund(Fund $fund): Collection
    {
        return $this->projects
            ->map(fn(Project $p) => $p->getProjectFundForFund($fund))
            ->filter(fn(?ProjectFund $pf) => $pf !== null);
    }
}
