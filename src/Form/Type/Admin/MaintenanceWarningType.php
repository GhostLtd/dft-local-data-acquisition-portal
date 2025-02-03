<?php

namespace App\Form\Type\Admin;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MaintenanceWarningType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDatetime', Gds\DateTimeType::class,[
                'label' => 'admin.maintenance.warning_form.start',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => null,
                'time_options' => [
                    'expanded' => false,
                ],
            ])
            ->add('endTime', Gds\TimeType::class, [
                'label' => 'admin.maintenance.warning_form.end',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => null,
                'expanded' => false,
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'forms.buttons.submit',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'forms.buttons.cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
            ;
    }
}
