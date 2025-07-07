<?php

namespace App\Command\Fix;

use App\Entity\ExpenseEntry;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\EventSubscriber\PropertyChangeLogEventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ldap:fix:20250405-patch-baselines',
    description: 'Propagate baselines fully to the 2024Q4 returns',
)]
class LdapFix20250405PatchBaselinesCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface           $entityManager,
        protected PropertyChangeLogEventSubscriber $changeLogEventSubscriber,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fundReturnRepository = $this->entityManager->getRepository(CrstsFundReturn::class);

        /** @var array<int, CrstsFundReturn> $fundReturns */
        $fundReturns = $fundReturnRepository
            ->createQueryBuilder('r')
            ->select('r, re, fa, a')
            ->join('r.expenses', 're')
            ->join('r.fundAward', 'fa')
            ->join('fa.authority', 'a')
            ->where('r.year = 2024')
            ->andWhere('r.quarter = 4')
            ->getQuery()
            ->execute();

        foreach($fundReturns as $fundReturn) {
            $expenses = $fundReturn->getExpenses();

            /** @var ?CrstsFundReturn $previousReturn */
            $previousReturn = $fundReturnRepository
                ->createQueryBuilder('r')
                ->join('r.expenses', 're')
                ->where('r.year = 2024')
                ->andWhere('r.quarter = 3')
                ->andWhere('IDENTITY(r.fundAward) = :award')
                ->setParameter('award', $fundReturn->getFundAward()->getId(), UlidType::NAME)
                ->getQuery()
                ->getOneOrNullResult();

            if ($previousReturn === null) {
                $io->error('Unable to fetch 2024Q3 return for fund award: ' . $fundReturn->getFundAward()->getId());;
                return Command::FAILURE;
            }

            $previousExpenses = $previousReturn->getExpenses();

            $getInfoString = fn(FundReturn $f, ExpenseEntry $e) =>
              "{$f->getFundAward()->getAuthority()->getName()} Return {$f->getYear()}Q{$f->getQuarter()} <comment>{$e->getDivision()} {$e->getColumn()} {$e->getType()->name}</comment>";


            foreach($previousExpenses as $previousExpense) {
                if ($previousExpense->getType()->isBaseline()) {
                    $existingExpense = $fundReturn->getExpenseWithSameDivisionTypeAndColumnAs($previousExpense);
                    if ($existingExpense) {
                        if ($existingExpense->getValue() !== null) {
                            $io->writeln("<info>EXISTS</info> {$getInfoString($fundReturn, $previousExpense)}");
                            continue;
                        }

                        $newExpense = $existingExpense;
                    } else {
                        $newExpense = (new ExpenseEntry())
                            ->setDivision($previousExpense->getDivision())
                            ->setColumn($previousExpense->getColumn())
                            ->setType($previousExpense->getType())
                            ->setForecast($previousExpense->isForecast());

                        $this->entityManager->persist($newExpense);
                    }

                    $newExpense->setValue($previousExpense->getValue());
                    $expenses->add($newExpense);
                    $io->writeln("<error>ADDING</error> {$getInfoString($fundReturn, $previousExpense)}");
                }
            }
        }

        $this->changeLogEventSubscriber->setDefaultSource('fix:2025-04-05:patch-baselines');
        $this->entityManager->flush();
        $this->changeLogEventSubscriber->setDefaultSource(null);

        return Command::SUCCESS;
    }
}
