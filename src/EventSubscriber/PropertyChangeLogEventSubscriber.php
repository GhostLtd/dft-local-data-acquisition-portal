<?php

namespace App\EventSubscriber;

use App\Entity\Enum\ChangeLogAction;
use App\Entity\PropertyChangeLog;
use App\Entity\PropertyChangeLoggableInterface;
use App\Utility\PropertyChangeLog\Events\ChangeLogEntityCreatedEvent;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use App\Utility\PropertyChangeLog\PropertyChangeSet;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::onFlush)]
class PropertyChangeLogEventSubscriber
{
    protected ?string $defaultSource;

    public function __construct(
        protected Security                 $security,
        protected EntityManagerInterface   $defaultEntityManager,
        protected EventDispatcherInterface $eventDispatcher,
    ) {
        $this->defaultSource = null;
    }

    public function setDefaultSource(?string $defaultSource): static
    {
        $this->defaultSource = $defaultSource;
        return $this;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $token = $this->security->getToken();

        $user = $token?->getUser();
        $userEmail = $user instanceof UserInterface ? $user->getUserIdentifier() : null;

        // Doing it like this so there is no crash if I've failed to enumerate all possible token types
        $firewallName = (
            $token instanceof PostAuthenticationToken ||
            $token instanceof RememberMeToken
        ) ?
            $token->getFirewallName() :
            null;

        $eventEntityManager = $eventArgs->getObjectManager();
        $unitOfWork = $eventEntityManager->getUnitOfWork();

        $changeLogMetadata = $this->defaultEntityManager->getClassMetadata(PropertyChangeLog::class);

        $logChanges = function (ChangeLogAction $action, mixed $entity) use ($changeLogMetadata, $firewallName, $unitOfWork, $userEmail) {
            if (!$entity instanceof PropertyChangeLoggableInterface) {
                return;
            }

            [$changeLogs, $fieldsChanged] = $this->logChanges($action, $unitOfWork, $userEmail, $firewallName, $entity);
            foreach($changeLogs as $changeLog) {
                $this->defaultEntityManager->persist($changeLog);
                $unitOfWork->computeChangeSet($changeLogMetadata, $changeLog);
            }
        };

        foreach($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $logChanges(ChangeLogAction::INSERT, $entity);
        }

        foreach($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $logChanges(ChangeLogAction::UPDATE, $entity);
        }

        foreach($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $logChanges(ChangeLogAction::DELETE, $entity);
        }
    }

    protected function logChanges(ChangeLogAction $action, UnitOfWork $unitOfWork, ?string $userEmail, ?string $firewallName, object $entity): array
    {
        $entityClass = $entity::class;
        $changeLogTemplate = (new PropertyChangeLog())
            ->setAction($action->value)
            ->setEntityId($entity->getId())
            ->setEntityClass(ClassUtils::getRealClass($entityClass))
            ->setTimestamp(new \DateTime())
            ->setSource($userEmail ?? $this->defaultSource)
            ->setFirewallName($firewallName)
            ->setPropertyName(null)
            ->setPropertyValue(null);

        $changeLogEntities = [];
        $fieldsChanged = [];

        if ($action === ChangeLogAction::DELETE) {
            $this->eventDispatcher->dispatch(new ChangeLogEntityCreatedEvent($changeLogTemplate, $entity, $action));
            $changeLogEntities[] = $changeLogTemplate;
        } else {
            $changeSet = new PropertyChangeSet($unitOfWork->getEntityChangeSet($entity));
            $this->eventDispatcher->dispatch(new ChangeSetRetrievedEvent($changeSet, $entity));

            foreach($changeSet->getChanges() as $field => [$oldValue, $newValue]) {
                $fieldsChanged[] = $field;

                $changeLogEntity = (clone $changeLogTemplate)
                    ->setPropertyName($field)
                    ->setPropertyValue($newValue);

                $this->eventDispatcher->dispatch(new ChangeLogEntityCreatedEvent($changeLogEntity, $entity, $action, $changeSet));
                $changeLogEntity->validatePropertyValue();

                $changeLogEntities[] = $changeLogEntity;
            }
        }

        return [$changeLogEntities, $fieldsChanged];
    }
}
