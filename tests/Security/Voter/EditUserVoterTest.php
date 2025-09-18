<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\User;
use App\Security\Voter\External\EditUserVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EditUserVoterTest extends TestCase
{
    private EditUserVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->voter = new EditUserVoter();
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributeAndSubject(): void
    {
        $targetUser = $this->createUserWithIdentifier('target@example.com');
        $currentUser = $this->createUserWithIdentifier('current@example.com');

        $this->token
            ->method('getUser')
            ->willReturn($currentUser);

        $result = $this->voter->vote($this->token, $targetUser, [Role::CAN_EDIT_USER]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $user = $this->createUserWithIdentifier('user@example.com');

        $result = $this->voter->vote($this->token, $user, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notUser = new \stdClass();

        $result = $this->voter->vote($this->token, $notUser, [Role::CAN_EDIT_USER]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }


    /**
     * @dataProvider userIdentifierDataProvider
     */
    public function testUserIdentifierComparison(string $currentUserIdentifier, string $targetUserIdentifier, int $expectedResult): void
    {
        $targetUser = $this->createUserWithIdentifier($targetUserIdentifier);
        $currentUser = $this->createUserWithIdentifier($currentUserIdentifier);

        $this->token
            ->method('getUser')
            ->willReturn($currentUser);

        $result = $this->voter->vote($this->token, $targetUser, [Role::CAN_EDIT_USER]);

        $this->assertEquals($expectedResult, $result);
    }

    public function userIdentifierDataProvider(): array
    {
        return [
            'Same identifier - deny' => ['user@example.com', 'user@example.com', VoterInterface::ACCESS_DENIED],
            'Different identifier - grant' => ['user1@example.com', 'user2@example.com', VoterInterface::ACCESS_GRANTED],
            'Case sensitive - grant' => ['User@example.com', 'user@example.com', VoterInterface::ACCESS_GRANTED],
            'Empty vs non-empty - grant' => ['', 'user@example.com', VoterInterface::ACCESS_GRANTED],
            'Both empty - deny' => ['', '', VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testUsesUserIdentifierNotObjectIdentity(): void
    {
        // Test that it uses getUserIdentifier(), not object identity
        $userIdentifier = 'same@example.com';

        // Create two different User objects with the same identifier
        $targetUser = $this->createUserWithIdentifier($userIdentifier);
        $currentUser = $this->createUserWithIdentifier($userIdentifier);

        // They are different objects
        $this->assertNotSame($targetUser, $currentUser);

        $this->token
            ->method('getUser')
            ->willReturn($currentUser);

        $result = $this->voter->vote($this->token, $targetUser, [Role::CAN_EDIT_USER]);

        // Should deny because identifiers are the same
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOnlyCallsGetUserForSupportedCombination(): void
    {
        $user = $this->createUserWithIdentifier('user@example.com');

        // Should not call getUser for unsupported combinations
        $this->token
            ->expects($this->never())
            ->method('getUser');

        // Wrong attribute
        $result1 = $this->voter->vote($this->token, $user, [Role::CAN_VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result1);

        // Wrong subject
        $result2 = $this->voter->vote($this->token, new \stdClass(), [Role::CAN_EDIT_USER]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result2);
    }

    public function testAlwaysGrantsAccessForDifferentUsers(): void
    {
        // Test that there's no additional logic beyond the self-edit check
        $targetUser = $this->createUserWithIdentifier('target@example.com');
        $currentUser = $this->createUserWithIdentifier('current@example.com');

        $this->token
            ->method('getUser')
            ->willReturn($currentUser);

        $result = $this->voter->vote($this->token, $targetUser, [Role::CAN_EDIT_USER]);

        // Should always grant for different users, no matter what
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function createUserWithIdentifier(string $identifier): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getUserIdentifier')
            ->willReturn($identifier);

        return $user;
    }
}