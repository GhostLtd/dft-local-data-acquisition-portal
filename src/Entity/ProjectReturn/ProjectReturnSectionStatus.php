<?php

namespace App\Entity\ProjectReturn;

use App\Entity\Enum\CompletionStatus;
use App\Repository\ProjectReturn\ProjectReturnSectionStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectReturnSectionStatusRepository::class)]
class ProjectReturnSectionStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(enumType: CompletionStatus::class)]
    private ?CompletionStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'sectionStatuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectReturn $projectReturn = null;

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

    public function getStatus(): ?CompletionStatus
    {
        return $this->status;
    }

    public function setStatus(CompletionStatus $status): static
    {
        $this->status = $status;

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
