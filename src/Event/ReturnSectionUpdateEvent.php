<?php

namespace App\Event;

use App\Entity\FundReturn\FundReturnSectionStatus;
use App\Entity\ProjectReturn\ProjectReturnSectionStatus;
use App\Form\ReturnBaseType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\Event;

abstract class ReturnSectionUpdateEvent extends Event
{
    /** @var array<string, string> */
    protected array $options;

    public function __construct(array $options)
    {
        $this->options = (new OptionsResolver())
            ->setDefault('mode', ReturnBaseType::SAVE)
            ->setAllowedValues('mode', [ReturnBaseType::SAVE, ReturnBaseType::MARK_AS_COMPLETED, ReturnBaseType::MARK_AS_IN_PROGRESS])
            ->resolve($options);
    }

    public function getMode(): string
    {
        return $this->options['mode'];
    }

    abstract public function getOrCreateSectionStatus(): FundReturnSectionStatus|ProjectReturnSectionStatus;
}
