<?php

namespace App\Repository\ProjectFund;

use App\Entity\Enum\Fund;
use App\Entity\ProjectFund\BsipProjectFund;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\Authority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
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
            Fund::CRSTS1 => CrstsProjectFund::class,
            Fund::CRSTS2 => throw new \RuntimeException('Not yet supported'),
        };
    }

    public function getProjectFundsForAuthority(Authority $authority, Fund $fund=null): array
    {
        $projectFundClass = $this->getProjectFundClassForFund($fund);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('projectFund, project')
            ->from($projectFundClass, 'projectFund')
            ->join('projectFund.project', 'project')
            ->join('project.owner', 'authority')
            ->where('authority.id = :authority_id')
            ->orderBy('project.name', 'ASC')
            ->getQuery()
            ->setParameter('authority_id', $authority->getId(), UlidType::NAME)
            ->getResult();
    }

    public function findForDashboard(string $id): ?ProjectFund
    {
        return $this->createQueryBuilder('projectFund')
            ->select('projectFund, project')
            ->join('projectFund.project', 'project')
            ->where('projectFund.id = :id')
            ->getQuery()
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getOneOrNullResult();
    }
}
