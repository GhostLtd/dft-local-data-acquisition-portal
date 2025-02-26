<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\TransportMode;
use App\Entity\Enum\TransportModeCategory;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use PhpParser\Builder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchemeTransportModeType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = TransportModeCategory::cases();

        $builder
            ->setDataMapper($this)
            ->add('transportModeCategory', ChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.category.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.category.help',
                'choices' => $categories,
                'choice_label' => fn(TransportModeCategory $choice) => "enum.transport_mode.categories.{$choice->value}",
                'choice_options' => function(TransportModeCategory $choice) {
                    $options = [
                        'conditional_form_name' => 'transportMode'.ucfirst($choice->value),
                    ];

                    if ($choice === TransportModeCategory::ACTIVE_TRAVEL) {
                        $options['conditional_hide_form_names'] = ['hasActiveTravelElements'];
                    }

                    return $options;
                },
                'choice_value' => fn(?TransportModeCategory $choice) => $choice->value ?? null,
            ]);

        foreach($categories as $category) {
            $choices = TransportMode::filterByCategory($category);

            $builder->add('transportMode'.ucfirst($category->value), ChoiceType::class, [
                'label' => "forms.scheme.transport_mode.categories.{$category->value}.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.transport_mode.categories.{$category->value}.help",
                'choices' => $choices,
                'choice_label' => fn(TransportMode $choice) => "enum.transport_mode.{$choice->value}",
                'choice_value' => fn(?TransportMode $choice) => $choice->value ?? null,
                'expanded' => false,
                'placeholder' => 'forms.generic.placeholder',
            ]);
        }

        $builder
            ->add('hasActiveTravelElements', BooleanChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.has_active_travel_elements.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.has_active_travel_elements.help',
                'choice_options' => [
                    'boolean.true' => [
                        'conditional_form_name' => 'activeTravelElement',
                    ]
                ],
            ])
            ->add('activeTravelElement', ChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.active_travel_element.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.active_travel_element.help',
                'choices' => ActiveTravelElement::casesExcludingNoElements(),
                'choice_label' => fn(ActiveTravelElement $choice) => "enum.active_travel_element.{$choice->value}",
                'choice_value' => fn(?ActiveTravelElement $choice) => $choice?->value,
                'expanded' => false,
                'placeholder' => 'forms.generic.placeholder',
            ])
            ->add('includesChargingPoints', BooleanChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.includes_charging_points.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.includes_charging_points.help',
            ])
            ->add('includesCleanAirElements', BooleanChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.includes_clean_air_elements.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.includes_clean_air_elements.help',
            ]);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('data_class', SchemeReturn::class)
            ->setDefault('validation_groups', ['scheme_transport_mode'])
            ->setDefault('error_mapping', [
                'schemeFund.scheme.transportMode' => 'transportModeCategory',
                'schemeFund.scheme.hasActiveTravelElements' => 'hasActiveTravelElements',
            ]);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof SchemeReturn) {
            throw new UnexpectedTypeException($viewData, SchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $scheme = $viewData->getSchemeFund()->getScheme();
        $transportMode = $scheme?->getTransportMode();

        if (!$transportMode) {
            return;
        }

        $category = $transportMode->category();
        $forms['transportModeCategory']->setData($category);
        $forms['transportMode'.ucfirst($category->value)]->setData($transportMode);

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

        $category = $forms['transportModeCategory']->getData();
        $transportMode = $category ? $forms['transportMode'.ucfirst($category->value)]->getData() : null;

        $scheme = $viewData->getSchemeFund()->getScheme();

        if ($category === TransportModeCategory::ACTIVE_TRAVEL) {
            $activeTravelElement = null;
        } else {
            $activeTravelElement = $forms['hasActiveTravelElements']->getData();

            if ($activeTravelElement !== null) {
                $activeTravelElement = $forms['hasActiveTravelElements']->getData() ?
                    $forms['activeTravelElement']->getData() :
                    ActiveTravelElement::NO_ACTIVE_TRAVEL_ELEMENTS;
            }
        }

        $scheme
            ->setTransportMode($transportMode)
            ->setActiveTravelElement($activeTravelElement)
            ->setIncludesChargingPoints($forms['includesChargingPoints']->getData())
            ->setIncludesCleanAirElements($forms['includesCleanAirElements']->getData());
    }
}
