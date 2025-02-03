<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\TransportMode;
use App\Entity\Enum\TransportModeCategory;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Form\Type\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
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
                'choice_options' => fn(TransportModeCategory $choice) => [
                    'conditional_form_name' => 'transportMode'.ucfirst($choice->value),
                ],
                'choice_value' => fn(?TransportModeCategory $choice) => $choice->value ?? null,
            ]);

        foreach($categories as $category) {
            $choices = TransportMode::filterByCategory($category);

            $builder->add('transportMode'.ucfirst($category->value), ChoiceType::class, [
                'label' => "forms.scheme.transport_mode.{$category->value}.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.transport_mode.{$category->value}.help",
                'choices' => $choices,
                'choice_label' => fn(TransportMode $choice) => "enum.transport_mode.{$choice->value}",
                'choice_value' => fn(?TransportMode $choice) => $choice->value ?? null,
                'expanded' => false,
                'placeholder' => 'forms.generic.placeholder',
            ]);
        }
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('data_class', SchemeReturn::class)
            ->setDefault('validation_groups', ['scheme_transport_mode'])
            ->setDefault('error_mapping', [
                'schemeFund.scheme.transportMode' => 'transportModeCategory',
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

        $viewData->getSchemeFund()->getScheme()->setTransportMode($transportMode);
    }
}
