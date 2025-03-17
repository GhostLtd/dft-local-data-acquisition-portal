<?php

namespace App\EventListener;

use App\Entity\Enum\Fund;
use App\Entity\Scheme;
use App\Utility\SchemeReturnHelper\SchemeReturnHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class SchemeFundChangeListener
{
    public function __construct(protected SchemeReturnHelper $schemeReturnHelper)
    {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $this->schemeReturnHelper->setEntityManager($em);

        foreach($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Scheme) {
                $this->schemeReturnHelper->schemeAddedToFunds($entity, $entity->getFunds());
            }
        }

        foreach($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Scheme) {
                $changeSet = $uow->getEntityChangeSet($entity);

                $fundChanges = $changeSet['funds'] ?? null;

                if ($fundChanges !== null) {
                    [$from, $to] = $fundChanges;

                    $valuesToFunds = fn(array $fundValues) => array_map(fn(string $fundValue) => Fund::from($fundValue), $fundValues);

                    $removed = $valuesToFunds(array_diff($from, $to));
                    $added = $valuesToFunds(array_diff($to, $from));

                    $this->schemeReturnHelper->schemeAddedToFunds($entity, $added);
                    $this->schemeReturnHelper->schemeRemovedFromFunds($entity, $removed);
                }
            }
        }

        foreach($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Scheme) {
                $this->schemeReturnHelper->schemeRemovedFromFunds($entity, $entity->getFunds());
            }
        }
    }
}
