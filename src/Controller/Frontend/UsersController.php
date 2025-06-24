<?php

namespace App\Controller\Frontend;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\MaintenanceWarning;
use App\Entity\User;
use App\Form\Type\UserType;
use App\ListPage\UserListPage;
use App\Utility\Breadcrumb\Frontend\UsersLinksBuilder;
use App\Utility\ConfirmAction\Admin\DeleteMaintenanceWarningConfirmAction;
use App\Utility\ConfirmAction\Frontend\DeleteUserConfirmAction;
use App\Utility\SimplifiedPermissionsHelper;
use App\Utility\UserReachableEntityResolver;
use Doctrine\ORM\EntityManagerInterface;
use GPBMetadata\Google\Api\Auth;
use PharIo\Manifest\Author;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Role::CAN_MANAGE_USERS, 'authority')]
#[Route('/authority/{authorityId}/users', name: 'app_user_')]
class UsersController extends AbstractController
{
    public function __construct(
        protected UsersLinksBuilder           $linksBuilder,
        protected UserReachableEntityResolver $userReachableEntityResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'list')]
    public function users(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority    $authority,
        Request      $request,
        UserListPage $userListPage,
    ): Response
    {
        $this->linksBuilder->setAtUsers($authority);

        $userListPage
            ->setAuthority($authority)
            ->handleRequest($request);

        if ($userListPage->isClearClicked()) {
            return new RedirectResponse($userListPage->getClearUrl());
        }

        return $this->render('frontend/users/list.html.twig', [
            'authority' => $authority,
            'linksBuilder' => $this->linksBuilder,
            'listPage' => $userListPage,
        ]);
    }

    #[IsGranted(Role::CAN_EDIT_USER, 'user', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/{userId}/edit', name: 'edit')]
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
        $cancelUrl = $this->generateUrl('app_user_view', ['authorityId' => $authority->getId(), 'userId' => $user->getId()]);

        return $this->addOrEdit($authority, $user, $request, $cancelUrl);
    }

    #[Route('/add', name: 'add')]
    public function add(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority $authority,
        Request   $request,
    ): Response
    {
        $this->linksBuilder->setAtUserAdd($authority);
        $cancelUrl = $this->generateUrl('app_user_list', ['authorityId' => $authority->getId()]);

        return $this->addOrEdit($authority, new User(), $request,$cancelUrl);
    }

    #[Route('/{userId}', name: 'view')]
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
                $url = $this->generateUrl('app_user_list', ['authorityId' => $authority->getId()]);
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

    #[IsGranted(Role::CAN_EDIT_USER, 'user', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route(path: '/{userId}/delete', name: 'delete')]
    #[Template('frontend/users/delete.html.twig')]
    public function delete(
        Request $request,
        DeleteUserConfirmAction $userConfirmAction,
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority                   $authority,
        #[MapEntity(expr: 'repository.find(userId)')]
        User                        $user,
    ): RedirectResponse|array
    {
        if (!$this->userReachableEntityResolver->isAuthorityReachableBy($authority, $user)) {
            throw new NotFoundHttpException();
        }
        $this->linksBuilder->setAtUser($authority, $user);

        return $userConfirmAction
            ->setSubject($user)
            ->setExtraViewData([
                'linksBuilder' => $this->linksBuilder,
            ])
            ->controller(
                $request,
                $this->generateUrl('app_user_list', ['authorityId' => $authority->getId()]  ),
                $this->generateUrl('app_user_view', ['authorityId' => $authority->getId(), 'userId' => $user->getId()]  )
            );
    }
}
