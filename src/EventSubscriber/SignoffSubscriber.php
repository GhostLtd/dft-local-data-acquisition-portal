<?php

namespace App\EventSubscriber;

use App\Entity\FundReturn\FundReturn;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class SignoffSubscriber implements EventSubscriberInterface
{
    public function __construct(protected Security $security)
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.return_state.transition.'.FundReturn::TRANSITION_SUBMIT_RETURN => 'onSubmitReturn',
        ];
    }

    public function onSubmitReturn(Event $event): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $return = $event->getSubject();

        if (!$return instanceof FundReturn) {
            throw new \RuntimeException('Invalid subject for submit_return transition');
        }

        $return->signoff($user);
    }
}
