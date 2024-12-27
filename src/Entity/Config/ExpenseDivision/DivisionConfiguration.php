<?php

namespace App\Entity\Config\ExpenseDivision;

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
        protected string                     $key,
        protected array                      $columnConfigurations,
        protected string|TranslatableMessage $label,
    ) {}

    // This is used to index the values in the DB, in the expenses collection, and also as
    // a slug in URLs, so needs to be suitable for both cases.
    public function getKey(): string
    {
        return $this->key;
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

        return $this->label;
    }
}
