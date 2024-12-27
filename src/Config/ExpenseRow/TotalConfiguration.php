<?php

namespace App\Config\ExpenseRow;

use App\Config\ExpenseRow\RowGroupInterface;
use App\Config\LabelProviderInterface;
use Symfony\Component\Translation\TranslatableMessage;

class TotalConfiguration implements LabelProviderInterface, RowGroupInterface
{
    /**
     * @param array<int, string> $keysOfRowsToSum
     */
    public function __construct(
        protected string                          $key,
        protected array                           $keysOfRowsToSum,
        protected null|string|TranslatableMessage $label,
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getKeysOfRowsToSum(): array
    {
        return $this->keysOfRowsToSum;
    }

    public function getLabel(array $extraParameters = []): string|TranslatableMessage
    {
        if ($this->label instanceof TranslatableMessage && !empty($extraParameters)) {
            return new TranslatableMessage(
                $this->label->getMessage(),
                array_merge($extraParameters, $this->label->getParameters()),
                $this->label->getDomain()
            );
        }

        return $this->label ?? $this->key;
    }
}
