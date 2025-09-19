<?php

namespace App\ListPage;

use App\Entity\MaintenanceWarning;
use App\Repository\MaintenanceWarningRepository;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class MaintenanceWarningListPage extends AbstractListPage
{
    public function __construct(
        private readonly MaintenanceWarningRepository $repository,
        FormFactoryInterface $formFactory,
        RouterInterface $router
    ) {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        return [
            (new Simple('Date', 'maintenance_warning.startDatetime'))->sortable(),
            (new Simple('Start', 'maintenance_warning.startDatetime')),
            (new Simple('End', 'maintenance_warning.endTime')),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('maintenance_warning');
        return $queryBuilder
            ->select('maintenance_warning')
            ->andWhere('maintenance_warning.startDatetime >= :now')
            ->setParameter('now', new \DateTime('-2 hours'));
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Date') => 'ASC',
        ];
    }
}
