<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Permission;
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
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class AbstractPermissionVoterTest extends AbstractFunctionalTest
{
    protected AbstractDatabaseTool $databaseTool;

    protected EntityManagerInterface $entityManager;
    protected ReferenceRepository $referenceRepository;

    protected TokenStorageInterface $tokenStorage;

    public function setUp(): void
    {
        parent::setUp();
        $this->databaseTool = $this->getFromContainer(DatabaseToolCollection::class, DatabaseToolCollection::class)->get();
        $this->tokenStorage = $this->getFromContainer('security.token_storage', TokenStorageInterface::class);
        $this->entityManager = $this->getFromContainer(EntityManagerInterface::class, EntityManagerInterface::class);

        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([PermissionDataFixture::class])
            ->getReferenceRepository();
    }

    /**
     * @param Permission|null $permission
     * @param string|null $permissionEntityId
     * @param string|null $permissionEntityReferenceClass
     * @param string $userRef
     * @param string|null $permissionEntityClass
     * @param array|null $permissionFundTypes
     * @return void
     */
    public function createPermission(?Permission $permission, ?string $permissionEntityId, ?string $permissionEntityReferenceClass, string $userRef, ?string $permissionEntityClass, ?array $permissionFundTypes): void
    {
        if ($permission) {
            $permissionEntityUlid = $this->referenceRepository->getReference($permissionEntityId, $permissionEntityReferenceClass)->getId();

            $userPermission = (new UserPermission())
                ->setUser($this->referenceRepository->getReference($userRef, User::class))
                ->setPermission($permission)
                ->setEntityClass($permissionEntityClass)
                ->setEntityId($permissionEntityUlid)
                ->setFundTypes($permissionFundTypes);

            $this->entityManager->persist($userPermission);
            $this->entityManager->flush();
        }
    }

    protected function createPermissionAndPerformTestOnSpecificVoter(
        VoterInterface $voter,
        string         $attribute,
        ?Permission    $permission,
        ?string        $permissionEntityReferenceClass,
        ?string        $permissionEntityClass,
        ?string        $permissionEntityId,
        ?array         $permissionFundTypes,

        bool           $expectedResult,
        string         $userRef,
        string         $subjectClass,
        string         $subjectRef,
    ): void
    {
        $this->createPermission($permission, $permissionEntityId, $permissionEntityReferenceClass, $userRef, $permissionEntityClass, $permissionFundTypes);
        $this->performTestOnSpecificVoter($voter, $attribute, $expectedResult, $userRef, $subjectClass, $subjectRef);
    }

    protected function performTestOnSpecificVoter(
        VoterInterface $voter,
        string         $attribute,
        bool           $expectedVoterResults,
        string         $userRef,
        string         $subjectClass,
        string         $subjectRef,
    ): void
    {
        $user = $this->referenceRepository->getReference($userRef, User::class);
        $subject = $this->referenceRepository->getReference($subjectRef, $subjectClass);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->setToken($token);

        $actualResult = $voter->vote($token, $subject, [$attribute]);

        $expectedVoterResults = match($expectedVoterResults) {
            false => [VoterInterface::ACCESS_DENIED, VoterInterface::ACCESS_ABSTAIN],
            true => [VoterInterface::ACCESS_GRANTED],
        };

        $this->assertContains($actualResult, $expectedVoterResults);
    }
}
