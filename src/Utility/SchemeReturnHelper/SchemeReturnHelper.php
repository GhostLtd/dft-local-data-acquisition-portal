<?php

namespace App\Utility\SchemeReturnHelper;

use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\UnitOfWork;

class SchemeReturnHelper
{
    protected EntityManagerInterface $entityManager;
    protected ClassMetadataFactory $metadataFactory;
    protected UnitOfWork $unitOfWork;

    /** N.B. Don't pass via constructor - the SchemeFundChangeListener will pass this, as
     *       we specifically want the entityManager / UOW that is being flushed
     */
    public function setEntityManager(EntityManagerInterface $entityManager): SchemeReturnHelper
    {
        $this->entityManager = $entityManager;
        $this->metadataFactory = $this->entityManager->getMetadataFactory();
        $this->unitOfWork = $this->entityManager->getUnitOfWork();

        return $this;
    }

    public function schemeAddedToFunds(Scheme $scheme, array $funds): void
    {
        array_walk($funds, fn($fund) => $this->schemeAddedToFund($scheme, $fund));
    }

    public function schemeRemovedFromFunds(Scheme $scheme, array $funds): void
    {
        array_walk($funds, fn($fund) => $this->schemeRemoveFromFund($scheme, $fund));
    }

    public function schemeAddedToFund(Scheme $scheme, Fund $fund): void
    {
        match($fund) {
            Fund::CRSTS1 => $this->schemeAddedToCrsts1($scheme),
            default => throw new \RuntimeException('Unsupported fund'),
        };
    }

    public function schemeRemoveFromFund(Scheme $scheme, Fund $fund): void
    {
        match($fund) {
            Fund::CRSTS1 => $this->schemeRemovedFromCrsts1($scheme),
            default => throw new \RuntimeException('Unsupported fund'),
        };
    }

    protected function schemeAddedToCrsts1(Scheme $scheme): void
    {
        $openReturns = $this->getUnsubmittedReturnsForSchemeAndFund($scheme, Fund::CRSTS1);

        foreach($openReturns as $fundReturn) {
            $schemeReturn = (new CrstsSchemeReturn())
                ->setScheme($scheme);

            $fundReturn->addSchemeReturn($schemeReturn);

            $this->persist($schemeReturn);
            $this->recalculate($fundReturn);
        }
    }

    protected function schemeRemovedFromCrsts1(Scheme $scheme): void
    {
        $openReturns = $this->getUnsubmittedReturnsForSchemeAndFund($scheme, Fund::CRSTS1);

        foreach($openReturns as $fundReturn) {
            foreach($fundReturn->getSchemeReturns() as $schemeReturn) {
                if ($schemeReturn->getScheme() === $scheme) {
                    $fundReturn->removeSchemeReturn($schemeReturn);
                    $this->entityManager->remove($schemeReturn);
                }
            }
        }
    }

    /**
     * @return array<int, FundReturn>
     */
    public function getUnsubmittedReturnsForSchemeAndFund(Scheme $scheme, Fund $fund): array
    {
        $returns = $this->getReturnsForSchemeAndFund($scheme, $fund);
        return array_filter($returns, fn(FundReturn $r) => !$r->isSignedOff());
    }

    /**
     * @return array<int, FundReturn>
     */
    public function getReturnsForSchemeAndFund(Scheme $scheme, Fund $fund): array
    {
        $fundAward = $this->getFundAwardForSchemeAndFund($scheme, $fund);

        if (!$fundAward) {
            return [];
        }

        return $fundAward->getReturns()->toArray();
    }

    public function getFundAwardForSchemeAndFund(Scheme $scheme, Fund $fund): ?FundAward
    {
        $authority = $scheme->getAuthority();
        foreach($authority->getFundAwards() as $fundAward) {
            if ($fundAward->getType() === $fund) {
                return $fundAward;
            }
        }

        return null;
    }

    protected function recalculate(mixed $entity): void
    {
        $metadata = $this->metadataFactory->getMetadataFor($entity::class);
        $this->unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
    }

    protected function persist(mixed $entity): void
    {
        $metadata = $this->metadataFactory->getMetadataFor($entity::class);

        $this->entityManager->persist($entity);
        $this->unitOfWork->computeChangeSet($metadata, $entity);
    }
}
