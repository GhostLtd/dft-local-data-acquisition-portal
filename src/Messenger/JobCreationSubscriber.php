<?php

namespace App\Messenger;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;

class JobCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(protected JobCacheHelper $jobCacheHelper)
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'onSendMessageToTransports',
        ];
    }

    public function onSendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        $job = $event->getEnvelope()->getMessage();

        if (!$job instanceof JobInterface) {
            return;
        }

        $this->jobCacheHelper->createJobStatus($job->getId());
    }
}
