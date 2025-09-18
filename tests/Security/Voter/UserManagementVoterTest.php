<?php

namespace App\Tests\Security\Voter;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\User;
use App\Security\Voter\External\UserManagementVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserManagementVoterTest extends TestCase
{
    private UserManagementVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->voter = new UserManagementVoter();
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributeAndSubject(): void
    {
        $authority = new Authority();
        $user = $this->createUserWithAdminAuthorities([$authority]);

        $this->token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $authority = new Authority();

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notAuthority = new \stdClass();

        $result = $this->voter->vote($this->token, $notAuthority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDeniesAccessWhenTokenUserIsNotUserInstance(): void
    {
        $authority = new Authority();
        $notUserObject = $this->createMock(UserInterface::class);

        $this->token
            ->method('getUser')
            ->willReturn($notUserObject);

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessWhenTokenUserIsNull(): void
    {
        $authority = new Authority();

        $this->token
            ->method('getUser')
            ->willReturn(null);

        $result = $this->voter->vote($this->token, $authority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }


    public function testDeniesAccessWhenUserHasNoAdminAuthorities(): void
    {
        $targetAuthority = new Authority();
        $user = $this->createUserWithAdminAuthorities([]);

        $this->token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($this->token, $targetAuthority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * @dataProvider authorityMatchingDataProvider
     */
    public function testAuthorityMatching(bool $userIsAdminOfTarget, int $expectedResult): void
    {
        $targetAuthority = new Authority();
        $adminAuthorities = $userIsAdminOfTarget ? [$targetAuthority] : [new Authority()];
        $user = $this->createUserWithAdminAuthorities($adminAuthorities);

        $this->token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($this->token, $targetAuthority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals($expectedResult, $result);
    }

    public function authorityMatchingDataProvider(): array
    {
        return [
            'User is admin of target authority - grant' => [true, VoterInterface::ACCESS_GRANTED],
            'User is not admin of target authority - deny' => [false, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testHandlesMultipleAdminAuthorities(): void
    {
        $targetAuthority = new Authority();
        $authority1 = new Authority();
        $authority2 = new Authority();
        $authority3 = new Authority();

        // User is admin of multiple authorities, including the target
        $user = $this->createUserWithAdminAuthorities([$authority1, $targetAuthority, $authority2, $authority3]);

        $this->token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($this->token, $targetAuthority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOnlyCallsGetUserForSupportedCombinations(): void
    {
        $authority = new Authority();

        // Should not call getUser for unsupported combinations
        $this->token
            ->expects($this->never())
            ->method('getUser');

        // Wrong attribute
        $result1 = $this->voter->vote($this->token, $authority, [Role::CAN_VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result1);

        // Wrong subject
        $result2 = $this->voter->vote($this->token, new \stdClass(), [Role::CAN_MANAGE_USERS]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result2);
    }

    public function testUsesIdenticalAuthorityComparison(): void
    {
        // Test that the voter uses identity comparison (===) not equality (==)
        $targetAuthority = new Authority();

        // Create a different authority instance (would be equal but not identical)
        $differentAuthorityInstance = new Authority();

        $user = $this->createUserWithAdminAuthorities([$differentAuthorityInstance]);

        $this->token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($this->token, $targetAuthority, [Role::CAN_MANAGE_USERS]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * @param Authority[] $adminAuthorities
     */
    private function createUserWithAdminAuthorities(array $adminAuthorities): User
    {
        $user = new User();

        // Use reflection to set the authoritiesAdminOf collection since it might be private
        $reflection = new \ReflectionClass($user);

        try {
            $property = $reflection->getProperty('authoritiesAdminOf');
            $property->setValue($user, new ArrayCollection($adminAuthorities));
        } catch (\ReflectionException $e) {
            // If the property doesn't exist or can't be accessed, try using a setter
            if (method_exists($user, 'setAuthoritiesAdminOf')) {
                $user->setAuthoritiesAdminOf(new ArrayCollection($adminAuthorities));
            } else {
                // As a fallback, try adding each authority individually
                foreach ($adminAuthorities as $authority) {
                    if (method_exists($user, 'addAuthorityAdminOf')) {
                        $user->addAuthorityAdminOf($authority);
                    }
                }
            }
        }

        return $user;
    }
}