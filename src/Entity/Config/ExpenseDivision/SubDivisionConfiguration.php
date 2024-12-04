<?php

namespace App\Entity\Config\ExpenseDivision;

use Symfony\Component\String\Slugger\AsciiSlugger;

class SubDivisionConfiguration
{
    public function __construct(
        protected string $title,
        protected bool   $isForecast,
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

    public function isForecast(): bool
    {
        return $this->isForecast;
    }
}
