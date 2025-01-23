<?php

namespace App\Config;

use App\Entity\Enum\SchemeLevelSection;
use Symfony\Component\Form\FormInterface;

class SchemeLevelSectionConfiguration
{
    /**
     * @param class-string<FormInterface> $formClass
     */
    public function __construct(
        protected SchemeLevelSection $section,
        protected string             $formClass,
        protected bool               $isDisplayedInExpensesList=false,
    ) {}

    public function getSection(): SchemeLevelSection
    {
        return $this->section;
    }

    public function getFormClass(): string
    {
        return $this->formClass;
    }

    public function isDisplayedInExpensesList(): bool
    {
        return $this->isDisplayedInExpensesList;
    }
}