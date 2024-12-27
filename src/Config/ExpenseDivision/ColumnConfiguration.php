<?php

namespace App\Config\ExpenseDivision;

use App\Config\LabelProviderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class ColumnConfiguration implements LabelProviderInterface
{
    public function __construct(
        protected string                            $key,
        protected bool                              $isForecast,
        protected null|string|TranslatableInterface $label = null,
    ) {}

    public function getKey(): string
    {
        return $this->key;
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

        return $this->label ?? $this->key;
    }

    public function isForecast(): bool
    {
        return $this->isForecast;
    }
}
