<?php

namespace App\Controller\Admin;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\RandomFixtureGenerator;
use App\Entity\Authority;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\Admin\AuthorityType;
use App\ListPage\AuthorityListPage;
use App\Utility\FinancialQuarter;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/authority', name: 'admin_authority')]
class AuthorityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RandomFixtureGenerator $randomFixtureGenerator,
        private readonly FixtureHelper          $fixtureHelper,
    ) {}

    #[Route(path: '', name: '')]
    public function list(AuthorityListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/authority/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: '_edit')]
    public function edit(Request $request, Session $session, Authority $authority, string $type='edit'): Response
    {
        /** @var Form $form */
        $form = $this->createForm(AuthorityType::class, $authority, [
            'cancel_url' => $this->generateUrl('admin_authority'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton()->getName() === 'cancel') {
                return $this->redirectToRoute('admin_authority');
            }

            if ($form->isValid()) {
                $authority = $form->getData();
                if (!$form->getData()->getId()) {
                    $this->entityManager->persist($authority);
                    $this->createAssetsForNewAuthority($authority);
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Authority added', 'The new authority has been added', ['style' => NotificationBanner::STYLE_SUCCESS]));
                } else {
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Authority updated', 'The authority has been updated', ['style' => NotificationBanner::STYLE_SUCCESS]));
                }
                if (!$authority?->getAdmin()?->getId()) {
                    $this->entityManager->persist($authority->getAdmin());
                }

                $this->entityManager->flush();
                return $this->redirectToRoute('admin_authority');
            }
        }

        return $this->render('admin/authority/edit.html.twig', [
            'form' => $form,
            'authority' => $form->getData(),
            'type' => $type,
        ]);
    }

    #[Route(path: '/add', name: '_add')]
    public function add(Request $request, Session $session): Response
    {
        return $this->edit($request, $session, new Authority(), 'add');
    }

    protected function createAssetsForNewAuthority(Authority $authority): void
    {
        $this->randomFixtureGenerator->setSeed(random_int(0, PHP_INT_MAX));
        $this->fixtureHelper->setEntityManager($this->entityManager);

        $returnQuarter = FinancialQuarter::createFromDate(new \DateTime('6 months ago'));
        [$schemes, $fundAwards] = $this->randomFixtureGenerator
            ->createSchemeAndFundAwardDefinitions($returnQuarter, $returnQuarter);
        $this->fixtureHelper->processSchemeAndFundDefinitions($authority, $schemes, $fundAwards);

        // sign off return
        /** @var CrstsFundReturn $existingReturn */
        $existingReturn = $authority->getFundAwards()->first()->getReturns()->first();
        $existingReturn->signoff($authority->getAdmin());

        // create new return for following quarter, from existing one.
        $nextReturn = $existingReturn->createFundReturnForNextQuarter();
        $this->entityManager->persist($nextReturn);
//        $nextReturn->getSchemeReturns()->map(fn($sr) => $this->entityManager->persist($sr));
//        $nextReturn->getExpenses()->map(fn($ex) => $this->entityManager->persist($ex));
//        $nextReturn->getSchemeReturns()->map(fn(CrstsSchemeReturn $sr) => $sr->getExpenses()->map(fn($ex) => $this->entityManager->persist($ex)));

    }
}
