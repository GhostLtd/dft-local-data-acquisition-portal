<?php

namespace App\ListPage;

use App\Entity\Authority;
use App\Entity\PermissionsView;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class UserListPage extends AbstractListPage
{
    protected Authority $authority;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory,
        RouterInterface $router
    )
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
            (new Simple('Name', 'user.name'))->sortable(),
            (new Simple('Email', 'user.email'))->sortable(),
            (new Simple('Last login', 'user.lastLogin'))->sortable(),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        /** @var UserRepository $repo */
        $repo = $this->entityManager->getRepository(User::class);
        return $repo->getAllForAuthorityQueryBuilder($this->authority);
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Name') => 'ASC',
        ];
    }
}
