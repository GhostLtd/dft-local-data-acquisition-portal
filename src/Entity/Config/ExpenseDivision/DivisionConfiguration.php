<?php

namespace App\Entity\Config\ExpenseDivision;

use Symfony\Component\String\Slugger\AsciiSlugger;

// Divisions map to groupings of columns on the spreadsheet (e.g. 2022/23)
// Columns then map to the individual columns on the spreadsheet (e.g. Q1)
class DivisionConfiguration
{
    /**
     * @param array<int, ColumnConfiguration> $columnConfigurations
     */
    public function __construct(
        protected string $title,
        protected array  $columnConfigurations,
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
     * @return array<int, ColumnConfiguration>
     */
    public function getColumnConfigurations(): array
    {
        return $this->columnConfigurations;
    }

    public function shouldHaveTotal(): bool
    {
        return count($this->getColumnConfigurations()) > 1;
    }
}
