<?php

namespace App\Entity\Config\ExpenseRow;

use Symfony\Component\String\Slugger\AsciiSlugger;

class TotalConfiguration implements RowGroupInterface
{
    /**
     * @param array<int, string> $slugsOfRowsToSum
     */
    public function __construct(
        protected string $title,
        protected array  $slugsOfRowsToSum,
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
}
