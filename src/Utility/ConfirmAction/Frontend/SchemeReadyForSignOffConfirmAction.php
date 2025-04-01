<?php

namespace App\Utility\ConfirmAction\Frontend;

use App\Controller\Frontend\SchemeReturn\ReadyForSignoffController;
use App\Entity\SchemeReturn\SchemeReturn;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SchemeReadyForSignOffConfirmAction extends AbstractConfirmAction
{
    public const string MARK_AS_READY = ReadyForSignoffController::MARK_AS_READY;
    public const string MARK_AS_NOT_READY = ReadyForSignoffController::MARK_AS_NOT_READY;


    /** @var SchemeReturn */
    protected mixed $subject;

    protected string $type;

    public function __construct(
        FormFactoryInterface             $formFactory,
        RequestStack                     $requestStack,
        protected EntityManagerInterface $entityManager,
        protected Security               $security,
    ) {
        parent::__construct($formFactory, $requestStack);
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::MARK_AS_READY, self::MARK_AS_NOT_READY])) {
            throw new \RuntimeException('');
        }

        $this->type = $type;
        return $this;
    }

    public function getTranslationKeyPrefix(): string
    {
        return match($this->type) {
            self::MARK_AS_READY => 'forms.scheme.mark_as_ready_for_signoff',
            self::MARK_AS_NOT_READY => "forms.scheme.mark_as_not_ready_for_signoff",
        };
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'label' => $this->getTranslationKeyPrefix() . '.confirm',
            ],
        ]);
    }

    public function doConfirmedAction(mixed $formData): void
    {
        $this->subject->setReadyForSignoff(match($this->type) {
            self::MARK_AS_READY => true,
            self::MARK_AS_NOT_READY => false,
        });
        $this->entityManager->flush();
    }
}