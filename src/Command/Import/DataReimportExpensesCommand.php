<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Entity\PropertyChangeLog;
use App\EventSubscriber\PropertyChangeLogEventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[AsCommand(name: 'app:import:fix-expenses-reimport', description: 'Import data from Jess` spreadsheets' )]
class DataReimportExpensesCommand extends DataImportCommand
{
    protected const array SHEET_NAMES = [
        // none of the scheme totalCost/agreedFunding values seem to be affected
//        'ReimportExpenses\\CrstsSchemeReturn' => 'CrstsSchemeReturn',
        'ReimportExpenses\\ExpenseEntry' => 'ExpenseEntry',
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        PropertyAccessorInterface $propertyAccessor,
        LoggerInterface $dataImportLogger,
        PropertyChangeLogEventSubscriber $propertyChangeLog
    ) {
        parent::__construct($entityManager, $propertyAccessor, $dataImportLogger);
        $propertyChangeLog->setDefaultSource("fix:negative-expenses");
    }
}
