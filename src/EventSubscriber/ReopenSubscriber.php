<?php

namespace App\EventSubscriber;

use App\Entity\FundReturn\FundReturn;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class ReopenSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.return_state.transition.'.FundReturn::TRANSITION_REOPEN_RETURN => 'onReopenReturn',
        ];
    }

    public function onReopenReturn(Event $event): void
    {
        $return = $event->getSubject();

        if (!$return instanceof FundReturn) {
            throw new \RuntimeException('Invalid subject for reopen_return transition');
        }

        $return->reOpen();
    }
}
