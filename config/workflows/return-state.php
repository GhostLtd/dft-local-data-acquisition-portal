<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\FundReturn\FundReturn;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $returnState = $config->workflows()->workflows('return_state');

    $returnState
        ->type('state_machine')
        ->supports([FundReturn::class])
        ->initialMarking(FundReturn::STATE_INITIAL);

    $returnState->auditTrail()->enabled(true);
    $returnState->markingStore()
        ->type('method')
        ->property('state');

    $returnState->place()->name(FundReturn::STATE_INITIAL);
    $returnState->place()->name(FundReturn::STATE_OPEN);
    $returnState->place()->name(FundReturn::STATE_SUBMITTED);

    $returnState->transition()
        ->name(FundReturn::TRANSITION_OPEN_RETURN)
        ->from(FundReturn::STATE_INITIAL)
        ->to(FundReturn::STATE_OPEN);

    $returnState->transition()
        ->name(FundReturn::TRANSITION_SUBMIT_RETURN)
        ->from(FundReturn::STATE_OPEN)
        ->to(FundReturn::STATE_SUBMITTED);

    $returnState->transition()
        ->name(FundReturn::TRANSITION_REOPEN_RETURN)
        ->from(FundReturn::STATE_SUBMITTED)
        ->to(FundReturn::STATE_OPEN);
};
