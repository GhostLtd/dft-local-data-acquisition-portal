<?php

namespace App\Repository\ProjectFund;

use App\Entity\Enum\Fund;
use App\Entity\ProjectFund\BsipProjectFund;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\Recipient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<ProjectFund>
 */
class ProjectFundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectFund::class);
    }

    /**
     * @return class-string<ProjectFund>
     */
    public function getProjectFundClassForFund(?Fund $fund=null): string
    {
        return match($fund) {
            null => ProjectFund::class,
            Fund::BSIP => BsipProjectFund::class,
            Fund::CRSTS => CrstsProjectFund::class,
        };
    }

    public function getProjectFundsForRecipient(Recipient $recipient, Fund $fund=null): array
    {
        $projectFundClass = $this->getProjectFundClassForFund($fund);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('projectFund, project')
            ->from($projectFundClass, 'projectFund')
            ->join('projectFund.project', 'project')
            ->join('project.owner', 'recipient')
            ->where('recipient.id = :recipient')
            ->orderBy('project.name', 'ASC')
            ->getQuery()
            ->setParameter('recipient', $recipient->getId()->toRfc4122())
            ->getResult();
    }

    public function findForDashboard(string $id): ?ProjectFund
    {
        return $this->createQueryBuilder('projectFund')
            ->select('projectFund, project')
            ->join('projectFund.project', 'project')
            ->where('projectFund.id = :id')
            ->getQuery()
            ->setParameter('id', (new Ulid($id))->toRfc4122())
            ->getOneOrNullResult();
    }
}
