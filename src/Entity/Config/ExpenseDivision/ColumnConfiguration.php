<?php

namespace App\Entity\Config\ExpenseDivision;

use App\Entity\Config\LabelProviderInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class ColumnConfiguration implements LabelProviderInterface
{
    public function __construct(
        protected string                            $title,
        protected bool                              $isForecast,
        protected null|string|TranslatableInterface $label = null,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
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

    public function getSlug(): string
    {
        $slugger = new AsciiSlugger();
        return $slugger->slug(strtolower($this->title));
    }

    public function isForecast(): bool
    {
        return $this->isForecast;
    }
}
