<?php

namespace App\Controller\Frontend;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\User;
use App\Form\Type\UserType;
use App\ListPage\UserListPage;
use App\Utility\Breadcrumb\Frontend\UsersLinksBuilder;
use App\Utility\SimplifiedPermissionsHelper;
use App\Utility\UserReachableEntityResolver;
use Doctrine\ORM\EntityManagerInterface;
use GPBMetadata\Google\Api\Auth;
use PharIo\Manifest\Author;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UsersController extends AbstractController
{
    public function __construct(
        protected UsersLinksBuilder           $linksBuilder,
        protected UserListPage                $userListPage,
        protected UserReachableEntityResolver $userReachableEntityResolver, private readonly RouterInterface $router, private readonly EntityManagerInterface $entityManager,
    ) {}

    #[IsGranted(Role::CAN_MANAGE_USERS, 'authority')]
    #[Route('/authority/{authorityId}/users', name: 'app_users')]
    public function users(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority $authority,
        Request   $request,
    ): Response
    {
        $this->linksBuilder->setAtUsers($authority);

        $this->userListPage
            ->setAuthority($authority)
            ->handleRequest($request);

        if ($this->userListPage->isClearClicked()) {
            return new RedirectResponse($this->userListPage->getClearUrl());
        }

        return $this->render('frontend/users/list.html.twig', [
            'authority' => $authority,
            'linksBuilder' => $this->linksBuilder,
            'listPage' => $this->userListPage,
        ]);
    }

    #[IsGranted(Role::CAN_MANAGE_USERS, 'authority')]
    #[Route('/authority/{authorityId}/users/{userId}/edit', name: 'app_user_edit')]
    public function edit(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority $authority,
        #[MapEntity(expr: 'repository.find(userId)')]
        User      $user,
        Request   $request,
    ): Response
    {
        if (!$this->userReachableEntityResolver->isAuthorityReachableBy($authority, $user)) {
            throw new NotFoundHttpException();
        }

        $this->linksBuilder->setAtUserEdit($authority, $user);
        $cancelUrl = $this->router->generate('app_user', ['authorityId' => $authority->getId(), 'userId' => $user->getId()]);

        return $this->addOrEdit($authority, $user, $request, $cancelUrl);
    }

    #[IsGranted(Role::CAN_MANAGE_USERS, 'authority')]
    #[Route('/authority/{authorityId}/users/add', name: 'app_user_add')]
    public function add(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority $authority,
        Request   $request,
    ): Response
    {
        $this->linksBuilder->setAtUserAdd($authority);
        $cancelUrl = $this->router->generate('app_users', ['authorityId' => $authority->getId()]);

        return $this->addOrEdit($authority, new User(), $request,$cancelUrl);
    }

    #[IsGranted(Role::CAN_MANAGE_USERS, 'authority')]
    #[Route('/authority/{authorityId}/users/{userId}', name: 'app_user')]
    public function view(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority                   $authority,
        #[MapEntity(expr: 'repository.find(userId)')]
        User                        $user,
        SimplifiedPermissionsHelper $userPermissionHelper,
    ): Response
    {
        if (!$this->userReachableEntityResolver->isAuthorityReachableBy($authority, $user)) {
            throw new NotFoundHttpException();
        }

        $this->linksBuilder->setAtUser($authority, $user);

        return $this->render('frontend/users/view.html.twig', [
            'authority' => $authority,
            'simplifiedPermission' => $userPermissionHelper->getSimplifiedPermissionAsString($user, $authority),
            'linksBuilder' => $this->linksBuilder,
            'user' => $user,
        ]);
    }

    protected function addOrEdit(Authority $authority, User $user, Request $request, string $cancelUrl): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'cancel_url' => $cancelUrl,
            'authority' => $authority,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $saveButton = $form->get('buttons')?->get('save');
            if ($saveButton instanceof SubmitButton && $saveButton->isClicked() && $form->isValid()) {
                $data = $form->getData();
                if (!$this->entityManager->contains($data)) {
                    $this->entityManager->persist($data);
                }

                $this->entityManager->flush();
                $url = $this->router->generate('app_user', ['authorityId' => $authority->getId(), 'userId' => $user->getId()]);
                return new RedirectResponse($url);
            }
        }

        return $this->render("frontend/users/add_or_edit.html.twig", [
            'authority' => $authority,
            'form' => $form,
            'linksBuilder' => $this->linksBuilder,
            'user' => $user,
        ]);
    }
}
