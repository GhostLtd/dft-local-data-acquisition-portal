<?php

namespace App\Entity\SchemeReturn;

use App\Entity\SectionStatusInterface;
use App\Entity\SectionStatusTrait;
use App\Entity\Traits\IdTrait;
use App\Repository\SchemeReturn\SchemeReturnSectionStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchemeReturnSectionStatusRepository::class)]
#[ORM\UniqueConstraint(columns: ['name', 'status', 'scheme_return_id'])]
class SchemeReturnSectionStatus implements SectionStatusInterface
{
    use IdTrait, SectionStatusTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'sectionStatuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SchemeReturn $schemeReturn = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSchemeReturn(): ?SchemeReturn
    {
        return $this->schemeReturn;
    }

    public function setSchemeReturn(?SchemeReturn $schemeReturn): static
    {
        $this->schemeReturn = $schemeReturn;
        return $this;
    }
}
