<?php

namespace App\EventSubscriber;

use App\Entity\Enum\CompletionStatus;
use App\Event\FundReturnSectionUpdateEvent;
use App\Event\ProjectReturnSectionUpdateEvent;
use App\Event\ReturnSectionUpdateEvent;
use App\Form\ReturnBaseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FundReturnSectionUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            FundReturnSectionUpdateEvent::class => 'onReturnSectionUpdateEvent',
            ProjectReturnSectionUpdateEvent::class => 'onReturnSectionUpdateEvent',
        ];
    }

    public function onReturnSectionUpdateEvent(ReturnSectionUpdateEvent $event): void
    {
        $status = $event->getOrCreateSectionStatus();

        if (!$this->entityManager->contains($status)) {
            $this->entityManager->persist($status);
        }

        $mode = $event->getMode();
        if ($mode === ReturnBaseType::MARK_AS_COMPLETED) {
            $status->setStatus(CompletionStatus::COMPLETED);
        } else if (
            $mode === ReturnBaseType::MARK_AS_IN_PROGRESS ||
            $status->getStatus() !== CompletionStatus::COMPLETED
        ) {
            $status->setStatus(CompletionStatus::IN_PROGRESS);
        }
    }
}
