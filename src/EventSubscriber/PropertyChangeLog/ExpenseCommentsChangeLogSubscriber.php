<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpenseCommentsChangeLogSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function changeSetRetrievedEvent(ChangeSetRetrievedEvent $event): void
    {
        $entity = $event->getSourceEntity();

        if (
            !$entity instanceof CrstsFundReturn &&
            !$entity instanceof CrstsSchemeReturn
        ) {
            return;
        }

        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        // The comments come out like so:
        //     'expenseDivisionComments' => [
        //       0 => [],
        //       1 => ['2022-23' => 'Comment 1', '2023-24' => 'Comment 2']
        //     ]
        //
        // This restructures them to be:
        //     'comments.2022-23' => [0 => null, 1 => 'Comment 1'],
        //     'comments.2023-24' => [0 => null, 1 => 'Comment 2'],
        $comments = $changes['expenseDivisionComments'] ?? null;

        if ($comments) {
            unset($changes['expenseDivisionComments']);

            $fields = array_merge(
                array_keys($comments[0] ?? []),
                array_keys($comments[1] ?? []),
            );

            foreach($fields as $fieldName) {
                $old = $comments[0][$fieldName] ?? null;
                $new = $comments[1][$fieldName] ?? null;

                // Only include if the comments have actually changed
                // (The comments as a batch may have changed, but not every individual comment will have)
                if ($old !== $new) {
                    $changes["comments.{$fieldName}"] = [$old, $new];
                }
            }
        }

        $changeSet->setChanges($changes);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeSetRetrievedEvent::class => ['changeSetRetrievedEvent', 0],
        ];
    }
}
