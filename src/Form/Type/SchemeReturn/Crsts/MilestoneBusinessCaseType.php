<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\BusinessCase;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MilestoneBusinessCaseType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->add('businessCase', ChoiceType::class, [
                'label' => "forms.scheme.milestone_business_case.business_case.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_business_case.business_case.help",
                'choices' => BusinessCase::cases(),
                'choice_label' => fn(BusinessCase $choice) => "enum.business_case.{$choice->value}",
                'choice_value' => fn(?BusinessCase $choice) => $choice?->value,
                'choice_options' => function(?BusinessCase $choice) {
                    $options = [];

                    if ($choice === BusinessCase::NOT_APPLICABLE) {
                        $options['conditional_hide_form_names'] = ['expectedBusinessCaseApproval'];
                    }

                    return $options;
                },
            ])
            ->add('expectedBusinessCaseApproval', DateType::class, [
                'label' => "forms.scheme.milestone_business_case.expected_business_case_approval.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_business_case.expected_business_case_approval.help",
            ]);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrstsSchemeReturn::class,
            'validation_groups' => ['milestone_business_case'],
        ]);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof CrstsSchemeReturn) {
            throw new UnexpectedTypeException($viewData, CrstsSchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $forms['businessCase']->setData($viewData->getBusinessCase());
        $forms['expectedBusinessCaseApproval']->setData($viewData->getExpectedBusinessCaseApproval());
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof CrstsSchemeReturn) {
            throw new UnexpectedTypeException($viewData, CrstsSchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        /** @var ?BusinessCase $businessCase */
        $businessCase = $forms['businessCase']->getData();

        $expectedBusinessCaseApproval = ($businessCase === BusinessCase::NOT_APPLICABLE) ?
            null :
            $forms['expectedBusinessCaseApproval']->getData();

        $viewData
            ->setBusinessCase($businessCase)
            ->setExpectedBusinessCaseApproval($expectedBusinessCaseApproval);
    }
}
