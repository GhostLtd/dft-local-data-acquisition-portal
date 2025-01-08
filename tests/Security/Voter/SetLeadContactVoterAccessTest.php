<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\User;
use App\Tests\AbstractFunctionalTest;
use App\Tests\DataFixtures\Security\Voter\PermissionDataFixture;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SetLeadContactVoterAccessTest extends AbstractFunctionalTest
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected AbstractDatabaseTool $databaseTool;
    protected ReferenceRepository $referenceRepository;

    protected TokenStorageInterface $tokenStorage;

    public function setUp(): void
    {
        parent::setUp();
        $this->authorizationChecker = $this->getFromContainer(AuthorizationCheckerInterface::class, AuthorizationCheckerInterface::class);
        $this->databaseTool = $this->getFromContainer(DatabaseToolCollection::class, DatabaseToolCollection::class)->get();
        $this->tokenStorage = $this->getFromContainer('security.token_storage', TokenStorageInterface::class);

        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([PermissionDataFixture::class])
            ->getReferenceRepository();
    }

    public function dataAccess(): array {
        return [
            ['admin:1', FundAward::class, 'recipient:1/fund-award:1', true],
            ['admin:1', FundAward::class, 'recipient:2/fund-award:1', true],
            ['admin:2', FundAward::class, 'recipient:3/fund-award:1', true],
            ['admin:1', FundAward::class, 'recipient:3/fund-award:1', false],
            ['admin:2', FundAward::class, 'recipient:1/fund-award:1', false],

            ['admin:1', CrstsFundReturn::class, 'recipient:1/return:1', true],
            ['admin:1', CrstsFundReturn::class, 'recipient:1/return:2', true],
            ['admin:1', CrstsFundReturn::class, 'recipient:2/return:1', true],
            ['admin:2', CrstsFundReturn::class, 'recipient:3/return:1', true],
            ['admin:1', CrstsFundReturn::class, 'recipient:3/return:1', false],
            ['admin:2', CrstsFundReturn::class, 'recipient:1/return:1', false],
        ];
    }

    /**
     * @dataProvider dataAccess
     */
    public function testAccess(string $userRef, string $subjectClass, string $subjectRef, bool $expectedResult): void
    {
        $user = $this->referenceRepository->getReference($userRef, User::class);
        $subject = $this->referenceRepository->getReference($subjectRef, $subjectClass);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->setToken($token);

        $actualResult = $this->authorizationChecker->isGranted(Role::CAN_SET_LEAD_CONTACT, $subject);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
