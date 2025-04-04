<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $token = $event->getAuthenticatedToken();
        $user = $token->getUser();
        if ($this->getFirewallName($token) !== 'main' || !$user instanceof User) {
            return;
        }

        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();
    }

    protected function getFirewallName(TokenInterface $token): ?string
    {
        if (!$token instanceof PostAuthenticationToken
            && !$token instanceof UsernamePasswordToken
        ) {
            return 'unknown';
        }
        return $token->getFirewallName();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}