<?php

namespace App\Command\Fix;

use App\Entity\Enum\MilestoneType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Milestone;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ldap:fix:20250801-fix-is-development-only-data',
    description: 'Fills in missing is-development-only data',
)]
class LdapFix20250801FixIsDevelopmentOnlyDataCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startYear = 2024;
        $startQuarter = 4;

        /**
         * @var array<int, CrstsSchemeReturn> $schemeReturns
         */
        $schemeReturns = $this->entityManager->createQueryBuilder()
            ->select('csr, m')
            ->from(CrstsSchemeReturn::class, 'csr')
            ->join('csr.fundReturn', 'fr')
            ->join('csr.milestones', 'm')
            ->where('fr.year = :startYear and fr.quarter >= :startQuarter')
            ->orWhere('fr.year > :startYear')
            ->andWhere('csr.developmentOnly IS NULL')
            ->setParameter('startYear', $startYear)
            ->setParameter('startQuarter', $startQuarter)
            ->getQuery()
            ->execute();

        $changedRecords = 0;

        foreach($schemeReturns as $schemeReturn) {
            $milestones = $schemeReturn->getMilestones()->toArray();
            $nonBaselineMilestones = array_filter($milestones, fn(Milestone $m) => !$m->getType()->isBaselineMilestone());

            $has = function(array $milestones, MilestoneType $type) {
                foreach($milestones as $milestone) {
                    if ($milestone->getType() === $type) {
                        return true;
                    }
                }
                return false;
            };

            if (count($nonBaselineMilestones) >= 4) {
                $isDevelopmentOnly = false;
            } elseif (
                $has($nonBaselineMilestones, MilestoneType::START_DEVELOPMENT)
                && $has($nonBaselineMilestones, MilestoneType::END_DEVELOPMENT)
            ) {
                $isDevelopmentOnly = true;
            } else {
                // This case never gets hit
                dump(array_map(fn(Milestone $m) => $m->getType()->name . ' = ' . $m->getDate()?->format('Y-m-d'), $schemeReturn->getMilestones()->toArray()));
                $isDevelopmentOnly = null;
            }

            $changedRecords++;
            $schemeReturn->setDevelopmentOnly($isDevelopmentOnly);
        }

        if ($changedRecords === 0) {
            $io->success("No records to update");
        } else {
            $io->success("Updated {$changedRecords} scheme returns; Saving...");
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
