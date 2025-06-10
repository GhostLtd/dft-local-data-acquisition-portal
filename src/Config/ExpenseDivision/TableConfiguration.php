<?php

namespace App\Config\ExpenseDivision;

use App\Config\ExpenseRow\RowGroupInterface;

class TableConfiguration
{
    /**
     * @param array<int, RowGroupInterface> $rowGroupConfigurations
     * @param array<int, DivisionConfiguration> $divisionConfigurations
     * @param array<string, string> $extraTranslationParameters
     */
    public function __construct(
        protected array $rowGroupConfigurations,
        protected array $divisionConfigurations,
        protected array $extraTranslationParameters,
    ) {}

    /**
     * @return array<int, RowGroupInterface>
     */
    public function getRowGroupConfigurations(): array
    {
        return $this->rowGroupConfigurations;
    }

    /**
     * @return array<int, DivisionConfiguration>
     */
    public function getDivisionConfigurations(): array
    {
        return $this->divisionConfigurations;
    }

    public function getDivisionConfigurationByKey(string $key): ?DivisionConfiguration
    {
        foreach($this->getDivisionConfigurations() as $divisionConfiguration) {
            if ($divisionConfiguration->getKey() === $key) {
                return $divisionConfiguration;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getExtraTranslationParameters(): array
    {
        return $this->extraTranslationParameters;
    }
}
