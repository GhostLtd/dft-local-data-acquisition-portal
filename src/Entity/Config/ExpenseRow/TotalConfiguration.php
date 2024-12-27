<?php

namespace App\Entity\Config\ExpenseRow;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Translation\TranslatableMessage;

class TotalConfiguration implements RowGroupInterface
{
    /**
     * @param array<int, string> $slugsOfRowsToSum
     */
    public function __construct(
        protected string $title,
        protected array  $slugsOfRowsToSum,
        protected null|string|TranslatableMessage $label,
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

    public function getSlugsOfRowsToSum(): array
    {
        return $this->slugsOfRowsToSum;
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

        return $this->label ?? $this->title;
    }
}
