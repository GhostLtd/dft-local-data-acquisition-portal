<?php

namespace App\Config;

use App\Entity\Enum\SchemeLevelSection;
use App\Entity\Enum\SectionDisplayGroup;
use Symfony\Component\Form\FormInterface;

class SchemeLevelSectionConfiguration
{
    /**
     * @param class-string<FormInterface> $formClass
     */
    public function __construct(
        protected SchemeLevelSection  $section,
        protected string              $formClass,
        protected SectionDisplayGroup $displayGroup = SectionDisplayGroup::DETAILS,
    ) {}

    public function getSection(): SchemeLevelSection
    {
        return $this->section;
    }

    public function getFormClass(): string
    {
        return $this->formClass;
    }

    public function getDisplayGroup(): SectionDisplayGroup
    {
        return $this->displayGroup;
    }
}
