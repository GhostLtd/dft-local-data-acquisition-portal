<?php

namespace App\Form\SchemeReturn\Crsts;

use App\Entity\Enum\BusinessCase;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MilestoneDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('businessCase', ChoiceType::class, [
                'label' => "forms.scheme.milestone_details.business_case.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_details.business_case.help",
                'choices' => BusinessCase::cases(),
                'choice_label' => fn(BusinessCase $choice) => "enum.business_case.{$choice->value}",
                'choice_value' => fn(BusinessCase $choice) => $choice->value,
            ])
            ->add('expectedBusinessCaseApproval', DateType::class, [
                'label' => "forms.scheme.milestone_details.expected_business_case_approval.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_details.expected_business_case_approval.help",
            ])
            ->add('progressUpdate', TextareaType::class, [
                'label' => "forms.scheme.milestone_details.progress_update.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_details.progress_update.help",
            ]);
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', CrstsSchemeReturn::class);
    }
}
