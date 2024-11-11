<?php

namespace App\Utility\ConfirmAction;

use App\Entity\MaintenanceWarning;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteMaintenanceWarningConfirmAction extends AbstractConfirmAction
{
    /** @var MaintenanceWarning */
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

    #[\Override]
    public function getTranslationParameters(): array
    {
        return [
            'start' => $this->subject->getStartDatetime()->getTimestamp(),
            'end' => $this->subject->getEndTime()->getTimestamp(),
        ];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'admin.maintenance.delete';
    }

    #[\Override]
    public function doConfirmedAction($formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}
