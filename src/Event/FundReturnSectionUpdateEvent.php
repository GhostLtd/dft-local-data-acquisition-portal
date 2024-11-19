<?php

namespace App\Event;

use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\FundLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Form\ReturnBaseType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\Event;

class FundReturnSectionUpdateEvent extends Event
{
    /** @var array<string, string> */
    protected array $options;

    public function __construct(
        protected FundReturn                   $fundReturn,
        protected ExpenseType|FundLevelSection $section,
        array                                  $options,
    )
    {
        $this->options = (new OptionsResolver())
            ->setDefault('mode', ReturnBaseType::SAVE)
            ->setAllowedValues('mode', [ReturnBaseType::SAVE, ReturnBaseType::MARK_AS_COMPLETED, ReturnBaseType::MARK_AS_IN_PROGRESS])
            ->resolve($options);
    }

    public function getFundReturn(): FundReturn
    {
        return $this->fundReturn;
    }

    public function getSection(): FundLevelSection|ExpenseType
    {
        return $this->section;
    }

    public function getMode(): string
    {
        return $this->options['mode'];
    }
}
