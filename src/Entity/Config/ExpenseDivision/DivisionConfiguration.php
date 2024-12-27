<?php

namespace App\Entity\Config\ExpenseDivision;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

// Divisions map to groupings of columns on the spreadsheet (e.g. 2022/23)
// Columns then map to the individual columns on the spreadsheet (e.g. Q1)
class DivisionConfiguration
{
    /**
     * @param array<int, ColumnConfiguration> $columnConfigurations
     */
    public function __construct(
        protected string                          $title,
        protected array                           $columnConfigurations,
        protected null|string|TranslatableMessage $label = null,
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

    public function getLabel(array $extraParameters = []): string|TranslatableInterface
    {
        if ($this->label instanceof TranslatableMessage && !empty($extraParameters)) {
            return new TranslatableMessage(
                $this->label->getMessage(),
                array_merge($extraParameters, $this->label->getParameters()),
                $this->label->getDomain()
            );
        }

        return $this->label ?? $this->title;
    }
}
