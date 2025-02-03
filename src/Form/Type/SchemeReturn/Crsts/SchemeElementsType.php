<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Form\Type\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchemeElementsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->add('hasActiveTravelElements', BooleanChoiceType::class, [
                'label' => 'forms.scheme.scheme_elements.has_active_travel_elements.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.scheme_elements.has_active_travel_elements.help',
                'choice_options' => [
                    'boolean.true' => [
                        'conditional_form_name' => 'activeTravelElement',
                    ]
                ],
            ])
            ->add('activeTravelElement', ChoiceType::class, [
                'label' => 'forms.scheme.scheme_elements.active_travel_element.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.scheme_elements.active_travel_element.help',
                'choices' => ActiveTravelElement::casesExcludingNoElements(),
                'choice_label' => fn(ActiveTravelElement $choice) => "enum.active_travel_element.{$choice->value}",
                'choice_value' => fn(?ActiveTravelElement $choice) => $choice?->value,
                'expanded' => false,
                'placeholder' => 'forms.generic.placeholder',
            ])
            ->add('includesChargingPoints', BooleanChoiceType::class, [
                'label' => 'forms.scheme.scheme_elements.includes_charging_points.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.scheme_elements.includes_charging_points.help',
            ])
            ->add('includesCleanAirElements', BooleanChoiceType::class, [
                'label' => 'forms.scheme.scheme_elements.includes_clean_air_elements.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.scheme_elements.includes_clean_air_elements.help',
            ]);
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', SchemeReturn::class);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof SchemeReturn) {
            throw new UnexpectedTypeException($viewData, SchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $scheme = $viewData->getSchemeFund()->getScheme();
        $activeTravelElement = $scheme->getActiveTravelElement();

        $forms['hasActiveTravelElements']->setData($activeTravelElement?->isNoActiveElement());
        $forms['activeTravelElement']->setData($activeTravelElement);
        $forms['includesChargingPoints']->setData($scheme->includesChargingPoints());
        $forms['includesCleanAirElements']->setData($scheme->includesCleanAirElements());
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof SchemeReturn) {
            throw new UnexpectedTypeException($viewData, SchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $scheme = $viewData->getSchemeFund()->getScheme();
        $activeTravelElement = $forms['hasActiveTravelElements']->getData() ?
            $forms['activeTravelElement']->getData() :
            ActiveTravelElement::NO_ACTIVE_TRAVEL_ELEMENTS;

        $scheme
            ->setActiveTravelElement($activeTravelElement)
            ->setIncludesChargingPoints($forms['includesChargingPoints']->getData())
            ->setIncludesCleanAirElements($forms['includesCleanAirElements']->getData());
    }
}
