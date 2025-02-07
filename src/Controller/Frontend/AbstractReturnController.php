<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Utility\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractReturnController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {}

    protected function processForm(
        FormInterface $form,
        Request $request,
        string $cancelUrl,
    ): ?RedirectResponse
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = FormHelper::whichButtonClicked($form);
            if ($clickedButton) {
                $this->entityManager->flush();
                return new RedirectResponse($cancelUrl);
            }
        }

        return null;
    }
}
