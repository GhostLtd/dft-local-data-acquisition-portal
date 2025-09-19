<?php

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LoginSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriberTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    private LoginSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->subscriber = new LoginSubscriber($this->entityManager);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = LoginSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(LoginSuccessEvent::class, $events);
        $this->assertSame('onLoginSuccess', $events[LoginSuccessEvent::class]);
    }

    /**
     * @dataProvider validLoginProvider
     */
    public function testOnLoginSuccessWithValidUser(TokenInterface $token): void
    {
        $user = $this->createMock(User::class);
        /** @phpstan-ignore-next-line */
        $token->method('getUser')->willReturn($user);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getAuthenticatedToken')->willReturn($token);

        $user->expects($this->once())
            ->method('setLastLogin')
            ->with($this->isInstanceOf(\DateTime::class))
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->subscriber->onLoginSuccess($event);
    }

    public function validLoginProvider(): array
    {
        return [
            'PostAuthenticationToken' => [
                $this->createPostAuthenticationToken('main')
            ],
            'UsernamePasswordToken' => [
                $this->createUsernamePasswordToken('main')
            ],
        ];
    }

    /**
     * @dataProvider invalidLoginProvider
     */
    public function testOnLoginSuccessWithInvalidScenarios(
        TokenInterface $token,
        ?UserInterface $user,
        string $scenario
    ): void {
        /** @phpstan-ignore-next-line */
        $token->method('getUser')->willReturn($user);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getAuthenticatedToken')->willReturn($token);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->subscriber->onLoginSuccess($event);
    }

    public function invalidLoginProvider(): array
    {
        return [
            'wrong firewall' => [
                $this->createPostAuthenticationToken('admin'),
                $this->createMock(User::class),
                'wrong firewall'
            ],
            'non-User instance' => [
                $this->createPostAuthenticationToken('main'),
                $this->createMock(UserInterface::class),
                'non-User instance'
            ],
            'null user' => [
                $this->createPostAuthenticationToken('main'),
                null,
                'null user'
            ],
            'unknown token type' => [
                $this->createMock(TokenInterface::class),
                $this->createMock(User::class),
                'unknown token type'
            ],
        ];
    }

    /**
     * @dataProvider firewallNameProvider
     */
    public function testGetFirewallName(TokenInterface $token, ?string $expectedFirewall): void
    {
        $reflection = new \ReflectionClass($this->subscriber);
        $method = $reflection->getMethod('getFirewallName');

        $result = $method->invoke($this->subscriber, $token);
        $this->assertSame($expectedFirewall, $result);
    }

    public function firewallNameProvider(): array
    {
        return [
            'PostAuthenticationToken main' => [
                $this->createPostAuthenticationToken('main'),
                'main'
            ],
            'PostAuthenticationToken admin' => [
                $this->createPostAuthenticationToken('admin'),
                'admin'
            ],
            'UsernamePasswordToken main' => [
                $this->createUsernamePasswordToken('main'),
                'main'
            ],
            'UsernamePasswordToken api' => [
                $this->createUsernamePasswordToken('api'),
                'api'
            ],
            'unknown token type' => [
                $this->createMock(TokenInterface::class),
                'unknown'
            ],
        ];
    }

    public function testLastLoginDateIsSet(): void
    {
        $user = $this->createMock(User::class);
        $token = $this->createPostAuthenticationToken('main');
        /** @phpstan-ignore-next-line */
        $token->method('getUser')->willReturn($user);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getAuthenticatedToken')->willReturn($token);

        $capturedDateTime = null;
        $user->expects($this->once())
            ->method('setLastLogin')
            ->willReturnCallback(function(\DateTime $dateTime) use (&$capturedDateTime, $user) {
                $capturedDateTime = $dateTime;
                return $user;
            });

        $this->entityManager->expects($this->once())->method('flush');

        $this->subscriber->onLoginSuccess($event);

        $this->assertInstanceOf(\DateTime::class, $capturedDateTime);
        $this->assertEqualsWithDelta(new \DateTime(), $capturedDateTime, 2);
    }

    private function createPostAuthenticationToken(string $firewallName): PostAuthenticationToken
    {
        $token = $this->createMock(PostAuthenticationToken::class);
        $token->method('getFirewallName')->willReturn($firewallName);
        return $token;
    }

    private function createUsernamePasswordToken(string $firewallName): UsernamePasswordToken
    {
        $token = $this->createMock(UsernamePasswordToken::class);
        $token->method('getFirewallName')->willReturn($firewallName);
        return $token;
    }
}