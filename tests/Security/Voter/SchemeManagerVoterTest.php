<?php

namespace App\Tests\Security\Voter;

use App\Entity\Authority;
use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\User;
use App\Security\Voter\External\SchemeManagerVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SchemeManagerVoterTest extends TestCase
{
    /** @var AccessDecisionManagerInterface&MockObject */
    private AccessDecisionManagerInterface $accessDecisionManager;
    private SchemeManagerVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new SchemeManagerVoter($this->accessDecisionManager);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCanManageSchemesWithFundReturn(): void
    {
        $authority = new Authority();
        $fundReturn = $this->createFundReturnWithAuthority($authority);
        $user = $this->createUserAsAdminOf($authority);

        $this->setupMocksForAdminUser($user);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_MANAGE_SCHEMES]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testSupportsCanManageSchemesWithAuthority(): void
    {
        $authority = new Authority();
        $user = $this->createUserAsAdminOf($authority);

        $this->setupMocksForAdminUser($user);

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_SCHEMES]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testSupportsSchemeRelatedAttributes(): void
    {
        $authority = new Authority();
        $scheme = $this->createSchemeWithAuthority($authority);
        $user = $this->createUserAsAdminOf($authority);

        $schemeAttributes = [
            Role::CAN_DELETE_SCHEME,
            Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS,
            Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS,
            Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME,
        ];

        foreach ($schemeAttributes as $attribute) {
            $this->accessDecisionManager
                ->expects($this->any())
                ->method('decide')
                ->willReturn(false);

            $this->token
                ->expects($this->any())
                ->method('getUser')
                ->willReturn($user);

            $result = $this->voter->vote($this->token, $scheme, [$attribute]);
            $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result,
                "Should support attribute: {$attribute}");
        }
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $authority = new Authority();

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubjectForCanManageSchemes(): void
    {
        $notValidSubject = new \stdClass();

        $result = $this->voter->vote($this->token, $notValidSubject, [Role::CAN_MANAGE_SCHEMES]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubjectForSchemeAttributes(): void
    {
        $notScheme = new \stdClass();

        $result = $this->voter->vote($this->token, $notScheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }


    public function testDeniesAccessWhenTokenUserIsNotUserInstance(): void
    {
        $authority = new Authority();
        $notUser = $this->createMock(UserInterface::class);

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($notUser);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_SCHEMES]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testResolvesAuthorityFromScheme(): void
    {
        $authority = new Authority();
        $scheme = $this->createSchemeWithAuthority($authority);
        $user = $this->createUserAsAdminOf($authority);

        $this->setupMocksForAdminUser($user);

        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testResolvesAuthorityFromFundReturn(): void
    {
        $authority = new Authority();
        $fundReturn = $this->createFundReturnWithAuthority($authority);
        $user = $this->createUserAsAdminOf($authority);

        $this->setupMocksForAdminUser($user);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_MANAGE_SCHEMES]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * @dataProvider accessControlDataProvider
     */
    public function testAccessControl(bool $accessDecisionResult, bool $userIsAdmin, int $expectedResult): void
    {
        $authority = new Authority();
        $user = $userIsAdmin ? $this->createUserAsAdminOf($authority) : new User();

        $this->accessDecisionManager
            ->method('decide')
            ->willReturn($accessDecisionResult);

        $this->token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_SCHEMES]);

        $this->assertEquals($expectedResult, $result);
    }

    public function accessControlDataProvider(): array
    {
        return [
            'AccessDecision grants - grant' => [true, false, VoterInterface::ACCESS_GRANTED],
            'User is admin - grant' => [false, true, VoterInterface::ACCESS_GRANTED],
            'Both conditions - grant' => [true, true, VoterInterface::ACCESS_GRANTED],
            'Neither condition - deny' => [false, false, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testUsesCorrectInternalRole(): void
    {
        $authority = new Authority();
        $user = new User();

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with(
                $this->identicalTo($this->token),
                $this->identicalTo([InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION]),
                $this->identicalTo($authority)
            )
            ->willReturn(false);

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_SCHEMES]);
    }

    public function testHandlesBothCanManageSchemesSubjectTypes(): void
    {
        $authority = new Authority();
        $user = $this->createUserAsAdminOf($authority);

        // Test with Authority subject
        $this->accessDecisionManager
            ->expects($this->any())
            ->method('decide')
            ->willReturn(false);
        $this->token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $result1 = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_SCHEMES]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result1);

        // Test with FundReturn subject
        $fundReturn = $this->createFundReturnWithAuthority($authority);
        $result2 = $this->voter->vote($this->token, $fundReturn, [Role::CAN_MANAGE_SCHEMES]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result2);
    }

    public function testAccessDecisionManagerTakesPrecedence(): void
    {
        $authority = new Authority();
        $differentAuthority = new Authority();
        $user = $this->createUserAsAdminOf($differentAuthority); // Not admin of target authority

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(true); // AccessDecisionManager grants

        // getUser should not be called when AccessDecisionManager grants access
        $this->token
            ->expects($this->never())
            ->method('getUser');

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_SCHEMES]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function setupMocksForAdminUser(User $user): void
    {
        $this->accessDecisionManager
            ->method('decide')
            ->willReturn(false);

        $this->token
            ->method('getUser')
            ->willReturn($user);
    }

    private function createUserAsAdminOf(Authority $authority): User
    {
        $user = new User();
        $authority->setAdmin($user);
        return $user;
    }

    private function createSchemeWithAuthority(Authority $authority): Scheme
    {
        $scheme = new Scheme();
        $scheme->setAuthority($authority);
        return $scheme;
    }

    private function createFundReturnWithAuthority(Authority $authority): CrstsFundReturn
    {
        $fundAward = new FundAward();
        $fundAward->setAuthority($authority);

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setFundAward($fundAward);

        return $fundReturn;
    }
}
