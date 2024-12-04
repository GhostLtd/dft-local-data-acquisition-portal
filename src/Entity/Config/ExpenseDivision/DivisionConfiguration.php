<?php

namespace App\Entity\Config\ExpenseDivision;

use App\Entity\Config\ExpenseDivision\SubDivisionConfiguration;
use Symfony\Component\String\Slugger\AsciiSlugger;

// Divisions map to column sections on the spreadsheet (e.g. 2022/23)
// SubDivisions then map to the sub-columns on the spreadsheet (e.g. Q1)
class DivisionConfiguration
{
    /**
     * @param array<int, SubDivisionConfiguration> $subDivisionConfigurations
     */
    public function __construct(
        protected string $title,
        protected array  $subDivisionConfigurations,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        $slugger = new AsciiSlugger();
        return $slugger->slug(strtolower($this->title));
    }

    /**
     * @return array<int, SubDivisionConfiguration>
     */
    public function getSubDivisionConfigurations(): array
    {
        return $this->subDivisionConfigurations;
    }

    public function shouldHaveTotal(): bool
    {
        return count($this->getSubDivisionConfigurations()) > 1;
    }
}
