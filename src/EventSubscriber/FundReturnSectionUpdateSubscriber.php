<?php

namespace App\EventSubscriber;

use App\Entity\Enum\CompletionStatus;
use App\Entity\FundReturn\FundReturnSectionStatus;
use App\Event\FundReturnSectionUpdateEvent;
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
            FundReturnSectionUpdateEvent::class => 'onFundReturnSectionUpdateEvent',
        ];
    }

    public function onFundReturnSectionUpdateEvent(FundReturnSectionUpdateEvent $event): void
    {
        $fundReturn = $event->getFundReturn();
        $section = $event->getSection();
        $fundStatus = $fundReturn->getFundReturnSectionStatusForSection($section);

        if (!$fundStatus) {
            $fundStatus = (new FundReturnSectionStatus())
                ->setStatus(CompletionStatus::NOT_STARTED)
                ->setName($section->name);

            $fundReturn->addSectionStatus($fundStatus);
            $this->entityManager->persist($fundStatus);
        }

        $mode = $event->getMode();
        if ($mode === ReturnBaseType::MARK_AS_COMPLETED) {
            $fundStatus->setStatus(CompletionStatus::COMPLETED);
        } else if (
            $mode === ReturnBaseType::MARK_AS_IN_PROGRESS ||
            $fundStatus->getStatus() !== CompletionStatus::COMPLETED
        ) {
            $fundStatus->setStatus(CompletionStatus::IN_PROGRESS);
        }
    }
}
