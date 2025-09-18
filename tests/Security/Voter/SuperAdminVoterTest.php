<?php

namespace App\Tests\Security\Voter;

use App\Entity\UserTypeRoles;
use App\Security\Voter\Admin\SuperAdminVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SuperAdminVoterTest extends TestCase
{
    /** @var AuthorizationCheckerInterface&MockObject */
    private AuthorizationCheckerInterface $authorizationChecker;
    private SuperAdminVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->voter = new SuperAdminVoter($this->authorizationChecker);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttribute(): void
    {
        $subject = new \stdClass();

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with(UserTypeRoles::ROLE_IAP_ADMIN)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $subject, ['DFT_SUPER_ADMIN']);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $subject = new \stdClass();

        $result = $this->voter->vote($this->token, $subject, ['WRONG_ATTRIBUTE']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }


    public function testAcceptsAnySubject(): void
    {
        // Test with various subject types to ensure it doesn't care about the subject
        $subjects = [
            new \stdClass(),
            'string_subject',
            123,
            null,
            [],
        ];

        $this->authorizationChecker
            ->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        foreach ($subjects as $subject) {
            $result = $this->voter->vote($this->token, $subject, ['DFT_SUPER_ADMIN']);
            $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result,
                'Should accept any subject type');
        }
    }

    /**
     * @dataProvider adminAccessDataProvider
     */
    public function testAdminAccessControl(bool $isAdmin, int $expectedResult): void
    {
        $subject = new \stdClass();

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with(UserTypeRoles::ROLE_IAP_ADMIN)
            ->willReturn($isAdmin);

        $result = $this->voter->vote($this->token, $subject, ['DFT_SUPER_ADMIN']);

        $this->assertEquals($expectedResult, $result);
    }

    public function adminAccessDataProvider(): array
    {
        return [
            'Is admin - grant' => [true, VoterInterface::ACCESS_GRANTED],
            'Not admin - deny' => [false, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testUsesCorrectAdminRole(): void
    {
        $subject = new \stdClass();

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->identicalTo(UserTypeRoles::ROLE_IAP_ADMIN))
            ->willReturn(true);

        $this->voter->vote($this->token, $subject, ['DFT_SUPER_ADMIN']);
    }

    public function testOnlyCallsAuthorizationCheckerForSupportedAttribute(): void
    {
        $subject = new \stdClass();

        // Should not call isGranted for unsupported attributes
        $this->authorizationChecker
            ->expects($this->never())
            ->method('isGranted');

        $result = $this->voter->vote($this->token, $subject, ['UNSUPPORTED_ATTRIBUTE']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAlwaysGrantsForAdminsRegardlessOfSubject(): void
    {
        // Test that admin access is always granted, no matter what the subject is
        $subject = new \stdClass();

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $subject, ['DFT_SUPER_ADMIN']);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testSimpleLogicNoComplexConditions(): void
    {
        // Test that the voter only checks admin role, no other complex conditions
        $subject = new \stdClass();

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with(UserTypeRoles::ROLE_IAP_ADMIN)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $subject, ['DFT_SUPER_ADMIN']);

        // Should be granted solely based on admin role
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAttributeStringMatch(): void
    {
        // Test exact string matching for the attribute
        $subject = new \stdClass();

        $this->authorizationChecker
            ->method('isGranted')
            ->willReturn(true);

        // Exact match
        $result1 = $this->voter->vote($this->token, $subject, ['DFT_SUPER_ADMIN']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result1);

        // Case sensitive - should not match
        $result2 = $this->voter->vote($this->token, $subject, ['dft_super_admin']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result2);

        // Partial match - should not match
        $result3 = $this->voter->vote($this->token, $subject, ['SUPER_ADMIN']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result3);
    }
}