<?php

namespace App\Entity;

use App\Entity\ProjectFund\ProjectFund;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipient $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null; // 1proj_info: Project name

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null; // 1proj_info: Project description

    /**
     * @var Collection<int, ProjectFund>
     */
    #[ORM\OneToMany(targetEntity: ProjectFund::class, mappedBy: 'project')]
    private Collection $projectFunds;

    public function __construct()
    {
        $this->projectFunds = new ArrayCollection();
    }

    public function getOwner(): ?Recipient
    {
        return $this->owner;
    }

    public function setOwner(?Recipient $owner): static
    {
        $this->owner = $owner;
        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Collection<int, ProjectFund>
     */
    public function getProjectFunds(): Collection
    {
        return $this->projectFunds;
    }

    public function addProjectFund(ProjectFund $projectFund): static
    {
        if (!$this->projectFunds->contains($projectFund)) {
            $this->projectFunds->add($projectFund);
            $projectFund->setProject($this);
        }

        return $this;
    }

    public function removeProjectFund(ProjectFund $projectFund): static
    {
        if ($this->projectFunds->removeElement($projectFund)) {
            // set the owning side to null (unless already changed)
            if ($projectFund->getProject() === $this) {
                $projectFund->setProject(null);
            }
        }

        return $this;
    }
}
