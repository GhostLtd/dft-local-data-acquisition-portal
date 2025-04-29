<?php

namespace App\ListPage;

use App\Entity\Authority;
use App\Repository\SchemeRepository;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SchemeManageListPage extends AbstractListPage
{
    protected Authority $authority;

    public function __construct(protected SchemeRepository $schemeRepository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    public function setAuthority(Authority $authority): static
    {
        $this->authority = $authority;
        return $this;
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Name', 'scheme.name'))->sortable(),
            (new TextFilter('Funds', 'scheme.funds'))->sortable(),
            (new TextFilter('Identifier', 'scheme.schemeIdentifier'))->sortable(),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->schemeRepository->getQueryBuilderForSchemesForAuthority($this->authority, noOrder: true);
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Name') => 'ASC',
        ];
    }
}
