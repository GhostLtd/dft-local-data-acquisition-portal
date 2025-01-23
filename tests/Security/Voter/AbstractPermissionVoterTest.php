<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Permission;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Authority;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Tests\AbstractFunctionalTest;
use App\Tests\DataFixtures\Security\Voter\PermissionDataFixture;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class AbstractPermissionVoterTest extends AbstractFunctionalTest
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected AbstractDatabaseTool $databaseTool;

    protected EntityManagerInterface $entityManager;
    protected ReferenceRepository $referenceRepository;

    protected TokenStorageInterface $tokenStorage;

    public function setUp(): void
    {
        parent::setUp();
        $this->authorizationChecker = $this->getFromContainer(AuthorizationCheckerInterface::class, AuthorizationCheckerInterface::class);
        $this->databaseTool = $this->getFromContainer(DatabaseToolCollection::class, DatabaseToolCollection::class)->get();
        $this->tokenStorage = $this->getFromContainer('security.token_storage', TokenStorageInterface::class);
        $this->entityManager = $this->getFromContainer(EntityManagerInterface::class, EntityManagerInterface::class);

        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([PermissionDataFixture::class])
            ->getReferenceRepository();
    }

    protected function createPermissionAndPerformTest(
        string      $attribute,
        ?Permission $permission,
        ?string     $permissionEntityReferenceClass,
        ?string     $permissionEntityClass,
        ?string     $permissionEntityId,
        ?array      $permissionFundTypes,
        ?array      $permissionSectionTypes,

        bool        $expectedResult,
        string      $userRef,
        string      $subjectClass,
        string      $subjectRef,
        ?string $subjectSectionType
    ): void
    {
        if ($permission) {
            $permissionEntityUlid = $this->referenceRepository->getReference($permissionEntityId, $permissionEntityReferenceClass)->getId();

            $userPermission = (new UserPermission())
                ->setUser($this->referenceRepository->getReference($userRef, User::class))
                ->setPermission($permission)
                ->setEntityClass($permissionEntityClass)
                ->setEntityId($permissionEntityUlid)
                ->setFundTypes($permissionFundTypes)
                ->setSectionTypes($permissionSectionTypes);

            $this->entityManager->persist($userPermission);
            $this->entityManager->flush();
        }

        $this->performTest($attribute, $expectedResult, $userRef, $subjectClass, $subjectRef, $subjectSectionType);
    }

    protected function performTest(
        string  $attribute,
        bool    $expectedResult,
        string  $userRef,
        string  $subjectClass,
        string  $subjectRef,
        ?string $subjectSectionType
    ): void
    {
        $user = $this->referenceRepository->getReference($userRef, User::class);
        $subject = $this->referenceRepository->getReference($subjectRef, $subjectClass);

        if ($subjectSectionType) {
            $subject = ['subject' => $subject, 'section' => $subjectSectionType];
        }

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->setToken($token);

        $actualResult = $this->authorizationChecker->isGranted($attribute, $subject);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
