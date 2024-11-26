<?php

namespace App\Form\ProjectReturn\Crsts;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Form\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', InputType::class, [
                'label' => 'forms.crsts.project_details.name.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'projectFund.project.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'forms.crsts.project_details.description.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'projectFund.project.description',
            ])
            ->add('projectIdentifier', InputType::class, [
                'label' => 'forms.crsts.project_details.project_identifier.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'projectFund.project.projectIdentifier',
            ])
            ->add('previouslyTcf', BooleanChoiceType::class, [
                'label' => 'forms.crsts.project_details.previously_tcf.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.crsts.project_details.previously_tcf.help',
                'property_path' => 'projectFund.previouslyTcf',
            ])
            ->add('benefitCostRatio', BenefitCostRatioType::class, [
                'label' => false,
                'property_path' => 'projectFund.benefitCostRatio',
            ])
            ->add('fundedMostlyAs', ChoiceType::class, [
                'label' => 'forms.crsts.project_details.funded_mostly_as.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.crsts.project_details.funded_mostly_as.help',
                'choices' => FundedMostlyAs::cases(),
                'choice_label' => fn(FundedMostlyAs $choice) => "enum.funded_mostly_as.{$choice->value}",
                'choice_value' => fn(FundedMostlyAs $choice) => $choice->value,
                'property_path' => 'projectFund.fundedMostlyAs',
            ])
        ;
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', CrstsProjectReturn::class);
    }
}
