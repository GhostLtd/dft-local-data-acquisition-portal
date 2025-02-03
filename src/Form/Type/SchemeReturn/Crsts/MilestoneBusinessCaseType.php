<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\BusinessCase;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MilestoneBusinessCaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('businessCase', ChoiceType::class, [
                'label' => "forms.scheme.milestone_business_case.business_case.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_business_case.business_case.help",
                'choices' => BusinessCase::cases(),
                'choice_label' => fn(BusinessCase $choice) => "enum.business_case.{$choice->value}",
                'choice_value' => fn(?BusinessCase $choice) => $choice?->value,
            ])
            ->add('expectedBusinessCaseApproval', DateType::class, [
                'label' => "forms.scheme.milestone_business_case.expected_business_case_approval.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_business_case.expected_business_case_approval.help",
            ]);
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrstsSchemeReturn::class,
            'validation_groups' => ['milestone_business_case'],
        ]);
    }
}
