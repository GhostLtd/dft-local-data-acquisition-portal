<?php

namespace App\Form\Type\Admin;

use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenanceWarningType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDatetime', Gds\DateTimeType::class,[
                'label' => 'maintenance.warning_form.start',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => null,
                'time_options' => [
                    'expanded' => false,
                ],
            ])
            ->add('endTime', Gds\TimeType::class, [
                'label' => 'maintenance.warning_form.end',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => null,
                'expanded' => false,
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => 'admin']);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }
}
