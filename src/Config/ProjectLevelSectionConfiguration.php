<?php

namespace App\Config;

use App\Entity\Enum\ProjectLevelSection;
use Symfony\Component\Form\FormInterface;

class ProjectLevelSectionConfiguration
{
    /**
     * @param class-string<FormInterface> $formClass
     */
    public function __construct(
        protected ProjectLevelSection $section,
        protected string              $formClass,
        protected bool                $isDisplayedInExpensesList=false,
    ) {}

    public function getSection(): ProjectLevelSection
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