<?php

namespace App\Security;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailAuthenticationHandler implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface, AuthenticationSuccessHandlerInterface
{

    public function __construct(
        protected LoggerInterface $logger,
        protected RequestRateLimiterInterface $loginLimiter,
        protected RequestStack $requestStack,
        protected TranslatorInterface $translator,
        protected UrlGeneratorInterface $urlGenerator
    ) {}

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $this->loginLimiter->reset($this->requestStack->getMainRequest());

        $user = $token->getUser();
        $this->logger->info("Login successful: {$user->getUserIdentifier()}");

        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = $exception->getPrevious()?->getMessage();

        if (!$message) {
            $message = $exception->getMessage();
        }

        if ($message === 'User not found.') {
            // Ultimately, these messages are generated in:
            // * LoginLinkHandler -> consumeLoginLink()
            // * SignatureHasher  -> verifySignatureHash()
            //
            // We want to give some more detail (e.g. whether it's invalid or whether its expired), but don't want to
            // info that could potentially be a security information leak (e.g. User not found).
            $message = 'Invalid or expired login link.';
        }

        if ($message === 'Login link can only be used "1" times.') {
            $message = 'Login link can only be used once.';
        }

        $user = $request->request->get('user', '<none>');
        $this->logger->info("Login failed: {$user} - {$message}");

        $exception = new AuthenticationException($message);
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    #[\Override]
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
