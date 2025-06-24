<?php

namespace App\Form\Type;

use App\Entity\Authority;
use App\Entity\Enum\Permission;
use App\Entity\PermissionsView;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Utility\SimplifiedPermissionsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UserDataMapper implements DataMapperInterface
{
    protected Authority $authority;

    public function __construct(
        protected EntityManagerInterface      $entityManager,
        protected SimplifiedPermissionsHelper $userPermissionHelper,
    ) {}

    public function setAuthority(Authority $authority): static
    {
        $this->authority = $authority;
        return $this;
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof User) {
            throw new UnexpectedTypeException($viewData, User::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $forms['name']->setData($viewData->getName());
        $forms['email']->setData($viewData->getEmail());
        $forms['position']->setData($viewData->getPosition());
        $forms['phone']->setData($viewData->getPhone());

        if (isset($forms['permissions'])) {
            $forms['permissions']->setData(array_map(
                fn(PermissionsView $p) => $p->getPermission(),
                $this->userPermissionHelper->getPermissionViews($viewData, $this->authority)
            ));
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof User) {
            throw new UnexpectedTypeException($viewData, User::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $viewData->setName($forms['name']->getData());
        $viewData->setEmail($forms['email']->getData());
        $viewData->setPosition($forms['position']->getData());
        $viewData->setPhone($forms['phone']->getData());

        if (isset($forms['permissions'])) {
            $existingPermissionViews = $this->userPermissionHelper->getPermissionViews($viewData, $this->authority);
            $existingUserPermissions = array_map(
                fn(PermissionsView $p) => $this->entityManager->find(UserPermission::class, $p->getId()),
                $existingPermissionViews
            );
            $viewIsSelected = false;
            $selectedUserPermissions = array_map(
                function(Permission $p) use (&$viewIsSelected) {
                    $viewIsSelected = $viewIsSelected | $p === Permission::VIEWER;
                    return (new UserPermission())
                        ->setPermission($p)
                        ->setEntityClass(Authority::class)
                        ->setEntityId($this->authority->getId());
                },
                $forms['permissions']->getData()
            );
            if (!$viewIsSelected) {
                $selectedUserPermissions[] = (new UserPermission())
                    ->setPermission(Permission::VIEWER)
                    ->setEntityClass(Authority::class)
                    ->setEntityId($this->authority->getId());
            }

            $compare = fn(UserPermission $a, UserPermission $b) => $a->getPermission()->value <=> $b->getPermission()->value;
            $additions = array_udiff($selectedUserPermissions, $existingUserPermissions, $compare);
            $deletions = array_udiff($existingUserPermissions, $selectedUserPermissions, $compare);

            foreach ($additions as $addition) {
                $this->entityManager->persist($addition);
                $viewData->addPermission($addition);
            }
            foreach ($deletions as $deletion) {
                $this->entityManager->remove($deletion);
                $viewData->removePermission($deletion);
            }

        }
    }
}
