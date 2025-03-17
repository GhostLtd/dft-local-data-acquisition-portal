<?php

namespace App\Controller\Frontend;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\Scheme;
use App\Form\Type\SchemeType;
use App\ListPage\SchemeManageListPage;
use App\Utility\Breadcrumb\Frontend\SchemesLinksBuilder;
use App\Utility\ConfirmAction\Frontend\SchemeDeleteConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SchemeController extends AbstractController
{
    public function __construct(
        protected SchemesLinksBuilder    $linksBuilder,
        protected SchemeManageListPage   $schemeManageListPage,
        protected RouterInterface        $router,
        protected EntityManagerInterface $entityManager,
    ) {}

    #[IsGranted(Role::CAN_MANAGE_SCHEMES, 'authority')]
    #[Route('/authority/{authorityId}/schemes', name: 'app_schemes_authority')]
    public function schemes(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority $authority,
        Request   $request,
    ): Response
    {
        $this->linksBuilder->setAtSchemes($authority);

        $this->schemeManageListPage
            ->setAuthority($authority)
            ->handleRequest($request);

        if ($this->schemeManageListPage->isClearClicked()) {
            return new RedirectResponse($this->schemeManageListPage->getClearUrl());
        }

        return $this->render('frontend/scheme/list.html.twig', [
            'authority' => $authority,
            'linksBuilder' => $this->linksBuilder,
            'listPage' => $this->schemeManageListPage,
        ]);
    }

    #[IsGranted(Role::CAN_MANAGE_SCHEMES, 'authority')]
    #[Route('/authority/{authorityId}/schemes/{schemeId}/edit', name: 'app_scheme_edit')]
    public function edit(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority $authority,
        #[MapEntity(expr: 'repository.find(schemeId)')]
        Scheme    $scheme,
        Request   $request,
    ): Response
    {
        if (!$this->doesSchemeBelongToAuthority($authority, $scheme)) {
            throw new NotFoundHttpException();
        }

        $this->linksBuilder->setAtSchemeEdit($authority, $scheme);
        $cancelUrl = $this->router->generate('app_scheme', ['authorityId' => $authority->getId(), 'schemeId' => $scheme->getId()]);

        return $this->addOrEdit($authority, $scheme, $request, $cancelUrl, SchemeType::MODE_EDIT);
    }

    #[IsGranted(Role::CAN_MANAGE_SCHEMES, 'authority')]
    #[Route('/authority/{authorityId}/schemes/add', name: 'app_scheme_add')]
    public function add(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority $authority,
        Request   $request,
    ): Response
    {
        $this->linksBuilder->setAtSchemeAdd($authority);
        $cancelUrl = $this->router->generate('app_schemes_authority', ['authorityId' => $authority->getId()]);

        return $this->addOrEdit($authority, new Scheme(), $request, $cancelUrl, SchemeType::MODE_ADD);
    }

    #[IsGranted(Role::CAN_MANAGE_SCHEMES, 'authority')]
    #[Route('/authority/{authorityId}/schemes/{schemeId}', name: 'app_scheme')]
    public function view(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority                   $authority,
        #[MapEntity(expr: 'repository.find(schemeId)')]
        Scheme                      $scheme,
    ): Response
    {
        if (!$this->doesSchemeBelongToAuthority($authority, $scheme)) {
            throw new NotFoundHttpException();
        }

        $this->linksBuilder->setAtScheme($authority, $scheme);

        return $this->render('frontend/scheme/view.html.twig', [
            'authority' => $authority,
            'linksBuilder' => $this->linksBuilder,
            'scheme' => $scheme,
        ]);
    }

    #[IsGranted(Role::CAN_MANAGE_SCHEMES, 'authority')]
    #[Route('/authority/{authorityId}/schemes/{schemeId}/delete', name: 'app_scheme_delete')]
    #[Template('frontend/scheme/delete.html.twig')]
    public function delete(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority                   $authority,
        #[MapEntity(expr: 'repository.find(schemeId)')]
        Scheme                      $scheme,
        SchemeDeleteConfirmAction   $schemeDeleteConfirmAction,
        Request                     $request,
    ): array|RedirectResponse
    {
        if (!$this->doesSchemeBelongToAuthority($authority, $scheme)) {
            throw new NotFoundHttpException();
        }

        $this->linksBuilder->setAtSchemeDelete($authority, $scheme);

        return $schemeDeleteConfirmAction
            ->setSubject($scheme)
            ->setExtraViewData([
                'authority' => $authority,
                'linksBuilder' => $this->linksBuilder,
                'scheme' => $scheme,
            ])
            ->controller(
                $request,
                $this->generateUrl('app_schemes_authority', ['authorityId' => $authority->getId()]),
                $this->generateUrl('app_scheme', ['authorityId' => $authority->getId(), 'schemeId' => $scheme->getId()])
            );
    }

    protected function addOrEdit(Authority $authority, Scheme $scheme, Request $request, string $cancelUrl, string $addOrEdit): Response
    {
        $form = $this->createForm(SchemeType::class, $scheme, [
            'add_or_edit' => $addOrEdit,
            'authority' => $authority,
            'cancel_url' => $cancelUrl,
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
                $url = $this->router->generate('app_scheme', ['authorityId' => $authority->getId(), 'schemeId' => $scheme->getId()]);
                return new RedirectResponse($url);
            }
        }

        return $this->render("frontend/scheme/add_or_edit.html.twig", [
            'authority' => $authority,
            'form' => $form,
            'linksBuilder' => $this->linksBuilder,
            'scheme' => $scheme,
        ]);
    }
    
    protected function doesSchemeBelongToAuthority(Authority $authority, Scheme $scheme): bool
    {
        return $scheme->getAuthority() === $authority;
    }
}
