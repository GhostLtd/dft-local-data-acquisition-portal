<?php

namespace App\Form\Type;

use App\Entity\Authority;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Utility\SimplifiedPermissionsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

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

        if (isset($forms['permission'])) {
            $bestPermissionView = $this->userPermissionHelper->getBestPermissionView($viewData, $this->authority);

            if ($bestPermissionView) {
                $forms['permission']->setData($bestPermissionView->getPermission());
            }
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

        if (isset($forms['permission'])) {
            $bestPermissionView = $this->userPermissionHelper->getBestPermissionView($viewData, $this->authority);

            if ($bestPermissionView) {
                $userPermission = $this->entityManager->find(UserPermission::class, $bestPermissionView->getId());

                if (!$userPermission) {
                    throw new InvalidArgumentException('Unable to find matching permission entity');
                }

                $userPermission->setPermission($forms['permission']->getData());
            } else {
                $userPermission = (new UserPermission())
                    ->setPermission($forms['permission']->getData())
                    ->setEntityClass(Authority::class)
                    ->setEntityId($this->authority->getId());

                $this->entityManager->persist($userPermission);
                $viewData->addPermission($userPermission);
            }
        }
    }
}
