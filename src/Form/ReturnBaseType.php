<?php

namespace App\Form;

use App\Entity\Enum\CompletionStatus;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReturnBaseType extends AbstractType
{
    const string SAVE = 'save';
    const string MARK_AS_COMPLETED = 'mark_as_completed';
    const string MARK_AS_IN_PROGRESS = 'mark_as_in_progress';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var CompletionStatus $completionStatus */
        $completionStatus = $options['completion_status'];

        $secondaryButtonName = $completionStatus === CompletionStatus::COMPLETED ?
            self::MARK_AS_IN_PROGRESS :
            self::MARK_AS_COMPLETED;

        $builder->add('buttons', ButtonGroupType::class, [
            'priority' => -1000,
        ]);

        $builder->get('buttons')
            ->add(self::SAVE, ButtonType::class, [
                'type' => 'submit',
                'label' => 'forms.buttons.save',
            ])
            ->add($secondaryButtonName, ButtonType::class, [
                'type' => 'submit',
                'label' => "forms.buttons.{$secondaryButtonName}",
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'forms.buttons.cancel',
                'href' => $options['cancel_url'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('cancel_url')
            ->setAllowedTypes('cancel_url', 'string')
            ->setRequired('completion_status')
            ->setAllowedTypes('completion_status', CompletionStatus::class);
    }
}
