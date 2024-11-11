<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Features;
use App\Form\LoginType;
use App\Messenger\AlphagovNotify\LoginEmail;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

class FrontendAuthController extends AbstractController
{
    #[Route("/login", name: "app_login")]
    public function login(
        Features                     $features,
        LoginLinkHandlerInterface    $loginLinkHandler,
        LoggerInterface              $logger,
        MaintenanceWarningRepository $maintenanceWarningRepository,
        MessageBusInterface          $messageBus,
        Request                      $request,
        RequestRateLimiterInterface  $loginLimiter,
        UserRepository               $userRepository,
        TranslatorInterface          $translator,
    ): Response {
        $user = $this->getUser();

        if ($user instanceof User) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        $authenticationError = $this->getAuthenticationError($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['email'];

            try {
                $user = $userRepository->loadUserByIdentifier($email);

                if ($user) {
                    $loginLimiter->consume($request)->ensureAccepted();
                    $loginLinkDetails = $loginLinkHandler->createLoginLink($user);

                    if ($this->getParameter('kernel.environment') === 'dev' &&
                        $features->isEnabled(Features::FEATURE_DEV_AUTO_LOGIN)
                    ) {
                        $logger->info("Login submitted: {$email} - success - DEV mode auto-redirect");
                        return new RedirectResponse($loginLinkDetails->getUrl());
                    }

                    $logger->info("Login submitted: {$email} - check-email page, message dispatched");

                    $messageBus->dispatch(
                        new LoginEmail($email, ['login_link' => $loginLinkDetails->getUrl()])
                    );
                } else {
                    $logger->info("Login submitted: {$email} - check-email page, no such user");
                }

                return $this->redirectToRoute('app_login_check_email');
            }
            catch(RateLimitExceededException $e) {
                $authenticationError = new AuthenticationException($translator->trans('auth.rate_limit_error', [
                     'retry_after' => $e->getRateLimit()->getRetryAfter()->format('Y-m-d H:i:s'),
                ]));
                $logger->info("Login submitted: {$email} - failure - rate-limit hit");
            }
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form,
            'authenticationError' => $authenticationError,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }

    #[Route("/login/check-email", name: "app_login_check_email")]
    public function loginCheckEmail(Security $security): Response {
        if ($security->getUser() !== null) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('auth/login_check_email.html.twig');
    }

    #[Route("/login/authenticate", name: "app_login_check")]
    public function loginCheck(Request $request): Response
    {
        $expires = $request->query->get('expires');
        $username = $request->query->get('user');
        $hash = $request->query->get('hash');

        if (!$expires || !$username || !$hash) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/login_process.html.twig', [
            'expires' => $expires,
            'user' => $username,
            'hash' => $hash,
        ]);
    }

    #[Route("/logout", name: "app_logout")]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    public function getAuthenticationError(Request $request): ?AuthenticationException
    {
        $session = $request->getSession();
        $authenticationError = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        $session->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);

        return $authenticationError;
    }
}
