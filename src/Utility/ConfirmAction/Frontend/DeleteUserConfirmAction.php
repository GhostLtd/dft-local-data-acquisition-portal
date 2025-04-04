<?php

namespace App\Utility\ConfirmAction\Frontend;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteUserConfirmAction extends AbstractConfirmAction
{
    /** @var User */
    protected mixed $subject;

    public function __construct(
        FormFactoryInterface             $formFactory,
        RequestStack                     $requestStack,
        protected EntityManagerInterface $entityManager
    )
    {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    public function getTranslationDomain(): ?string
    {
        return 'messages';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'frontend.pages.user_delete';
    }

    public function getTranslationParameters(): array
    {
        return [
            'userName' => $this->subject->getName(),
            'userIdentifier' => $this->subject->getUserIdentifier(),
        ];
    }

    #[\Override]
    public function doConfirmedAction($formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}
