<?php

namespace App\Entity\ProjectReturn;

use App\Entity\SectionStatusInterface;
use App\Entity\SectionStatusTrait;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectReturn\ProjectReturnSectionStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectReturnSectionStatusRepository::class)]
class ProjectReturnSectionStatus implements SectionStatusInterface
{
    use IdTrait, SectionStatusTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'sectionStatuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectReturn $projectReturn = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getProjectReturn(): ?ProjectReturn
    {
        return $this->projectReturn;
    }

    public function setProjectReturn(?ProjectReturn $projectReturn): static
    {
        $this->projectReturn = $projectReturn;

        return $this;
    }
}
