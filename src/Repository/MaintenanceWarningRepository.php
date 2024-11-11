<?php

namespace App\Repository;

use App\Entity\MaintenanceWarning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ServiceEntityRepository<MaintenanceWarning>
 */
class MaintenanceWarningRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry               $registry,
        protected TranslatorInterface $translator,
    )
    {
        parent::__construct($registry, MaintenanceWarning::class);
    }

    public function getNotificationBanner($warningPeriodDateModifier = '+1 week'): ?NotificationBanner
    {
        /** @var MaintenanceWarning $warning */
        $warning = $this->createQueryBuilder('maintenanceWarning')
            ->andWhere('maintenanceWarning.startDatetime >= :now')
            ->andWhere('maintenanceWarning.startDatetime < :future')
            ->orderBy('maintenanceWarning.startDatetime', 'DESC')
            ->setMaxResults(1)
            ->setParameters(new ArrayCollection([
                new Parameter('now', (new \DateTime())->modify('-15 minutes')),
                new Parameter('future', (new \DateTime())->modify($warningPeriodDateModifier))
            ]))
            ->getQuery()
            ->getOneOrNullResult();

        if ($warning) {
            $timeFormat = $this->translator->trans('format.time.default');
            $dateFormat = $this->translator->trans('format.date.full-with-year');

            return new NotificationBanner(
                $this->translator->trans('maintenance-warning.banner.title'),
                $this->translator->trans('maintenance-warning.banner.heading'),
                $this->translator->trans('maintenance-warning.banner.content', [
                    'startTime' => $warning->getStartDatetime()->format($timeFormat),
                    'endTime' => $warning->getEndTime()->format($timeFormat),
                    'date' => $warning->getStartDatetime()->format($dateFormat),
                ])
            );
        }

        return null;
    }
}
